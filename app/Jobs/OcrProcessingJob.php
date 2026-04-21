<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════
 *  OcrProcessingJob — FINAL FIX v2
 *
 *  ✅ FIX: upload_id dikirim di 4 tempat:
 *     1. URL query params
 *     2. HTTP Headers (X-Upload-ID)
 *     3. Filename (upload_id.jpg)
 *     4. Multipart form fields
 *
 *  ✅ FIX: Gunakan ->asMultipart() untuk memastikan form fields terkirim
 *  ✅ FIX: Update ai_status di DB (bukan hanya cache)
 *  ✅ FIX: Broadcast saat status berubah queued → processing
 * ═══════════════════════════════════════════════════════════════
 */
class OcrProcessingJob implements ShouldQueue, ShouldBeUnique
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

    /**
     * Pastikan satu upload_id hanya diproses satu kali dalam satu waktu.
     */
    public function uniqueId(): string
    {
        return (string) $this->uploadId;
    }

    public function handle()
    {
        Log::channel('ocr')->info('🔄 [OCR JOB] JOB STARTED', [
            'upload_id' => $this->uploadId,
            'file_path' => $this->filePath,
            'priority'  => $this->priority,
            'transaksi_id' => $this->transaksiId,
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

            // ── ✅ NEW: Selalu optimalkan gambar untuk OCR sebelum kirim ke n8n ──
            $compressionService = app(\App\Services\ImageCompressionService::class);
            Log::channel('ocr')->info('🗜️ [OCR JOB] OPTIMIZING IMAGE FOR N8N', [
                'upload_id'        => $this->uploadId,
                'original_size_kb' => round(filesize($fullPath) / 1024, 2),
            ]);
            $fullPath = $compressionService->optimizeForOcr($fullPath);
            Log::channel('ocr')->info('✅ [OCR JOB] OPTIMIZATION DONE', [
                'upload_id'      => $this->uploadId,
                'final_size_kb'  => round(filesize($fullPath) / 1024, 2),
            ]);

            Log::channel('ocr')->info('📤 [OCR JOB] SENDING TO N8N', [
                'upload_id'    => $this->uploadId,
                'transaksi_id' => $this->transaksiId,
                'full_path'    => $fullPath,
                'file_size_kb' => round(filesize($fullPath) / 1024, 2),
                'n8n_webhook'  => config('services.n8n.webhook_url'),
            ]);

            $secret = config('services.n8n.secret');

            // ✅ FIX: Send upload_id in FOUR places for maximum reliability:
            // 1. URL query params (most reliable for n8n webhook node)
            // 2. HTTP Headers (fallback #1)
            // 3. Filename (fallback #2)
            // 4. Multipart form fields (fallback #3)
            
            $callbackUrl = url('/api/ai/auto-fill')
                . '?upload_id=' . urlencode($this->uploadId)
                . '&transaksi_id=' . urlencode((string) $this->transaksiId);

            $n8nUrl = config('services.n8n.webhook_url') . '/webhook/upload-nota'
                . '?upload_id=' . urlencode($this->uploadId)
                . '&transaksi_id=' . urlencode((string) $this->transaksiId);

            // ✅ NEW: Embed upload_id in filename as additional fallback
            $originalName = basename($fullPath);
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $filenameWithId = "{$this->uploadId}.{$extension}";

            $response = Http::timeout(120)
                ->withHeaders([
                    'X-SECRET'       => $secret,
                    'X-Upload-ID'    => $this->uploadId,      // ✅ Header 1
                    'X-Transaksi-ID' => (string) $this->transaksiId, // ✅ Header 2
                ])
                ->attach(
                    'data',
                    file_get_contents($fullPath),
                    $filenameWithId  // ✅ NEW: filename contains upload_id
                )
                ->asMultipart()  // ✅ NEW: Explicitly set multipart mode
                ->post($n8nUrl, [
                    // ✅ These will be sent as multipart form fields
                    'upload_id'    => $this->uploadId,
                    'transaksi_id' => (string) $this->transaksiId,
                    'priority'     => $this->priority,
                    'secret'       => $secret,
                    'callback_url' => $callbackUrl,
                ]);

            Log::channel('ocr')->info('📥 [OCR JOB] N8N RESPONSE', [
                'upload_id'    => $this->uploadId,
                'transaksi_id' => $this->transaksiId,
                'status_code'  => $response->status(),
                'headers'      => $response->headers(),
                'body_preview' => substr($response->body(), 0, 1000),
                'success'      => $response->successful(),
            ]);

            // ✅ IMPROVED ERROR HANDLING: If n8n returns an error, update status and broadcast immediately
            if (!$response->successful()) {
                Log::channel('ocr')->error('❌ [OCR JOB] N8N RETURNED ERROR', [
                    'upload_id'   => $this->uploadId,
                    'status_code' => $response->status(),
                    'body'        => $response->body(),
                ]);

                $body = $response->json() ?? [];
                
                // Decide on error message
                $errorMessage = 'Gagal memproses nota (HTTP ' . $response->status() . ')';
                if ($response->status() === 404) $errorMessage = 'Layanan OCR n8n tidak ditemukan (404).';
                if ($response->status() === 500) $errorMessage = 'Layanan OCR n8n sedang bermasalah (500).';
                if (isset($body['error'])) $errorMessage = $body['error'];

                Cache::put("ai_autofill:{$this->uploadId}", [
                    'status'  => 'error',
                    'message' => $errorMessage,
                ], now()->addMinutes(30));

                $transaction = \App\Models\Transaction::where('upload_id', $this->uploadId)->first();
                if ($transaction) {
                    $transaction->update(['ai_status' => 'error']);
                    
                    // Broadcast the error so the loading screen can show the fallback
                    broadcast(new \App\Events\OcrStatusUpdated($transaction->submitted_by, [
                        'upload_id' => $this->uploadId,
                        'transaction_id' => $transaction->id,
                        'ai_status' => 'error',
                        'message' => $errorMessage,
                    ]));
                    
                    broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));
                }
                return;
            }

            // ✅ LOG SUCCESSFUL ACCEPTANCE
            $body = $response->json() ?? [];
            Log::channel('ocr')->info('✅ [OCR JOB] N8N ACCEPTED REQUEST', [
                'upload_id' => $this->uploadId,
                'transaksi_id' => $this->transaksiId,
                'response_data' => $body,
                'next_step' => 'Waiting for n8n callback to /api/ai/auto-fill',
                'sent_via' => [
                    'query_params' => true,
                    'headers' => true,
                    'filename' => $filenameWithId,
                    'form_fields' => true,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::channel('ocr')->error('❌ [OCR JOB] EXCEPTION', [
                'upload_id' => $this->uploadId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
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