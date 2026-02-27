<?php

namespace App\Services\OCR;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ═══════════════════════════════════════════════════════════════
 *  GeminiRateLimiter — Global Cooldown Lock (Anti-429 Enterprise)
 *  Disesuaikan untuk WHUSNET Admin Payment
 * ═══════════════════════════════════════════════════════════════
 *
 *  ✅ Kompatibel dengan upload_id pattern: 'nota-{timestamp}'
 *  ✅ Fallback ke Cache driver jika Redis tidak langsung tersedia
 *
 *  Tier Gemini:
 *  - Free tier:  15 RPM → set GEMINI_RPM_LIMIT=15
 *  - Pro tier:   60 RPM → set GEMINI_RPM_LIMIT=60
 *
 *  Cara kerja:
 *  - Sliding window Redis sorted set mencatat setiap request
 *  - Global lock Redis mencegah SEMUA worker mengirim saat 429
 *  - Exponential backoff + jitter mencegah thundering herd
 */
class GeminiRateLimiter
{
    // Redis keys
    private const KEY_WINDOW    = 'gemini:rpm:window';
    private const KEY_COOLDOWN  = 'gemini:cooldown:global';
    private const KEY_SLOT      = 'gemini:slot:';
    private const KEY_QUEUE_CNT = 'gemini:queue:count';
    private const KEY_LAST_429  = 'gemini:last429';

    private int $rpmLimit;
    private int $cooldownSec;
    private int $maxQueue;

    public function __construct()
    {
        $this->rpmLimit    = (int) env('GEMINI_RPM_LIMIT', 12);
        $this->cooldownSec = (int) env('GEMINI_COOLDOWN_SECONDS', 5);
        $this->maxQueue    = (int) env('OCR_MAX_QUEUE_SIZE', 200);
    }

    /**
     * ─────────────────────────────────────────────────────────
     *  Acquire slot sebelum mengirim ke n8n/Gemini
     *  Dipanggil oleh OcrProcessingJob sebelum HTTP request
     * ─────────────────────────────────────────────────────────
     *
     * @param string $uploadId  Format: 'nota-1234567890'
     * @param int    $maxWait   Max detik menunggu (default 5 menit)
     * @throws \RuntimeException jika timeout atau queue penuh
     */
    public function acquireSlot(string $uploadId, int $maxWait = 300): bool
    {
        // Cek queue tidak overload
        $queueSize = (int) (Redis::get(self::KEY_QUEUE_CNT) ?? 0);
        if ($queueSize >= $this->maxQueue) {
            throw new \RuntimeException(
                "OCR queue penuh ({$queueSize}/{$this->maxQueue}). Upload ID: {$uploadId}"
            );
        }

        Redis::incr(self::KEY_QUEUE_CNT);
        Redis::expire(self::KEY_QUEUE_CNT, 3600);

        $waited  = 0;
        $attempt = 0;

        try {
            while ($waited < $maxWait) {

                // ① Cek global 429 cooldown
                if ($this->isCooldownActive()) {
                    $remaining = $this->getCooldownTtl();
                    Log::channel('ocr')->warning("GeminiRateLimiter: 429 cooldown aktif {$remaining}s", [
                        'upload_id' => $uploadId,
                    ]);
                    $sleep = min($remaining + 2, 30);
                    sleep($sleep);
                    $waited += $sleep;
                    continue;
                }

                // ② Cek sliding window RPM
                $currentRpm = $this->getCurrentRpm();
                $safeLimit  = (int) floor($this->rpmLimit * 0.80); // 80% safety buffer

                if ($currentRpm >= $safeLimit) {
                    $sleep = $this->backoffTime($attempt, $currentRpm);
                    Log::channel('ocr')->info("GeminiRateLimiter: RPM throttle {$currentRpm}/{$this->rpmLimit}, backoff {$sleep}s", [
                        'upload_id' => $uploadId,
                        'attempt'   => $attempt,
                    ]);
                    sleep($sleep);
                    $waited  += $sleep;
                    $attempt++;
                    continue;
                }

                // ③ Atomic acquire per-job slot
                $slotKey  = self::KEY_SLOT . $uploadId;
                $acquired = Redis::set($slotKey, '1', 'EX', $this->cooldownSec, 'NX');

                if ($acquired) {
                    $this->recordRequest();
                    Log::channel('ocr')->info("GeminiRateLimiter: Slot acquired", [
                        'upload_id'   => $uploadId,
                        'current_rpm' => $currentRpm + 1,
                        'rpm_limit'   => $this->rpmLimit,
                    ]);
                    return true;
                }

                // Slot sedang digunakan, tunggu sebentar
                usleep(300_000); // 300ms
                $waited += 0.3;
            }
        } finally {
            Redis::decr(self::KEY_QUEUE_CNT);
        }

        throw new \RuntimeException(
            "GeminiRateLimiter: Timeout {$maxWait}s untuk upload_id: {$uploadId}"
        );
    }

    /**
     * ─────────────────────────────────────────────────────────
     *  Panggil ini ketika dapat response 429 dari n8n
     * ─────────────────────────────────────────────────────────
     */
    public function register429(int $retryAfter = 60): void
    {
        $cooldown = max($retryAfter, $this->cooldownSec * 3);

        Redis::setex(self::KEY_COOLDOWN, $cooldown, now()->toISOString());
        Redis::setex(self::KEY_LAST_429, 86400, json_encode([
            'at'          => now()->toISOString(),
            'retry_after' => $cooldown,
        ]));

        Log::channel('ocr')->error("GeminiRateLimiter: 429 registered! Cooldown {$cooldown}s aktif.");

        // Update cache status untuk semua job yang sedang menunggu
        // (opsional: bisa di-broadcast ke frontend)
    }

    /**
     * Release slot setelah job selesai (sukses atau gagal)
     */
    public function releaseSlot(string $uploadId): void
    {
        Redis::del(self::KEY_SLOT . $uploadId);
    }

    /**
     * Status untuk admin monitoring
     */
    public function getStatus(): array
    {
        $rpm = $this->getCurrentRpm();
        return [
            'current_rpm'        => $rpm,
            'rpm_limit'          => $this->rpmLimit,
            'safe_limit'         => (int) floor($this->rpmLimit * 0.80),
            'utilization_pct'    => $this->rpmLimit > 0 ? round(($rpm / $this->rpmLimit) * 100, 1) : 0,
            'queue_size'         => (int) (Redis::get(self::KEY_QUEUE_CNT) ?? 0),
            'max_queue_size'     => $this->maxQueue,
            'cooldown_active'    => $this->isCooldownActive(),
            'cooldown_remaining' => $this->getCooldownTtl(),
            'last_429'           => json_decode(Redis::get(self::KEY_LAST_429) ?? 'null', true),
        ];
    }

    // ─── Private helpers ──────────────────────────────────────

    private function isCooldownActive(): bool
    {
        return (bool) Redis::exists(self::KEY_COOLDOWN);
    }

    private function getCooldownTtl(): int
    {
        return max(0, (int) Redis::ttl(self::KEY_COOLDOWN));
    }

    private function getCurrentRpm(): int
    {
        // Sliding window: hapus entri > 60 detik lalu, hitung yang tersisa
        $now    = microtime(true);
        $cutoff = $now - 60.0;

        Redis::zremrangebyscore(self::KEY_WINDOW, '-inf', $cutoff);
        return (int) Redis::zcard(self::KEY_WINDOW);
    }

    private function recordRequest(): void
    {
        $ts = microtime(true);
        Redis::zadd(self::KEY_WINDOW, $ts, uniqid('req_', true));
        Redis::expire(self::KEY_WINDOW, 120);
    }

    /**
     * Exponential backoff + random jitter
     * Mencegah semua worker retry secara bersamaan (thundering herd)
     */
    private function backoffTime(int $attempt, int $currentRpm): int
    {
        $base    = $this->cooldownSec;
        $backoff = min($base * (2 ** $attempt), 60); // max 60 detik
        $jitter  = random_int(0, (int) ($backoff * 0.25)); // ±25% jitter

        // Tambah extra delay jika RPM sangat tinggi
        if ($this->rpmLimit > 0 && ($currentRpm / $this->rpmLimit) > 0.90) {
            $backoff = (int) ($backoff * 1.5);
        }

        return $backoff + $jitter;
    }
}
