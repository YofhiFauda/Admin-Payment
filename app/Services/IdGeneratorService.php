<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * IdGeneratorService
 *
 * Generates sequential, daily-reset IDs using a SINGLE shared Redis counter.
 *
 * KEY DESIGN: upload_id and invoice_number always share the same sequence number.
 *   - nextSequence()          → returns int N  (increments shared Redis counter once)
 *   - buildUploadId(N)        → 'UP-YYYYMMDD-NNNNN'
 *   - buildInvoiceNumber(N)   → 'INV-YYYYMMDD-NNNNN'
 *
 * Usage in controllers:
 *   $seq           = IdGeneratorService::nextSequence();
 *   $uploadId      = IdGeneratorService::buildUploadId($seq);
 *   $invoiceNumber = IdGeneratorService::buildInvoiceNumber($seq);
 *   // → UP-20260302-00006 and INV-20260302-00006  ← always the same number!
 *
 * Anti-race condition: Redis INCR is atomic — safe across all Docker/Horizon workers.
 * Daily reset: key set to EXPIREAT midnight so counter restarts at 00001 next day.
 * Fallback: if Redis is down, random suffix is used to avoid crash.
 */
class IdGeneratorService
{
    /**
     * Get the next sequence number for today.
     * This is the ONLY method that touches Redis — call it once per transaction.
     */
    public static function nextSequence(): int
    {
        try {
            $date = now()->format('Ymd');
            $key  = "id_seq:daily:{$date}";     // single shared key for all IDs

            $seq = Redis::incr($key);            // ATOMIC: guaranteed unique

            if ($seq === 1) {
                // First use today — expire at midnight so counter resets tomorrow
                Redis::expireAt($key, strtotime('tomorrow midnight'));
            }

            return (int) $seq;

        } catch (\Exception $e) {
            Log::warning('[IdGenerator] Redis unavailable, using random fallback.', [
                'error' => $e->getMessage(),
            ]);
            // Fallback: large random number won't collide with normal sequential counters
            return rand(90000, 99999);
        }
    }

    /**
     * Build an upload_id string from a sequence number.
     * Format: UP-YYYYMMDD-XXXXX (e.g. UP-20260302-00006)
     */
    public static function buildUploadId(int $seq, ?string $date = null): string
    {
        $date = $date ?? now()->format('Ymd');
        return 'UP-' . $date . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Build an invoice_number string from a sequence number.
     * Format: INV-YYYYMMDD-XXXXX (e.g. INV-20260302-00006)
     */
    public static function buildInvoiceNumber(int $seq, ?string $date = null): string
    {
        $date = $date ?? now()->format('Ymd');
        return 'INV-' . $date . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique trace ID.
     * Format: TRX-XXXXXXXX (e.g. TRX-8DK29XQZ)
     */
    public static function nextTraceId(): string
    {
        return 'TRX-' . strtoupper(Str::random(8));
    }

    // ─── Convenience one-liners (backward compat, each uses a separate counter tick) ───

    public static function nextInvoiceNumber(): string
    {
        return self::buildInvoiceNumber(self::nextSequence());
    }

    public static function nextUploadId(): string
    {
        return self::buildUploadId(self::nextSequence());
    }

    // ─── Debug helpers ─────────────────────────────────────────────────────────

    public static function peekCounter(?string $date = null): int
    {
        $date = $date ?? now()->format('Ymd');
        try {
            return (int) (Redis::get("id_seq:daily:{$date}") ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
