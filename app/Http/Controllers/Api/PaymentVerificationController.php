<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\PaymentDiscrepancyAudit;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $request->validate([
            'upload_id'      => 'required|string',
            'status'         => 'required|in:match,flagged',
            'actual_total'   => 'nullable|numeric',
            'expected_total' => 'nullable|numeric',
            'selisih'        => 'nullable|numeric',
            'confidence'     => 'nullable|numeric',
            'flag_reason'    => 'nullable|string',
        ]);

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

        Log::channel('ai_autofill')->info('📥 [PAYMENT VERIFY] Callback received from n8n', [
            'upload_id'      => $uploadId,
            'transaction_id' => $transaction->id,
            'status'         => $status,
            'actual_total'   => $request->actual_total,
            'expected_total' => $request->expected_total,
            'selisih'        => $request->selisih,
        ]);

        if ($status === 'match') {
            // ═══════════════════════════════════════════════════════════
            //  STATUS: MATCH — Nominal sesuai
            // ═══════════════════════════════════════════════════════════
            
            // Determine final status based on amount
            $isRequiresOwner = $transaction->effective_amount >= 1000000;
            $finalStatus = $isRequiresOwner ? 'approved' : 'completed';

            $transaction->update([
                'status'       => $finalStatus,
                'actual_total' => $request->actual_total,
                'selisih'      => 0,
                'confidence'   => $request->confidence ?? 100,
            ]);

            Log::channel('ai_autofill')->info('✅ [PAYMENT VERIFY] Transfer MATCH', [
                'transaction_id' => $transaction->id,
                'final_status'   => $finalStatus,
                'requires_owner' => $isRequiresOwner,
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

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data'    => [
                    'transaction_id' => $transaction->id,
                    'status'         => $finalStatus,
                    'actual_total'   => $request->actual_total,
                ],
            ]);

        } else {
            // ═══════════════════════════════════════════════════════════
            //  STATUS: FLAGGED — Ada selisih nominal
            // ═══════════════════════════════════════════════════════════
            
            $transaction->update([
                'status'       => 'flagged',
                'actual_total' => $request->actual_total,
                'selisih'      => $request->selisih ?? 0,
                'flag_reason'  => $request->flag_reason ?? 'Selisih nominal transfer',
                'confidence'   => $request->confidence ?? 0,
                'is_locked'    => true,  // Lock transaction, perlu force approve
            ]);

            // Create audit record
            PaymentDiscrepancyAudit::create([
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'expected_total' => $request->expected_total ?? $transaction->expected_total ?? 0,
                'actual_total'   => $request->actual_total   ?? 0,
                'selisih'        => $request->selisih        ?? 0,
                'ocr_result'     => 'MISMATCH',
                'flag_reason'    => $request->flag_reason    ?? 'Selisih nominal transfer',
                'resolution'     => 'pending',
                'submitted_by'   => $transaction->submitted_by,
                'payment_method' => $transaction->payment_method,
            ]);

            Log::channel('ai_autofill')->warning('⚠️ [PAYMENT VERIFY] Transfer FLAGGED', [
                'transaction_id' => $transaction->id,
                'expected'       => $request->expected_total,
                'actual'         => $request->actual_total,
                'selisih'        => $request->selisih,
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
    }
}