<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════
 *  OcrProcessingJob — FULL FIX
 *
 *  ✅ FIX: Update ai_status di DB (bukan hanya cache)
 *          Frontend baca ai_status dari search-data (DB), bukan cache.
 *  ✅ FIX: Broadcast saat status berubah queued → processing
 *  ✅ FIX: Revert ai_status ke 'queued' saat kena 429 (re-queue)
 * ═══════════════════════════════════════════════════════════════
 */
class OcrProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uploadId;
    public $filePath;
    public $priority;
    public $transaksiId;

    public function __construct($uploadId, $filePath, $priority = 'normal', $transaksiId = null)
    {
        $this->uploadId    = $uploadId;
        $this->filePath    = $filePath;
        $this->priority    = $priority;
        // Resolve transaksi_id dari DB jika tidak diberikan
        $this->transaksiId = $transaksiId ?? optional(
            \App\Models\Transaction::where('upload_id', $uploadId)->first()
        )->id;
    }

    public function handle()
    {
        Log::channel('ocr')->info('🔄 [OCR JOB] JOB STARTED', [
            'upload_id' => $this->uploadId,
            'file_path' => $this->filePath,
            'priority'  => $this->priority,
            'job_id'    => $this->job?->getJobId(),
            'queue'     => $this->job?->getQueue(),
        ]);

        $rateLimiter = app(\App\Services\OCR\GeminiRateLimiter::class);

        try {
            $rateLimiter->acquireSlot($this->uploadId);
        } catch (\RuntimeException $e) {
            Log::channel('ocr')->warning('⏳ [OCR JOB] RATE LIMITER TIMEOUT / QUEUE FULL, RELEASING JOB', [
                'upload_id' => $this->uploadId,
                'error'     => $e->getMessage(),
            ]);
            $this->release(30);
            return;
        }

        try {
            // ── ✅ FIX: Update BOTH cache AND DB ai_status to 'processing' ──
            // Frontend reads ai_status from the DB (via /transactions/search-data), not cache.
            // Without this, the AI badge stays stuck on 'queued'.
            Cache::put("ai_autofill:{$this->uploadId}", [
                'status' => 'processing',
                'phase'  => 'processing',
            ], now()->addMinutes(30));

            \App\Models\Transaction::where('upload_id', $this->uploadId)
                ->update(['ai_status' => 'processing']);

            // ── ✅ FIX: Broadcast so frontend auto-refreshes badge queued → processing ──
            $transaction = \App\Models\Transaction::where('upload_id', $this->uploadId)->first();
            if ($transaction) {
                broadcast(new \App\Events\TransactionUpdated($transaction));
            }

            Log::channel('ocr')->info('🔄 [OCR JOB] STATUS UPDATED TO PROCESSING', [
                'upload_id'      => $this->uploadId,
                'transaction_id' => $transaction->id ?? null,
            ]);

            // ── Resolve file path (absolute or relative) ──
            if (str_starts_with($this->filePath, '/')) {
                $fullPath = $this->filePath;
            } else {
                $fullPath = storage_path('app/public/' . $this->filePath);
            }

            Log::channel('ocr')->info('📂 [OCR JOB] RESOLVED PATH', [
                'upload_id'  => $this->uploadId,
                'input_path' => $this->filePath,
                'full_path'  => $fullPath,
                'exists'     => file_exists($fullPath),
            ]);

            if (!file_exists($fullPath)) {
                Log::channel('ocr')->error('❌ [OCR JOB] FILE NOT FOUND', [
                    'upload_id' => $this->uploadId,
                    'full_path' => $fullPath,
                ]);

                Cache::put("ai_autofill:{$this->uploadId}", [
                    'status'  => 'error',
                    'message' => 'File nota tidak ditemukan di server. Silakan isi data secara manual.',
                ], now()->addMinutes(30));

                \App\Models\Transaction::where('upload_id', $this->uploadId)
                    ->update(['ai_status' => 'error']);

                return;
            }

            Log::channel('ocr')->info('📤 [OCR JOB] SENDING TO N8N', [
                'upload_id'    => $this->uploadId,
                'full_path'    => $fullPath,
                'file_size_kb' => round(filesize($fullPath) / 1024, 2),
                'n8n_webhook'  => config('services.n8n.webhook_url'),
            ]);

            // field name 'data' matches n8n binaryPropertyName: "data"
            // ✅ FIX: Sertakan transaksi_id agar N8N bisa kirim kembali ke callback
            $response = Http::timeout(120)
                ->attach(
                    'data',
                    file_get_contents($fullPath),
                    basename($fullPath)
                )
                ->post(config('services.n8n.webhook_url') . '/webhook/upload-nota', [
                    'upload_id'    => $this->uploadId,
                    'transaksi_id' => (string) $this->transaksiId,
                    'priority'     => $this->priority,
                    'secret'       => config('services.n8n.secret'),
                ]);

            Log::channel('ocr')->info('📥 [OCR JOB] N8N RESPONSE', [
                'upload_id'    => $this->uploadId,
                'status_code'  => $response->status(),
                'headers'      => $response->headers(),
                'body_preview' => substr($response->body(), 0, 1000),
                'success'      => $response->successful(),
            ]);

            // ✅ ADD: Log kalau response sukses tapi bukan expected format
            if ($response->successful()) {
                $body = $response->json() ?? [];
                
                Log::channel('ocr')->info('✅ [OCR JOB] N8N ACCEPTED REQUEST', [
                    'upload_id' => $this->uploadId,
                    'response_data' => $body,
                    'next_step' => 'Waiting for n8n callback to /api/ai/auto-fill',
                ]);
            }

            // ── Handle 429 (Too Many Requests) ──
            if ($response->status() === 429) {
                $retryAfterHeader = $response->header('Retry-After');
                $retryAfter = $retryAfterHeader ? (int) $retryAfterHeader : 60;
                $rateLimiter->register429($retryAfter);

                Log::channel('ocr')->warning('⚠️ [OCR JOB] 429 TOO MANY REQUESTS, RELEASING JOB', [
                    'upload_id'   => $this->uploadId,
                    'retry_after' => $retryAfter,
                ]); 

                // ── ✅ FIX: Revert ai_status back to 'queued' when re-queuing ──
                \App\Models\Transaction::where('upload_id', $this->uploadId)
                    ->update(['ai_status' => 'queued']);

                $this->release($retryAfter);
                return;
            }

            if (!$response->successful()) {
                Log::channel('ocr')->error('❌ [OCR JOB] N8N RETURNED ERROR', [
                    'upload_id'   => $this->uploadId,
                    'status_code' => $response->status(),
                    'body'        => $response->body(),
                ]);

                $body = $response->json() ?? [];
                if (empty($body) || (isset($body['valid']) && $body['valid'] === false)) {
                    Cache::put("ai_autofill:{$this->uploadId}", [
                        'status'  => 'error',
                        'message' => 'Gagal memproses nota: ' . ($body['error'] ?? 'Unknown or connection error (' . $response->status() . ')'),
                    ], now()->addMinutes(30));

                    \App\Models\Transaction::where('upload_id', $this->uploadId)
                        ->update(['ai_status' => 'error']);
                }
            }

        } catch (\Throwable $e) {
            Log::channel('ocr')->error('❌ [OCR JOB] EXCEPTION', [
                'upload_id' => $this->uploadId,
                'error'     => $e->getMessage(),
            ]);

            Cache::put("ai_autofill:{$this->uploadId}", [
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem. Silakan isi data secara manual.',
            ], now()->addMinutes(30));

            \App\Models\Transaction::where('upload_id', $this->uploadId)
                ->update(['ai_status' => 'error']);

            throw $e;
        } finally {
            $rateLimiter->releaseSlot($this->uploadId);
        }
    }
}