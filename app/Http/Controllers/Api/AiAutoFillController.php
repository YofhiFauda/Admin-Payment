<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OCR\GeminiRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Notifications\OcrStatusNotification;
use App\Models\User;



/**
 * ═══════════════════════════════════════════════════════════════
 *  AiAutoFillController — Diperbaiki
*
*  ✅ store()  : Callback dari n8n (tidak banyak berubah)
*  ✅ status() : Polling dari loading page (tidak berubah)
*  ✅ ocrStatus(): Admin monitoring endpoint (BARU)
* ═══════════════════════════════════════════════════════════════
*/
class AiAutoFillController extends Controller
{
    /**
     * ─────────────────────────────────────────────────────────
     *  POST /api/ai/auto-fill
     *  Callback dari n8n setelah Gemini selesai OCR
     *  (Tidak banyak berubah dari versi asli)
     * ─────────────────────────────────────────────────────────
     */
    public function store(Request $request)
    {
        $this->logCallbackReceived($request);

        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $uploadId = $this->resolveUploadId($request);
        if (!$uploadId) {
            return response()->json(['message' => 'upload_id is required'], 422);
        }

        if ($request->boolean('ocr_failed')) {
            return $this->handleOcrFailed($request, $uploadId);
        }

        $validator = Validator::make($request->all(), [
            'customer'                   => 'nullable|string|max:255',
            'amount'                     => 'nullable|numeric',
            'date'                       => 'nullable|date',
            'items'                      => 'nullable|array',
            'items.*.nama_barang'        => 'nullable|string',
            'items.*.qty'                => 'nullable|numeric',
            'items.*.satuan'             => 'nullable|string',
            'items.*.harga_satuan'       => 'nullable|numeric',
            'items.*.total_harga'        => 'nullable|numeric',
            'confidence'                 => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            Log::channel('ai_autofill')->error('❌ [AI CALLBACK] VALIDATION FAILED', [
                'step' => '5_validation_error',
                'upload_id' => $uploadId,
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json($validator->errors(), 422);
        }

        $cacheData = $this->prepareCacheData($request, $uploadId);
        Cache::put("ai_autofill:{$uploadId}", $cacheData, now()->addMinutes(30));

        $this->logCacheStored($uploadId, $cacheData);
        $this->updateTransactionOnSuccess($uploadId, $cacheData);

        Log::channel('ai_autofill')->info('✅ [AI CALLBACK] CALLBACK COMPLETE', [
            'step' => '5_complete',
            'upload_id' => $uploadId,
            'success' => true,
        ]);

        return response()->json(['success' => true]);
    }

    private function logCallbackReceived(Request $request): void
    {
        Log::channel('ai_autofill')->info('📥 [AI CALLBACK] RECEIVED FROM N8N', [
            'step' => '5_callback_received',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function isAuthorized(Request $request): bool
    {
        if ($request->header('X-SECRET') !== config('services.n8n.secret')) {
            Log::channel('ai_autofill')->warning('🔒 [AI CALLBACK] UNAUTHORIZED', [
                'step' => '5_unauthorized',
                'ip' => $request->ip(),
                'provided_secret' => substr($request->header('X-SECRET') ?? '', 0, 10) . '...',
            ]);
            return false;
        }
        return true;
    }

    private function resolveUploadId(Request $request): ?string
    {
        $queryId  = $request->query('upload_id');
        $headerId = $request->header('X-Upload-ID');
        $bodyId   = $request->upload_id;

        $isValidId = function ($id) {
            return $id && is_string($id) && !str_contains($id, '{{') && !str_contains($id, '}}');
        };

        $uploadId = null;
        $idSource = null;

        if ($isValidId($queryId)) {
            $uploadId = $queryId;
            $idSource = 'query';
        } elseif ($isValidId($headerId)) {
            $uploadId = $headerId;
            $idSource = 'header';
        } elseif ($isValidId($bodyId)) {
            $uploadId = $bodyId;
            $idSource = 'body';
        }

        Log::channel('ai_autofill')->info('🔍 [AI CALLBACK] UPLOAD ID RESOLVED', [
            'step' => '5_upload_id',
            'upload_id' => $uploadId,
            'id_source' => $idSource,
            'query_id' => $queryId,
            'header_id' => $headerId,
            'body_id' => $bodyId,
            'confidence' => $request->confidence,
        ]);

        if (!$uploadId) {
            Log::channel('ai_autofill')->error('❌ [AI CALLBACK] MISSING UPLOAD ID', [
                'step' => '5_missing_id',
                'query' => $queryId,
                'header' => $headerId,
                'body' => $bodyId,
            ]);
        }

        return $uploadId;
    }

    private function handleOcrFailed(Request $request, string $uploadId)
    {
        Log::channel('ai_autofill')->warning('⚠️ [AI CALLBACK] OCR FAILED', [
            'step' => '5_ocr_failed',
            'upload_id' => $uploadId,
            'confidence' => $request->confidence,
            'message' => $request->message ?? null,
        ]);

        Cache::put("ai_autofill:{$uploadId}", [
            'status' => 'error',
            'message' => 'AI tidak dapat membaca nota dengan jelas (confidence: ' . $request->confidence . '%). Silakan isi manual.',
        ], now()->addMinutes(30));

        $transaction = \App\Models\Transaction::where('upload_id', $uploadId)->first();
        if ($transaction) {
            $transaction->update(['ai_status' => 'error', 'confidence' => $request->confidence]);

            // Broadcast to global transactions channel so grid auto-refreshes for all viewers
            broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));

            Log::channel('ai_autofill')->info('🔄 [AI CALLBACK] TRANSACTION UPDATED (ERROR)', [
                'step' => '5_transaction_error',
                'upload_id' => $uploadId,
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'ai_status' => 'error',
            ]);

            $submitter = User::find($transaction->submitted_by);
            if ($submitter) {
                $submitter->notify(new OcrStatusNotification(
                    transaction: $transaction,
                    aiStatus: 'error',
                    confidence: $request->confidence,
                ));
                
                // Trigger WebSocket event
                broadcast(new \App\Events\OcrStatusUpdated($submitter->id, [
                    'transaction_id' => $transaction->id,
                    'invoice_number' => $transaction->invoice_number,
                    'ai_status' => 'error',
                    'message' => 'AI tidak dapat membaca nota dengan jelas (confidence: ' . $request->confidence . '%). Silakan isi manual.',
                ]));

                Log::channel('ai_autofill')->info('📬 [AI CALLBACK] NOTIFICATION SENT (ERROR)', [
                    'step' => '5_notification_error',
                    'upload_id' => $uploadId,
                    'transaction_id' => $transaction->id,
                    'user_id' => $submitter->id,
                    'user_name' => $submitter->name,
                ]);
            }
        }

        return response()->json(['success' => true, 'status' => 'failed']);
    }

    private function prepareCacheData(Request $request, string $uploadId): array
    {
        $date = null;
        if ($request->date) {
            try {
                $date = Carbon::parse($request->date)->format('Y-m-d');
            } catch (\Exception $e) {
                $date = null;
            }
        }

        $items = [];
        if ($request->items && is_array($request->items)) {
            foreach ($request->items as $item) {
                $items[] = [
                    'nama_barang'       => $item['nama_barang'] ?? $item['name'] ?? '',
                    'name'              => $item['nama_barang'] ?? $item['name'] ?? '',
                    'qty'               => $item['qty'] ?? 1,
                    'satuan'            => $item['satuan'] ?? $item['unit'] ?? 'pcs',
                    'unit'              => $item['satuan'] ?? $item['unit'] ?? 'pcs',
                    'harga_satuan'      => $item['harga_satuan'] ?? $item['price'] ?? 0,
                    'price'             => $item['harga_satuan'] ?? $item['price'] ?? 0,
                    'total_harga'       => $item['total_harga'] ?? 0,
                    'deskripsi_kalimat' => $item['deskripsi_kalimat'] ?? $item['desc'] ?? '',
                    'desc'              => $item['deskripsi_kalimat'] ?? $item['desc'] ?? '',
                ];
            }
        }

        Log::channel('ai_autofill')->info('📦 [AI CALLBACK] ITEMS NORMALIZED', [
            'step' => '5_items',
            'upload_id' => $uploadId,
            'items_count' => count($items),
            'total_amount' => $request->total_belanja ?? $request->amount ?? 0,
        ]);

        return [
            'status'        => 'completed',
            'upload_id'     => $uploadId,
            'customer'      => $request->nama_toko ?? $request->customer ?? '',
            'nama_toko'     => $request->nama_toko ?? $request->customer ?? '',
            'amount'        => $request->total_belanja ?? $request->amount ?? 0,
            'total_belanja' => $request->total_belanja ?? $request->amount ?? 0,
            'date'          => $date,
            'tanggal'       => $date,
            'items'         => $items,
            'confidence'    => $request->confidence,
        ];
    }

    private function logCacheStored(string $uploadId, array $cacheData): void
    {
        Log::channel('ai_autofill')->info('💾 [AI CALLBACK] CACHE STORED', [
            'step' => '5_cache',
            'upload_id' => $uploadId,
            'cache_key' => "ai_autofill:{$uploadId}",
            'ttl_minutes' => 30,
            'confidence' => $cacheData['confidence'],
        ]);
    }

    private function updateTransactionOnSuccess(string $uploadId, array $cacheData): void
    {
        $transaction = \App\Models\Transaction::where('upload_id', $uploadId)->first();
        if ($transaction) {
            $transaction->update([
                'customer'   => $cacheData['customer'],
                'amount'     => $cacheData['amount'],
                'items'      => $cacheData['items'],
                'date'       => $cacheData['date'],
                'ai_status'  => 'completed',
                'confidence' => $cacheData['confidence'],
            ]);

            // Broadcast to global transactions channel so grid auto-refreshes for all viewers
            broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));

            Log::channel('ai_autofill')->info('🔄 [AI CALLBACK] TRANSACTION UPDATED (SUCCESS)', [
                'step' => '5_transaction_success',
                'upload_id' => $uploadId,
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'ai_status' => 'completed',
                'confidence' => $cacheData['confidence'],
                'amount' => $cacheData['amount'],
                'items_count' => count($cacheData['items'] ?? []),
            ]);

            $submitter = User::find($transaction->submitted_by);

            if ($submitter) {
                $submitter->notify(new OcrStatusNotification(
                    transaction: $transaction,
                    aiStatus: 'completed',
                    confidence: $cacheData['confidence'],
                ));
                
                // Trigger WebSocket event
                broadcast(new \App\Events\OcrStatusUpdated($submitter->id, [
                    'transaction_id' => $transaction->id,
                    'invoice_number' => $transaction->invoice_number,
                    'ai_status' => 'completed',
                    'message' => 'Auto-fill AI selesai (Confidence: ' . $cacheData['confidence'] . '%).',
                ]));

                Log::channel('ai_autofill')->info('📬 [AI CALLBACK] NOTIFICATION SENT (SUCCESS)', [
                    'step' => '5_notification_success',
                    'upload_id' => $uploadId,
                    'transaction_id' => $transaction->id,
                    'user_id' => $submitter->id,
                    'user_name' => $submitter->name,
                    'notification_type' => 'ocr_status',
                ]);
            }
        }
    }

    /**
     * ─────────────────────────────────────────────────────────
     *  GET /api/ai/auto-fill/status/{uploadId}
     *  Polling dari loading.blade.php (tidak berubah)
     * ─────────────────────────────────────────────────────────
     */
    public function status($uploadId)
    {
        $cacheKey = "ai_autofill:{$uploadId}";
        $data = Cache::get($cacheKey);
        
        Log::channel('ai_autofill')->debug('🔍 [AI POLL] STATUS POLL REQUEST', [
            'upload_id' => $uploadId,
            'cache_found' => $data !== null,
            'cache_status' => $data['status'] ?? 'not_found',
        ]);

        // ✅ Handle error state
        if (($data['status'] ?? '') === 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $data['message'] ?? 'Terjadi kesalahan pada proses AI.',
            ]);
        }

        // ✅ Handle completed state - return full data + confidence
        if (($data['status'] ?? '') === 'completed') {
            return response()->json([
                'status' => 'completed',
                'data' => [
                    'customer'      => $data['customer'] ?? null,
                    'amount'        => $data['amount'] ?? null,
                    'date'          => $data['date'] ?? null,
                    'items'         => $data['items'] ?? [],
                    'confidence'    => $data['confidence'] ?? null, // ✅ Penting untuk badge
                    'total_items'   => count($data['items'] ?? []),
                ],
            ]);
        }

        // ✅ Handle queued/pending/processing states
        $phase = $data['phase'] ?? 'queued';
        $status = in_array($phase, ['queued', 'pending', 'processing']) ? $phase : 'processing';
        
        return response()->json([
            'status' => $status,
            'phase' => $phase,
            'message' => match($phase) {
                'queued' => 'Menunggu dalam antrian...',
                'pending' => 'Menunggu file terupload...',
                'processing' => 'Sedang memproses dengan AI...',
                default => 'Memproses...',
            },
            'estimated_wait' => $phase === 'queued' ? 30 : ($phase === 'processing' ? 15 : null), // detik
        ]);
    }

    /**
     * ─────────────────────────────────────────────────────────
     *  ✅ BARU: GET /api/admin/ocr-status
     *  Admin monitoring: rate limiter + queue stats
     * ─────────────────────────────────────────────────────────
     */
    public function ocrStatus(GeminiRateLimiter $rateLimiter)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'owner'])) {
            // 📝 LOG: OCR Status - Forbidden
            Log::channel('ai_autofill')->warning('🔒 [AI ADMIN] OCR STATUS FORBIDDEN', [
                'step' => '7_admin',
                'user_id' => auth()->id(),
                'user_role' => auth()->user()->role ?? 'guest',
            ]);
            return response()->json(['message' => 'Forbidden'], 403);
        }


        // 📝 LOG: OCR Status - Admin Access
        Log::channel('ai_autofill')->info('👤 [AI ADMIN] OCR STATUS ACCESSED', [
            'step' => '7_admin',
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'user_role' => auth()->user()->role,
        ]);

        return response()->json([
            'rate_limiter' => $rateLimiter->getStatus(),
            'queue_stats' => [
                'ocr_high' => \Illuminate\Support\Facades\Redis::llen('queues:ocr_high'),
                'ocr_normal' => \Illuminate\Support\Facades\Redis::llen('queues:ocr_normal'),
                'ocr_low' => \Illuminate\Support\Facades\Redis::llen('queues:ocr_low'),
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }
}
