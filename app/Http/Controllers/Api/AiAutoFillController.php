<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OCR\GeminiRateLimiter;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Notifications\OcrStatusNotification;
use App\Models\User;
use App\Models\Transaction;
use App\Models\PaymentDiscrepancyAudit;
use App\Events\TransactionUpdated;
use App\Events\OcrStatusUpdated;
use Illuminate\Support\Facades\Redis;


/**
 * ═══════════════════════════════════════════════════════════════
 *  AiAutoFillController — FULL FIX
 *
 *  ✅ Bug #1: auto_reject & low_confidence dari n8n kini ditangani
 *  ✅ Bug #2: Semua status dinormalisasi ke key frontend
 *  ✅ Bug #5: handleOcrFailed membedakan auto-reject vs error biasa
 *            + duplikat auto-reject logic di updateTransactionOnSuccess dihapus
 * ═══════════════════════════════════════════════════════════════
 */
class AiAutoFillController extends Controller
{
    
    // ─────────────────────────────────────────────────────
    //  ✅ FIX Bug #2: Normalisasi status dari n8n/internal
    //  ke key yang dikenali frontend index.blade.php
    // ─────────────────────────────────────────────────────
    private function normalizeTransactionStatus(string $rawStatus): string
    {
        return match (strtolower(trim($rawStatus))) {
            'selesai', 'completed'                                    => 'completed',
            'flagged', 'flagged - selisih nominal',
            'flagged - manual verification required'                  => 'flagged',
            'menunggu pembayaran', 'waiting_payment'                  => 'waiting_payment',
            'auto-reject', 'auto reject', 'auto_reject'              => 'auto-reject',
            'pending', 'pending - menunggu approval admin'            => 'pending',
            'approved', 'disetujui', 'menunggu approve owner'        => 'approved',
            'rejected', 'ditolak'                                     => 'rejected',
            'menunggu konfirmasi teknisi'                             => 'Menunggu Konfirmasi Teknisi',
            'sedang diverifikasi ai'                                  => 'Sedang Diverifikasi AI',
            'ditolak teknisi'                                         => 'Ditolak Teknisi',
            default                                                   => $rawStatus,
        };
    }

    /**
     * AI Auto-Fill Callback (Primary)
     * 
     * Primary callback endpoint for n8n/Gemini to submit OCR extraction results.
     * 
     * @unauthenticated
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::channel('ai_autofill')->info('📥 [AI CALLBACK] RAW REQUEST BODY', [
            'body' => $request->all(),
            'query' => $request->query(),
            'headers' => $request->headers->all(),
        ]);
        $this->logCallbackReceived($request);
        $this->normalizeRequest($request);
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $uploadId = $this->resolveUploadId($request);
        // ✅ FIX: If still no upload_id, try to find transaction by other means
        if (!$uploadId) {
            // Last resort: find any transaction with ai_status='processing'
            $lastResortTx = Transaction::where('ai_status', 'processing')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->orderBy('created_at', 'desc')
                ->first();
            if ($lastResortTx) {
                $uploadId = $lastResortTx->upload_id;
                Log::channel('ai_autofill')->warning('⚠️ [AI CALLBACK] LAST RESORT UPLOAD ID', [
                    'upload_id' => $uploadId,
                    'transaction_id' => $lastResortTx->id,
                ]);
            }
        }
        if (!$uploadId) {
            return response()->json(['message' => 'upload_id is required'], 422);
        }

        // ── ✅ NEW: Penanggulangan Race Condition via Redis Lock ──
        $lock = Cache::lock("lock:ai_callback:{$uploadId}", 30); // Lock selama 30 detik
        
        try {
            if (!$lock->get()) {
                Log::channel('ai_autofill')->warning('🔒 [AI CALLBACK] DUPLICATE REQUEST BLOCKED (LOCKED)', [
                    'upload_id' => $uploadId
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Request is already being processed.'
                ], 202);
            }
            // ─── ✅ FIX Bug #1: Tangkap SEMUA status gagal dari n8n ───
            $failStatuses = ['failed', 'low_confidence', 'error', 'auto_reject'];
            if ($request->boolean('ocr_failed') || in_array($request->status, $failStatuses)) {
                $request->merge([
                    'message' => $request->reason ?? $request->message ?? 'OCR gagal',
                ]);
                return $this->handleOcrFailed($request, $uploadId);
            }
        // ─── ✅ FIX Bug #1: Validator menerima auto_reject & low_confidence ───
        $validator = Validator::make($request->all(), [
            'upload_id'              => 'nullable|string',
            'status'                 => 'nullable|string|in:success,failed,error,auto_reject,low_confidence',
            'vendor'                 => 'nullable|string|max:255',
            'nama_vendor'            => 'nullable|string|max:255',
            'customer'               => 'nullable|string|max:255',
            'nama_toko'              => 'nullable|string|max:255',
            'tanggal'                => 'nullable|string',
            'date'                   => 'nullable|date',
            'total_belanja'          => 'nullable|numeric',
            'amount'                 => 'nullable|numeric',
            'dpp_lainnya'            => 'nullable|numeric',
            'tax_amount'             => 'nullable|numeric',
            'items'                  => 'nullable|array',
            'confidence'             => 'nullable|integer|min:0|max:100',
            'overall_confidence'     => 'nullable|integer|min:0|max:100',
            'confidence_label'       => 'nullable|string|in:HIGH,MEDIUM,LOW',
            'field_confidence'       => 'nullable|array',
            'items.*.nama_barang'    => 'nullable|string',
            'items.*.qty'            => 'nullable|numeric',
            'items.*.satuan'         => 'nullable|string',
            'items.*.harga_satuan'   => 'nullable|numeric',
            'items.*.total_harga'    => 'nullable|numeric',
            'items.*.nama_barang_confidence' => 'nullable|integer|min:0|max:100',
            'items.*.qty_confidence'         => 'nullable|integer|min:0|max:100',
            'items.*.satuan_confidence'      => 'nullable|integer|min:0|max:100',
            'items.*.harga_satuan_confidence'=> 'nullable|integer|min:0|max:100',
            'items.*.total_harga_confidence' => 'nullable|integer|min:0|max:100',
        ]);
        if ($validator->fails()) {
            Log::channel('ai_autofill')->error('❌ [AI CALLBACK] VALIDATION FAILED', [
                'step'      => '5_validation_error',
                'upload_id' => $uploadId,
                'errors'    => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }
        $cacheData = $this->prepareCacheData($request, $uploadId);
        
        // ─── 🔍 FIND TRANSACTION (KODE 1 IMPLEMENTED) ───
        // Strategy 1: By upload_id
        $transaction = Transaction::where('upload_id', $uploadId)->first();

        if (!$transaction && $request->has('transaksi_id') && is_numeric($request->transaksi_id)) {
            $transaksiId = (int) $request->transaksi_id;
            $transaction = Transaction::find($transaksiId);
        }
        
        // ─── Check for duplicate/empty payload ───
        if ($transaction && $transaction->ai_status === 'completed' && $cacheData['confidence'] === 0 && empty($cacheData['items'])) {
            Log::channel('ai_autofill')->warning('⚠️ [AI CALLBACK] INCOMPLETE DUPLICATE PAYLOAD IGNORED', [
                'step'      => '5_duplicate_ignored',
                'upload_id' => $uploadId,
                'reason'    => 'Transaction already completed and new payload is empty',
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Ignored empty duplicate payload. Transaction already completed.',
                'upload_id' => $uploadId,
                'status'    => 'completed'
            ]);
        }
        
        Cache::put("ai_autofill:{$uploadId}", $cacheData, now()->addMinutes(30));
        $this->logCacheStored($uploadId, $cacheData);
        $this->updateTransactionOnSuccess($uploadId, $cacheData);
        
        // ✅ ADD: Log sebelum return response
        $finalResponse = [
            'success'          => true,
            'upload_id'        => $uploadId,
            'status'           => 'completed',
            'confidence'       => $cacheData['confidence'],
            'confidence_label' => $cacheData['confidence_label'],
        ];
        Log::channel('ai_autofill')->info('✅ [AI CALLBACK] SENDING FINAL RESPONSE', [
            'step'      => '5_final_response',
            'upload_id' => $uploadId,
            'response'  => $finalResponse,
            'timestamp' => now()->toIso8601String(),
        ]);
        Log::channel('ai_autofill')->info('✅ [AI CALLBACK] CALLBACK COMPLETE', [
            'step'             => '5_complete',
            'upload_id'        => $uploadId,
            'success'          => true,
            'confidence'       => $cacheData['confidence'],
            'confidence_label' => $cacheData['confidence_label'],
        ]);
        return response()->json([
            'success'          => true,
            'upload_id'        => $uploadId,
            'status'           => 'completed',
            'confidence'       => $cacheData['confidence'],
            'confidence_label' => $cacheData['confidence_label'],
        ]);
        } finally {
            $lock->release();
        }
    }
    
    /**
     * AI Auto-Fill Callback (Legacy)
     * 
     * Legacy callback endpoint (handles typos like /ai/auto-fil).
     * 
     * @unauthenticated
     */
    public function storeLegacy(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->store($request);
    }

        private function isAuthorized(Request $request): bool
        {
            $secret = config('services.n8n.secret') ?? env('N8N_SECRET');
            $providedSecret = $request->header('X-SECRET') ?? $request->input('secret');

            if ($providedSecret !== $secret) {
                Log::channel('ai_autofill')->warning('🔒 [AI CALLBACK] UNAUTHORIZED', [
                    'step'            => '5_unauthorized',
                    'ip'              => $request->ip(),
                    'provided_header' => substr($request->header('X-SECRET') ?? '', 0, 5) . '...',
                    'provided_body'   => substr($request->input('secret') ?? '', 0, 5) . '...',
                ]);
                return false;
            }
            return true;
        }

        /**
         * ─────────────────────────────────────────────────────────
         *  ✅ FIX: Improved upload_id resolution with multiple fallbacks
         *  ─────────────────────────────────────────────────────────
         */
        /**
         * ═══════════════════════════════════════════════════════════════
         *  resolveUploadId() — FINAL FIX v2
         *  
         *  Membaca upload_id dari callback n8n dengan 6 prioritas fallback:
         *  1. Body (paling reliable untuk n8n callback)
         *  2. Header X-Upload-ID
         *  3. Query params
         *  4. Resolve dari transaksi_id
         *  5. Fuzzy match (amount + date)
         *  6. Recent transaction dengan ai_status=processing
         * ═══════════════════════════════════════════════════════════════
         */
        private function resolveUploadId(Request $request): ?string
        {
            // ── Helper: Validasi upload_id ──
            $isValidId = function ($id) {
                return $id
                && is_string($id)
                && $id !== 'unknown'
                && $id !== 'null'
                && $id !== 'undefined'
                && !str_contains($id, '{{')
                && !str_contains($id, '}}')
                && strlen($id) >= 10; // Upload ID minimal 10 karakter
            };

            $uploadId = null;
            $idSource = null;

            // ── Extract dari berbagai sumber ──
            $bodyId   = $request->input('upload_id');
            $headerId = $request->header('X-Upload-ID');
            $queryId  = $request->query('upload_id');
            
            // ✅ PRIORITAS 1: Body (n8n mengirim callback via POST body)
            if ($isValidId($bodyId)) {
                $uploadId = $bodyId;
                $idSource = 'body';
            }
            // ✅ PRIORITAS 2: Header (X-Upload-ID dari job)
            elseif ($isValidId($headerId)) {
                $uploadId = $headerId;
                $idSource = 'header';
            }
            // ✅ PRIORITAS 3: Query string
            elseif ($isValidId($queryId)) {
                $uploadId = $queryId;
                $idSource = 'query';
            }

            // ── ✅ FALLBACK 1: Resolve dari transaksi_id ──
            if (!$uploadId) {
                $transaksiId = $request->input('transaksi_id') 
                    ?? $request->query('transaksi_id') 
                    ?? $request->header('X-Transaksi-ID');
                    
                if ($transaksiId && is_numeric($transaksiId)) {
                    $transaction = Transaction::find((int) $transaksiId);
                    if ($transaction && $transaction->upload_id) {
                        $uploadId = $transaction->upload_id;
                        $idSource = 'fallback_transaksi_id';
                        
                        Log::channel('ai_autofill')->info('🔄 [AI CALLBACK] UPLOAD ID RESOLVED VIA TRANSAKSI_ID', [
                            'transaksi_id' => $transaksiId,
                            'resolved_upload_id' => $uploadId,
                        ]);
                    }
                }
            }

            // ── ✅ FALLBACK 2: Fuzzy match by amount + date ──
            if (!$uploadId && $request->amount && $request->date) {
                $possibleTx = Transaction::where('amount', $request->amount)
                    ->where('date', $request->date)
                    ->whereIn('ai_status', ['processing', 'queued', 'pending'])
                    ->whereNull('customer') // Only match transactions without OCR data yet
                    ->latest()
                    ->first();
                
                if ($possibleTx) {
                    $uploadId = $possibleTx->upload_id;
                    $idSource = 'fallback_fuzzy_match';
                    
                    Log::channel('ai_autofill')->warning('⚠️ [AI CALLBACK] UPLOAD ID RESOLVED VIA FUZZY MATCH', [
                        'matched_upload_id' => $uploadId,
                        'amount' => $request->amount,
                        'date' => $request->date,
                    ]);
                }
            }

            // ── ✅ FALLBACK 3: Recent transaction dengan ai_status=processing ──
            if (!$uploadId) {
                $recentTx = Transaction::where('ai_status', 'processing')
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($recentTx) {
                    $uploadId = $recentTx->upload_id;
                    $idSource = 'fallback_recent_transaction';
                    
                    Log::channel('ai_autofill')->warning('⚠️ [AI CALLBACK] UPLOAD ID RESOLVED VIA RECENT TRANSACTION', [
                        'matched_upload_id' => $uploadId,
                        'transaction_id' => $recentTx->id,
                    ]);
                }
            }

            // ── Log resolution result ──
            Log::channel('ai_autofill')->info('🔍 [AI CALLBACK] UPLOAD ID RESOLUTION', [
                'step' => '5_upload_id',
                'upload_id' => $uploadId,
                'id_source' => $idSource,
                'sources' => [
                    'body' => $bodyId,
                    'header' => $headerId,
                    'query' => $queryId,
                ],
                'confidence' => $request->input('confidence'),
            ]);

            if (!$uploadId) {
                Log::channel('ai_autofill')->error('❌ [AI CALLBACK] MISSING UPLOAD ID AFTER ALL FALLBACKS', [
                    'step' => '5_missing_id',
                    'body_id' => $bodyId,
                    'header_id' => $headerId,
                    'query_id' => $queryId,
                    'transaksi_id' => $request->input('transaksi_id'),
                    'amount' => $request->input('amount'),
                    'date' => $request->input('date'),
                ]);
            }

            return $uploadId;
        }


        /**
         * Prepare cache data dari n8n callback
         */
        private function prepareCacheData(Request $request, string $uploadId): array
        {
            $date       = null;
            $tanggalRaw = $request->tanggal ?? $request->date;
            if ($tanggalRaw) {
                try {
                    // Handle DD/MM/YYYY
                    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $tanggalRaw, $matches)) {
                        $date = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                    } else {
                        $date = Carbon::parse($tanggalRaw)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    Log::channel('ai_autofill')->error('❌ [AI CALLBACK] DATE PARSE FAILED', [
                        'raw_tanggal' => $tanggalRaw,
                        'error'       => $e->getMessage(),
                    ]);
                    $date = null;
                }
            }

            $items = [];
            if ($request->items && is_array($request->items)) {
                foreach ($request->items as $item) {
                    $items[] = [
                        'nama_barang'  => $item['nama_barang'] ?? $item['name'] ?? '',
                        'name'         => $item['nama_barang'] ?? $item['name'] ?? '',
                        'qty'          => $item['qty'] ?? 1,
                        'satuan'       => $item['satuan'] ?? $item['unit'] ?? 'pcs',
                        'unit'         => $item['satuan'] ?? $item['unit'] ?? 'pcs',
                        'harga_satuan' => $item['harga_satuan'] ?? $item['price'] ?? 0,
                        'price'        => $item['harga_satuan'] ?? $item['price'] ?? 0,
                        'total_harga'  => $item['total_harga'] ?? 0,

                        'nama_barang_confidence'  => $item['nama_barang_confidence'] ?? $item['name_confidence'] ?? null,
                        'qty_confidence'          => $item['qty_confidence'] ?? null,
                        'satuan_confidence'       => $item['satuan_confidence'] ?? $item['unit_confidence'] ?? null,
                        'harga_satuan_confidence' => $item['harga_satuan_confidence'] ?? $item['price_confidence'] ?? null,
                        'total_harga_confidence'  => $item['total_harga_confidence'] ?? null,

                        'deskripsi_kalimat' => $item['deskripsi_kalimat'] ?? $item['desc'] ?? '',
                        'desc'              => $item['deskripsi_kalimat'] ?? $item['desc'] ?? '',
                    ];
                }
            }

            $vendor = $request->vendor
                ?? $request->nama_vendor
                ?? $request->customer
                ?? $request->nama_toko
                ?? '';

            $confidence      = $request->confidence ?? $request->overall_confidence ?? 0;
            $confidenceLabel = $request->confidence_label
                ?? ($confidence > 70 ? 'HIGH' : ($confidence > 40 ? 'MEDIUM' : 'LOW'));

            $fieldConfidence = $request->field_confidence ?? [];
            if (empty($fieldConfidence)) {
                $fieldConfidence = [
                    'vendor'        => $confidence,
                    'tanggal'       => $confidence,
                    'total_belanja' => $confidence,
                    'material'      => $confidence,
                    'jumlah'        => $confidence,
                    'satuan'        => $confidence,
                    'nominal'       => $confidence,
                ];
            }

            Log::channel('ai_autofill')->info('📦 [AI CALLBACK] ITEMS NORMALIZED', [
                'step'             => '5_items',
                'upload_id'        => $uploadId,
                'items_count'      => count($items),
                'vendor'           => $vendor,
                'total_amount'     => $request->total_belanja ?? $request->amount ?? 0,
                'confidence'       => $confidence,
                'confidence_label' => $confidenceLabel,
            ]);

            return [
                'status'    => 'completed',
                'upload_id' => $uploadId,

                'vendor'     => $vendor,
                'customer'   => $vendor,
                'nama_vendor'=> $vendor,
                'nama_toko'  => $vendor,

                'amount'        => $request->total_belanja ?? $request->amount ?? 0,
                'total_belanja' => $request->total_belanja ?? $request->amount ?? 0,
                'dpp_lainnya'   => $request->dpp_lainnya ?? 0,
                'tax_amount'    => $request->tax_amount ?? $request->tax ?? 0,

                'date'    => $date,
                'tanggal' => $date,

                'items' => $items,

                'confidence'         => $confidence,
                'overall_confidence' => $request->overall_confidence ?? $confidence,
                'confidence_label'   => $confidenceLabel,
                'field_confidence'   => $fieldConfidence,
            ];
        }


        private function logCacheStored(string $uploadId, array $cacheData): void
        {
            Log::channel('ai_autofill')->info('💾 [AI CALLBACK] CACHE STORED', [
                'step'        => '5_cache',
                'upload_id'   => $uploadId,
                'cache_key'   => "ai_autofill:{$uploadId}",
                'ttl_minutes' => 30,
                'confidence'  => $cacheData['confidence'],
            ]);
        }


        /**
         * ─────────────────────────────────────────────────────────
         *  ✅ FIX Bug #2: Status menggunakan key frontend
         *  ✅ FIX Bug #5: Duplikat auto-reject logic DIHAPUS
         *     (sudah ditangani oleh n8n Layer 2 → handleOcrFailed)
         * ─────────────────────────────────────────────────────────
         */
        private function updateTransactionOnSuccess(string $uploadId, array $cacheData): void
        {
            Log::channel('ai_autofill')->info('🔄 [AI CALLBACK] TRANSACTION UPDATED', [
                'step'           => '5_transaction_updated',
                'upload_id'      => $uploadId,
                'new_ai_status'  => 'completed',
                'confidence'     => $cacheData['confidence'],
            ]);

            $transaction = Transaction::where('upload_id', $uploadId)->first();

            // Fallback check
            if (!$transaction && request()->has('transaksi_id') && request()->transaksi_id) {
                $transaction = Transaction::find(request()->transaksi_id);
            }

            if (!$transaction) {
                Log::channel('ai_autofill')->error('❌ [AI CALLBACK] TRANSACTION NOT FOUND', [
                    'step'           => '5_transaction_not_found',
                    'upload_id'      => $uploadId,
                    'all_upload_ids' => Transaction::pluck('upload_id')->take(10)->toArray(),
                ]);
                return;
            }

            Log::channel('ai_autofill')->info('✅ [AI CALLBACK] TRANSACTION FOUND', [
                'step'              => '5_transaction_found',
                'upload_id'         => $uploadId,
                'transaction_id'    => $transaction->id,
                'invoice_number'    => $transaction->invoice_number,
                'current_status'    => $transaction->status,
                'current_ai_status' => $transaction->ai_status,
            ]);

            // ── ✅ FIX Bug #2: 'pending' (lowercase) = frontend key ──
            // ── ✅ FIX Bug #5: TIDAK ada duplikat auto-reject check ──
            //    Auto-reject sudah ditangani di handleOcrFailed() via n8n callback.
            //    Method ini HANYA dipanggil saat status = "success" dari n8n.
            $newStatus = 'pending';

            $transaction->update([
                'customer'           => $cacheData['customer'],
                'vendor'             => $cacheData['vendor'] ?? $cacheData['customer'],
                'amount'             => $cacheData['amount'],
                'dpp_lainnya'        => $cacheData['dpp_lainnya'] ?? 0,
                'tax_amount'         => $cacheData['tax_amount'] ?? 0,
                'items'              => $cacheData['items'],
                'date'               => $cacheData['date'],
                'ai_status'          => 'completed',
                'status'             => $newStatus,
                'confidence'         => $cacheData['confidence'],
                'overall_confidence' => $cacheData['overall_confidence'] ?? $cacheData['confidence'],
                'confidence_label'   => $cacheData['confidence_label'] ?? ($cacheData['confidence'] > 70 ? 'HIGH' : 'LOW'),
                'field_confidence'   => $cacheData['field_confidence'] ?? null,
            ]);

            Log::channel('ai_autofill')->info('🔄 [AI CALLBACK] TRANSACTION UPDATED', [
                'step'           => '5_transaction_updated',
                'upload_id'      => $uploadId,
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'new_status'     => $newStatus,
                'new_ai_status'  => 'completed',
                'confidence'     => $cacheData['confidence'],
            ]);

                dispatch(function() use ($transaction) {
            broadcast(new TransactionUpdated($transaction->fresh()));
        })->afterResponse(); // Fire setelah HTTP response dikirim  

            $submitter = User::find($transaction->submitted_by);
            if ($submitter) {
                $submitter->notify(new OcrStatusNotification(
                    transaction: $transaction,
                    aiStatus: 'completed',
                    confidence: $cacheData['confidence'],
                ));

                broadcast(new OcrStatusUpdated($submitter->id, [
                    'transaction_id' => $transaction->id,
                    'invoice_number' => $transaction->invoice_number,
                    'ai_status'      => 'completed',
                    'confidence'     => $cacheData['confidence'],
                    'confidence_label' => $cacheData['confidence_label'] ?? ($cacheData['confidence'] > 70 ? 'HIGH' : 'LOW'),
                    'message'        => 'Auto-fill AI selesai (Confidence: ' . $cacheData['confidence'] . '%).',
                    'transaction'    => $transaction->fresh()->toSearchArray(),
                ]));

                Log::channel('ai_autofill')->info('📬 [AI CALLBACK] NOTIFICATION SENT', [
                    'step'           => '5_notification',
                    'upload_id'      => $uploadId,
                    'transaction_id' => $transaction->id,
                    'user_id'        => $submitter->id,
                    'user_name'      => $submitter->name,
                ]);
            }
        }


    /**
     * Get OCR Status (Primary)
     * 
     * Primary polling endpoint for the frontend to check the current status of an AI OCR process.
     */
    public function status($uploadId): \Illuminate\Http\JsonResponse
    {
            $cacheKey = "ai_autofill:{$uploadId}";
            $data     = Cache::get($cacheKey);

            Log::channel('ai_autofill')->debug('🔍 [AI POLL] STATUS POLL REQUEST', [
                'upload_id'    => $uploadId,
                'cache_found'  => $data !== null,
                'cache_status' => $data['status'] ?? 'not_found',
            ]);

            // ✅ FIX: Check timeout for stuck processing
            $transaction = Transaction::where('upload_id', $uploadId)->first();

            if ($transaction && $transaction->ai_status === 'processing') {
                $processingDuration = now()->diffInSeconds($transaction->updated_at);

                // If processing > 3 minutes = timeout
                if ($processingDuration > 180) {
                    Log::channel('ai_autofill')->warning('⚠️ [AI POLL] PROCESSING TIMEOUT DETECTED', [
                        'upload_id'        => $uploadId,
                        'duration_seconds' => $processingDuration,
                        'action'           => 'Marking as error - n8n callback likely failed'
                    ]);

                    $transaction->update(['ai_status' => 'error']);

                    Cache::put($cacheKey, [
                        'status'  => 'error',
                        'message' => 'Proses AI timeout (>3 menit). Silakan isi data secara manual.'
                    ], now()->addMinutes(30));

                    broadcast(new TransactionUpdated($transaction->fresh()));

                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Proses AI timeout. Silakan isi data secara manual.'
                    ]);
                }
            }

            // ── Handle error state ──
            if (($data['status'] ?? '') === 'error') {
                return response()->json([
                    'status'  => 'error',
                    'message' => $data['message'] ?? 'Terjadi kesalahan pada proses AI.',
                ]);
            }

            // ── ✅ FIX Bug #5: Handle auto-reject state (from n8n Layer 1/2) ──
            if (($data['status'] ?? '') === 'auto-reject') {
                return response()->json([
                    'status'  => 'auto-reject',
                    'message' => $data['message'] ?? 'Nota ditolak otomatis.',
                ]);
            }

            // ── Handle completed state ──
            if (($data['status'] ?? '') === 'completed') {
                return response()->json([
                    'status' => 'completed',
                    'data'   => [
                        'customer'    => $data['customer'] ?? null,
                        'amount'      => $data['amount'] ?? null,
                        'date'        => $data['date'] ?? null,
                        'items'       => $data['items'] ?? [],
                        'confidence'  => $data['confidence'] ?? null,
                        'total_items' => count($data['items'] ?? []),
                    ],
                ]);
            }

            // ── ✅ FIX: Jika cache kosong DAN tidak ada transaksi (ghost job akibat rollback) → error ──
            // Sebelumnya: default ke 'processing' meskipun data tidak ada sama sekali
            if ($data === null && !$transaction) {
                Log::channel('ai_autofill')->warning('⚠️ [AI POLL] GHOST JOB DETECTED - NO CACHE AND NO TRANSACTION', [
                    'upload_id' => $uploadId,
                    'action'    => 'Returning error - transaction likely rolled back'
                ]);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Data tidak ditemukan. Proses mungkin gagal, silakan coba upload ulang.',
                ]);
            }

            // ── Handle queued/pending/processing states ──
            $phase  = $data['phase'] ?? 'queued';
            $status = in_array($phase, ['queued', 'pending', 'processing']) ? $phase : 'processing';

            return response()->json([
                'status'         => $status,
                'phase'          => $phase,
                'message'        => match ($phase) {
                    'queued'     => 'Menunggu dalam antrian...',
                    'pending'    => 'Menunggu file terupload...',
                    'processing' => 'Sedang memproses dengan AI...',
                    default      => 'Memproses...',
                },
                'estimated_wait' => $phase === 'queued' ? 30 : ($phase === 'processing' ? 15 : null),
            ]);
        }
    
    /**
     * Get OCR Status (Legacy)
     * 
     * Legacy polling endpoint for backward compatibility.
     */
    public function statusLegacy($uploadId): \Illuminate\Http\JsonResponse
    {
        return $this->status($uploadId);
    }


    /**
     * Admin OCR Monitoring
     * 
     * Provides status updates on rate limits and queue lengths. Restricted to Admin/Owner.
     */
    public function ocrStatus(GeminiRateLimiter $rateLimiter): \Illuminate\Http\JsonResponse
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'owner'])) {
            Log::channel('ai_autofill')->warning('🔒 [AI ADMIN] OCR STATUS FORBIDDEN', [
                'step'      => '7_admin',
                'user_id'   => auth()->id(),
                'user_role' => auth()->user()->role ?? 'guest',
            ]);
            return response()->json(['message' => 'Forbidden'], 403);
        }

        Log::channel('ai_autofill')->info('👤 [AI ADMIN] OCR STATUS ACCESSED', [
            'step'      => '7_admin',
            'user_id'   => auth()->id(),
            'user_name' => auth()->user()->name,
            'user_role' => auth()->user()->role,
        ]);

        return response()->json([
            'rate_limiter' => $rateLimiter->getStatus(),
            'queue_stats'  => [
                'default'    => Redis::llen('queues:default'),
                'ocr_high'   => Redis::llen('queues:ocr_high'),
                'ocr_normal' => Redis::llen('queues:ocr_normal'),
                'ocr_low'    => Redis::llen('queues:ocr_low'),
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }


    /**
     * Update Payment Status (AI Callback)
     * 
     * Callback for n8n to update payment status after verifying transfer/cash receipts via OCR.
     * 
     * @unauthenticated
     */
    public function updateStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload_id'      => 'required|string',
            'transaksi_id'   => 'nullable|string',
            'payment_method' => 'required|string|in:CASH,TRANSFER',
            'status'         => 'required|string',

            'ocr_result'     => 'nullable|string|in:MATCH,MISMATCH',
            'actual_total'   => 'nullable|numeric',
            'expected_total' => 'nullable|numeric',
            'selisih'        => 'nullable|numeric',
            'flag_reason'    => 'nullable|string',
            'ocr_confidence' => 'nullable|numeric',

            'konfirmasi_by'  => 'nullable|string',
            'konfirmasi_at'  => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $transactionQuery = Transaction::where('upload_id', $request->upload_id);

        if ($request->transaksi_id) {
            $transactionQuery->orWhere('id', $request->transaksi_id);
        }

        $transaction = $transactionQuery->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        // ── ✅ FIX Bug #2: Normalisasi status dari n8n ──
        $normalizedStatus = $this->normalizeTransactionStatus($request->status);

        // ── ✅ FIX: Sesuaikan rule 1 Juta jika status Selesai (Completed) ──
        if ($normalizedStatus === 'completed' && $transaction->effective_amount >= 1000000) {
            $normalizedStatus = 'approved'; // Menunggu Owner
        }

        $updateData = [
            'status' => $normalizedStatus,
        ];

        if ($request->payment_method === 'TRANSFER') {
            if ($request->has('ocr_result'))     $updateData['ocr_result']     = $request->ocr_result;
            if ($request->has('actual_total'))    $updateData['actual_total']   = $request->actual_total;
            if ($request->has('expected_total'))  $updateData['expected_total'] = $request->expected_total;
            if ($request->has('selisih'))         $updateData['selisih']        = $request->selisih;
            if ($request->has('flag_reason'))     $updateData['flag_reason']    = $request->flag_reason;
            if ($request->has('ocr_confidence'))  $updateData['ocr_confidence'] = $request->ocr_confidence;
        }

        if ($request->payment_method === 'CASH') {
            if ($request->has('konfirmasi_by')) $updateData['konfirmasi_by'] = $request->konfirmasi_by;
            if ($request->has('konfirmasi_at')) $updateData['konfirmasi_at'] = Carbon::parse($request->konfirmasi_at);
        }

        $transaction->update($updateData);

        // ── ✅ NEW: Catat Audit + Kirim Telegram jika FLAGGED ──
        if ($normalizedStatus === 'flagged') {
            $this->recordDiscrepancyAudit($transaction, $request);
            $this->sendFlaggedTelegramNotification($transaction);
        }

        broadcast(new TransactionUpdated($transaction->fresh()));

        Log::channel('ai_autofill')->info('🔄 [AI CALLBACK] PEMBAYARAN UPDATED', [
            'upload_id'       => $request->upload_id,
            'payment_method'  => $request->payment_method,
            'raw_status'      => $request->status,
            'normalized_status' => $updateData['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status pembayaran berhasil diperbarui',
        ]);
    }

    /**
     * Log initial callback info
     */
    private function logCallbackReceived(Request $request): void
    {
        Log::channel('ai_autofill')->info('📥 [AI CALLBACK] RECEIVED FROM N8N', [
            'step'         => '5_callback_received',
            'ip'           => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'headers'      => [
                'X-Upload-ID'    => $request->header('X-Upload-ID'),
                'X-Transaksi-ID' => $request->header('X-Transaksi-ID'),
                'X-SECRET'       => substr($request->header('X-SECRET') ?? '', 0, 5) . '...',
            ],
            'query_params' => [
                'upload_id'    => $request->query('upload_id'),
                'transaksi_id' => $request->query('transaksi_id'),
            ],
            'body_params'  => [
                'upload_id'    => $request->input('upload_id'),
                'transaksi_id' => $request->input('transaksi_id'),
                'status'       => $request->input('status'),
            ],
            'timestamp'    => now()->toIso8601String(),
        ]);
    }

    /**
     * Map old fields to new fields for backward compatibility
     */
    private function normalizeRequest(Request $request): void
    {
        if ($request->has('vendor') && !$request->has('customer')) {
            $request->merge(['customer' => $request->vendor]);
        }
        if ($request->has('total_belanja') && (!$request->has('amount') || (float)$request->amount == 0)) {
            $request->merge(['amount' => $request->total_belanja]);
        }
        if ($request->has('tanggal') && !$request->has('date')) {
            $request->merge(['date' => $request->tanggal]);
        }
        if (($request->has('overall_confidence') || $request->has('confidence')) && !$request->has('confidence_score')) {
            $score = $request->input('overall_confidence') ?? $request->input('confidence') ?? 0;
            $request->merge(['confidence_score' => $score]);
        }
    }

    /**
     * Handle OCR failure status (failed, error, auto_reject, low_confidence)
     */
    private function handleOcrFailed(Request $request, string $uploadId)
    {
        $status = $this->normalizeTransactionStatus($request->status ?? 'error');
        $message = $request->message ?? $request->reason ?? 'OCR gagal';

        Log::channel('ai_autofill')->warning('❌ [AI CALLBACK] OCR FAILED', [
            'upload_id' => $uploadId,
            'status'    => $status,
            'message'   => $message,
        ]);

        $transaction = Transaction::where('upload_id', $uploadId)->first();
        
        // Fallback search
        if (!$transaction && $request->transaksi_id && is_numeric($request->transaksi_id)) {
            $transaction = Transaction::find((int) $request->transaksi_id);
        }

        if ($transaction) {
            $aiStatus = ($status === 'auto-reject') ? 'auto-reject' : 'error';
            
            $transaction->update([
                'ai_status' => $aiStatus,
                'status'    => ($aiStatus === 'auto-reject') ? 'auto-reject' : $transaction->status,
                'description' => $transaction->description . " | AI Detail: {$message}"
            ]);

            Cache::put("ai_autofill:{$uploadId}", [
                'status'  => $aiStatus,
                'message' => $message,
            ], now()->addMinutes(30));

            $submitter = User::find($transaction->submitted_by);
            if ($submitter) {
                // 🔔 Record in database
                $submitter->notify(new OcrStatusNotification(
                    transaction: $transaction,
                    aiStatus: $aiStatus
                ));

                // 🔔 Trigger real-time grid update
                broadcast(new OcrStatusUpdated($submitter->id, [
                    'transaction_id' => $transaction->id,
                    'invoice_number' => $transaction->invoice_number,
                    'ai_status'      => $aiStatus,
                    'message'        => 'Auto-fill AI gagal: ' . $message,
                ]));
            }
            
            broadcast(new TransactionUpdated($transaction->fresh()));
        }

        return response()->json([
            'success'   => false,
            'status'    => $status,
            'upload_id' => $uploadId,
            'message'   => $message
        ]);
    }

    // ─── Private Helpers ──────────────────────────────────────────────

    /**
     * Catat selisih ke tabel payment_discrepancy_audits
     */
    private function recordDiscrepancyAudit(Transaction $transaction, Request $request): void
    {
        try {
            PaymentDiscrepancyAudit::create([
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'expected_total' => $request->expected_total ?? $transaction->expected_total ?? 0,
                'actual_total'   => $request->actual_total   ?? $transaction->actual_total   ?? 0,
                'selisih'        => $request->selisih         ?? $transaction->selisih        ?? 0,
                'ocr_result'     => $request->ocr_result      ?? 'MISMATCH',
                'ocr_confidence' => $request->ocr_confidence  ?? null,
                'flag_reason'    => $request->flag_reason      ?? $transaction->flag_reason   ?? null,
                'resolution'     => 'pending',
                'submitted_by'   => $transaction->submitted_by,
                'payment_method' => $transaction->payment_method,
            ]);

            Log::channel('ai_autofill')->info('📋 [AUDIT] Discrepancy audit recorded', [
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'selisih'        => $request->selisih ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::channel('ai_autofill')->error('❌ [AUDIT] Failed to record discrepancy audit', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * Kirim notifikasi Telegram ke Owner jika transaksi Flagged
     */
    private function sendFlaggedTelegramNotification(Transaction $transaction): void
    {
        try {
            $telegram = new TelegramBotService();
            $telegram->notifyFlaggedTransaction($transaction->load('submitter'));
        } catch (\Exception $e) {
            Log::channel('ai_autofill')->error('❌ [TELEGRAM] Failed to send flagged notification', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}