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

class OcrProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uploadId;
    public $filePath;
    public $priority;

    public function __construct($uploadId, $filePath, $priority = 'normal')
    {
        $this->uploadId = $uploadId;
        $this->filePath = $filePath;
        $this->priority = $priority;
        $this->onQueue('ocr_' . $priority);
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
            // Tunggu dapat slot (bisa blocking sleep dlm fungsi ini sampai 5 menit)
            $rateLimiter->acquireSlot($this->uploadId);
        } catch (\RuntimeException $e) {
            Log::channel('ocr')->warning('⏳ [OCR JOB] RATE LIMITER TIMEOUT / QUEUE FULL, RELEASING JOB', [
                'upload_id' => $this->uploadId,
                'error'     => $e->getMessage()
            ]);
            // Re-queue ulang 30 detik kemudian secara halus, bukan mark failed
            $this->release(30);
            return;
        }

        try {
            // Set cache ke "processing" agar UI tidak stuck di "queued"
            Cache::put("ai_autofill:{$this->uploadId}", [
                'status' => 'processing',
                'phase'  => 'processing',
            ], now()->addMinutes(30));

            // =========================================================
            // ✅ FIX FINAL - Handle BOTH absolute dan relative path
            // =========================================================
            if (str_starts_with($this->filePath, '/')) {
                // Absolute path — langsung pakai
                $fullPath = $this->filePath;
            } else {
                // Relative path — tambahkan storage prefix
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

            // ✅ field name 'data' agar cocok dengan n8n binaryPropertyName: "data"
            $response = Http::timeout(120)
                ->attach(
                    'data',
                    file_get_contents($fullPath),
                    basename($fullPath)
                )
                ->post(config('services.n8n.webhook_url'), [
                    'upload_id' => $this->uploadId,
                    'priority'  => $this->priority,
                    'secret'    => config('services.n8n.secret'),
                ]);

            Log::channel('ocr')->info('📥 [OCR JOB] N8N RESPONSE', [
                'upload_id'    => $this->uploadId,
                'status_code'  => $response->status(),
                'body_preview' => substr($response->body(), 0, 500),
            ]);

            // ✨ TANGANI RATE LIMIT (429) ✨
            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 60);
                $rateLimiter->register429($retryAfter);
                
                Log::channel('ocr')->warning('⚠️ [OCR JOB] 429 TOO MANY REQUESTS, RELEASING JOB', [
                    'upload_id'   => $this->uploadId,
                    'retry_after' => $retryAfter,
                ]);

                // Re-queue job supaya worker mencoba lagi nanti tanpa gagal di DB
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
                if (isset($body['valid']) && $body['valid'] === false) {
                    Cache::put("ai_autofill:{$this->uploadId}", [
                        'status'  => 'error',
                        'message' => 'Gagal memproses nota: ' . ($body['error'] ?? 'Unknown error'),
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
            // Pastikan slot Redis dibebaskan bila job selesai, gagal (kecuali di-release sebelum blok try ini), 
            // agar request dari worker/job lain dapat masuk.
            $rateLimiter->releaseSlot($this->uploadId);
        }
    }
}