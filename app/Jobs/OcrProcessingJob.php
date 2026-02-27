<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
    }

    public function handle()
    {
        // 📝 LOG: Job Started
        Log::channel('ocr')->info('🔄 [OCR JOB] JOB STARTED', [
            'step' => 'job_start',
            'upload_id' => $this->uploadId,
            'file_path' => $this->filePath,
            'priority' => $this->priority,
            'job_id' => $this->job->getJobId(),
            'queue' => $this->job->getQueue(),
        ]);

        try {
            // Check file exists
            if (!Storage::exists($this->filePath)) {
                // 📝 LOG: File Not Found
                Log::channel('ocr')->error('❌ [OCR JOB] FILE NOT FOUND', [
                    'step' => 'job_file_not_found',
                    'upload_id' => $this->uploadId,
                    'file_path' => $this->filePath,
                ]);
                return;
            }

            // 📝 LOG: File Found - Sending to n8n
            Log::channel('ocr')->info('📤 [OCR JOB] SENDING TO N8N', [
                'step' => 'job_send_n8n',
                'upload_id' => $this->uploadId,
                'file_path' => $this->filePath,
                'n8n_webhook' => config('services.n8n.ocr_webhook'),
            ]);

            // Send to n8n webhook
            $response = Http::withHeaders([
                'X-SECRET' => config('services.n8n.secret'),
                'X-Upload-ID' => $this->uploadId,
                'Content-Type' => 'multipart/form-data',
            ])->attach('file', file_get_contents($this->filePath), basename($this->filePath))
            ->post(config('services.n8n.ocr_webhook'), [
                'upload_id' => $this->uploadId,
                'priority' => $this->priority,
            ]);

            // 📝 LOG: n8n Response
            Log::channel('ocr')->info('📥 [OCR JOB] N8N RESPONSE', [
                'step' => 'job_n8n_response',
                'upload_id' => $this->uploadId,
                'status_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                // 📝 LOG: Job Complete
                Log::channel('ocr')->info('✅ [OCR JOB] JOB COMPLETE', [
                    'step' => 'job_complete',
                    'upload_id' => $this->uploadId,
                    'n8n_status' => $response->status(),
                ]);
            } else {
                // 📝 LOG: Job Failed
                Log::channel('ocr')->error('❌ [OCR JOB] JOB FAILED', [
                    'step' => 'job_failed',
                    'upload_id' => $this->uploadId,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }

        } catch (\Exception $e) {
            // 📝 LOG: Job Exception
            Log::channel('ocr')->error('❌ [OCR JOB] JOB EXCEPTION', [
                'step' => 'job_exception',
                'upload_id' => $this->uploadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}