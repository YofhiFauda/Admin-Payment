<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\PaymentDiscrepancyAudit;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ═══════════════════════════════════════════════════════════════
 *  PaymentVerificationController
 * 
 *  Handle callback dari n8n setelah OCR bukti transfer
 *  
 *  🔔 TELEGRAM INTEGRATION:
 *  - IF status=match → notifyPaymentComplete() ke TEKNISI
 *  - IF status=flagged → notifyFlaggedTransaction() ke SEMUA OWNER
 * ═══════════════════════════════════════════════════════════════
 */
class PaymentVerificationController extends Controller
{
    private TelegramBotService $telegram;

    public function __construct(TelegramBotService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * POST /api/payment/verify
     * 
     * Callback dari n8n workflow OCR bukti transfer
     * 
     * Expected payload:
     * {
     *   "upload_id": "RMBSH-20260313-001",
     *   "status": "match|flagged",
     *   "actual_total": 150000,
     *   "expected_total": 150000,
     *   "selisih": 0,
     *   "confidence": 95,
     *   "flag_reason": "Selisih nominal Rp 5.000",
     *   "extracted_data": {...}
     * }
     */
    public function handle(Request $request)
    {
        // ✅ Support upload_id dari query parameter atau body
        $uploadId = $request->query('upload_id') ?? $request->input('upload_id');
        
        // Merge upload_id ke request untuk validasi
        if ($uploadId) {
            $request->merge(['upload_id' => $uploadId]);
        }
        
        $request->validate([
            'upload_id'      => 'required|string',
            'status'         => 'required|in:match,flagged,completed,success,mismatch,failed,error',
            'actual_total'   => 'nullable|numeric',
            'expected_total' => 'nullable|numeric',
            'amount'         => 'nullable|numeric',
            'total'          => 'nullable|numeric',
            'nominal'        => 'nullable|numeric',
            'total_transfer' => 'nullable|numeric',
            'selisih'        => 'nullable|numeric',
            'confidence'     => 'nullable|numeric',
            'ocr_confidence' => 'nullable|numeric',  // N8N mengirim field ini
            'flag_reason'    => 'nullable|string',
        ]);

        // 🔍 DEBUG: Log payload asli dari N8N untuk analisa Match/Mismatch
        Log::channel('ai_autofill')->info('🔍 [PAYMENT VERIFY] Raw N8N Payload Received', [
            'query_params' => $request->query(),
            'body_params'  => $request->except(['secret']),
            'upload_id'    => $uploadId,
        ]);

        // ── ✅ NEW: Penanggulangan Race Condition via Redis Lock ──
        $lock = Cache::lock("lock:payment_verify:{$uploadId}", 30);

        try {
            if (!$lock->get()) {
                Log::channel('ai_autofill')->warning('🔒 [PAYMENT VERIFY] DUPLICATE REQUEST BLOCKED (LOCKED)', [
                    'upload_id' => $uploadId
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Request is already being processed.'
                ], 202);
            }

        // Normalisasi status: N8N kadang kirim "completed" atau "mismatch"
        // Laravel hanya pakai "match" atau "flagged" secara internal
        $rawStatus = $request->status;
        if (in_array($rawStatus, ['completed', 'success'], true)) {
            $request->merge(['status' => 'match']);
        } elseif (in_array($rawStatus, ['mismatch', 'failed', 'error'], true)) {
            $request->merge(['status' => 'flagged']);
        }

        // Normalisasi confidence: N8N bisa kirim 'ocr_confidence' atau 'confidence'
        $confidence = $request->confidence ?? $request->ocr_confidence ?? null;

        $uploadId = $request->upload_id;
        $status   = $request->status;

        // Find transaction by upload_id
        $transaction = Transaction::where('upload_id', $uploadId)
            ->with('submitter')  // 🔔 Load teknisi untuk Telegram
            ->first();

        if (!$transaction) {
            Log::channel('ai_autofill')->error('❌ [PAYMENT VERIFY] Transaction not found', [
                'upload_id' => $uploadId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        $expectedTotal = $this->firstPositiveAmount(
            $transaction->expected_total,
            $request->expected_total,
            $transaction->effective_amount
        );
        $actualTotal = $this->firstPositiveAmount(
            $request->actual_total,
            $request->amount,
            $request->total,
            $request->nominal,
            $request->total_transfer
        );
        $calculatedSelisih = abs($expectedTotal - $actualTotal);
        $tolerance     = 1000; // Standard system tolerance

        // 🛡️ ZERO TRUST: Even if n8n says 'match', we override if backend finds a discrepancy
        if ($status === 'match' && ($expectedTotal <= 0 || $actualTotal <= 0 || $calculatedSelisih > $tolerance)) {
            Log::channel('ai_autofill')->warning('🚨 [PAYMENT VERIFY] n8n reported MATCH but backend found DISCREPANCY', [
                'upload_id' => $uploadId,
                'expected'  => $expectedTotal,
                'actual'    => $actualTotal,
                'selisih'   => $calculatedSelisih
            ]);
            $status = 'flagged';
        }
        
        Log::channel('ai_autofill')->info('📥 [PAYMENT VERIFY] Processing callback', [
            'upload_id'      => $uploadId,
            'transaction_id' => $transaction->id,
            'status'         => $status,
            'actual_total'   => $actualTotal,
            'expected_total' => $expectedTotal,
            'selisih'        => $calculatedSelisih
        ]);

        if ($status === 'match') {
            // ═══════════════════════════════════════════════════════════
            //  STATUS: MATCH — Nominal sesuai
            // ═══════════════════════════════════════════════════════════
            
            // Determine final status based on amount
            $isRequiresOwner = $transaction->effective_amount >= 1000000;
            $finalStatus = $isRequiresOwner ? 'approved' : 'completed';

            $transaction->update([
                'status'         => $finalStatus,
                'actual_total'   => $actualTotal,
                'expected_total' => $expectedTotal,
                'confidence'     => $confidence ?? 100,
                // 'selisih' will be auto-calculated by Model Saving event
            ]);

            Log::channel('ai_autofill')->info('✅ [PAYMENT VERIFY] Transfer MATCH', [
                'transaction_id' => $transaction->id,
                'final_status'   => $finalStatus,
                'requires_owner' => $isRequiresOwner,
                'selisih'        => $transaction->selisih,
            ]);

            // ═══════════════════════════════════════════════════════════
            //  🔔 TELEGRAM: Kirim notifikasi ke TEKNISI (transfer berhasil)
            // ═══════════════════════════════════════════════════════════
            try {
                $this->telegram->notifyPaymentComplete($transaction);
                
                Log::channel('ai_autofill')->info('✅ [TELEGRAM] Transfer complete notification sent', [
                    'transaction_id' => $transaction->id,
                    'teknisi_id'     => $transaction->submitter?->id,
                ]);
            } catch (\Exception $e) {
                Log::channel('ai_autofill')->error('❌ [TELEGRAM] Failed to send transfer complete notification', [
                    'transaction_id' => $transaction->id,
                    'error'          => $e->getMessage(),
                ]);
            }

            // 🔔 Broadcast Realtime Update (Agar UI langsung terganti tanpa Refresh F5)
            broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data'    => [
                    'transaction_id' => $transaction->id,
                    'status'         => $finalStatus,
                    'actual_total'   => $actualTotal,
                    'selisih'        => $transaction->selisih,
                ],
            ]);

        } else {
            // ═══════════════════════════════════════════════════════════
            //  STATUS: FLAGGED — Ada selisih nominal
            // ═══════════════════════════════════════════════════════════
            
            $transaction->update([
                'status'         => 'flagged',
                'actual_total'   => $actualTotal,
                'expected_total' => $expectedTotal,
                'flag_reason'    => $request->flag_reason
                    ?? ($expectedTotal <= 0 || $actualTotal <= 0
                        ? 'Nominal transfer tidak terbaca lengkap oleh AI'
                        : 'Selisih nominal transfer'),
                'confidence'     => $confidence ?? 0,
                'is_locked'      => true,
                // 'selisih' will be auto-calculated by Model Saving event
            ]);

            // Create audit record
            PaymentDiscrepancyAudit::create([
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'expected_total' => $transaction->expected_total,
                'actual_total'   => $transaction->actual_total,
                'selisih'        => $transaction->selisih,
                'ocr_result'     => 'MISMATCH',
                'flag_reason'    => $transaction->flag_reason,
                'resolution'     => 'pending',
                'submitted_by'   => $transaction->submitted_by,
                'payment_method' => $transaction->payment_method,
            ]);

            Log::channel('ai_autofill')->warning('⚠️ [PAYMENT VERIFY] Transfer FLAGGED', [
                'transaction_id' => $transaction->id,
                'expected'       => $transaction->expected_total,
                'actual'         => $transaction->actual_total,
                'selisih'        => $transaction->selisih,
            ]);

            // ═══════════════════════════════════════════════════════════
            //  🔔 TELEGRAM: Kirim notifikasi ke SEMUA OWNER (flagged alert)
            // ═══════════════════════════════════════════════════════════
            try {
                $this->telegram->notifyFlaggedTransaction($transaction);
                
                Log::channel('ai_autofill')->info('✅ [TELEGRAM] Flagged notification sent to all owners', [
                    'transaction_id' => $transaction->id,
                    'selisih'        => $request->selisih,
                ]);
            } catch (\Exception $e) {
                Log::channel('ai_autofill')->error('❌ [TELEGRAM] Failed to send flagged notification', [
                    'transaction_id' => $transaction->id,
                    'error'          => $e->getMessage(),
                ]);
            }

            // 🔔 Broadcast Realtime Update (Agar UI Terminal/Status Flagged langsung muncul tanpa Refresh F5)
            broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));

            return response()->json([
                'success' => true,
                'message' => 'Payment flagged for review',
                'data'    => [
                    'transaction_id' => $transaction->id,
                    'status'         => 'flagged',
                    'expected_total' => $request->expected_total,
                    'actual_total'   => $request->actual_total,
                    'selisih'        => $request->selisih,
                ],
            ]);
        }
        } finally {
            $lock->release();
        }
    }

    private function firstPositiveAmount(...$values): float
    {
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $normalized = is_string($value)
                ? preg_replace('/[^\d.-]/', '', $value)
                : $value;

            if (!is_numeric($normalized)) {
                continue;
            }

            $amount = (float) $normalized;
            if ($amount > 0) {
                return $amount;
            }
        }

        return 0.0;
    }
}
