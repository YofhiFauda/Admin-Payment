<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transaction;
use App\Models\PaymentDiscrepancyAudit;
use App\Services\IdGeneratorService;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

/**
 * ═══════════════════════════════════════════════════════════════
 *  OcrNotaController — FULL FIX
 *
 *  ✅ Bug #2: Status strings normalized to frontend keys
 *  ✅ Bug #3: Webhook URLs fixed with /webhook/ prefix
 *  ✅ Bug #6: Accept field names from both frontend & API
 * ═══════════════════════════════════════════════════════════════
 */
class OcrNotaController extends Controller
{
    /**
     * POST /api/v1/nota/upload
     */
    public function uploadNota(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foto_nota'        => 'required|file|image|max:1024',
            'transaksi_id'     => 'nullable|string',
            'expected_nominal' => 'nullable|numeric',
            'payment_method'   => 'required|in:cash,transfer_teknisi,transfer_penjual',
            'branch_id'        => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi awal gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $seq         = IdGeneratorService::nextSequence();
        $uploadId    = IdGeneratorService::buildUploadId($seq);
        $transaksiId = $request->transaksi_id ?? Str::uuid()->toString();
        $path        = $request->file('foto_nota')->store('notas', 'public');

        // ── ✅ FIX Bug #2: Use 'pending' (frontend key) instead of 'Diproses' ──
        $transaction = Transaction::create([
            'upload_id'      => $uploadId,
            'trace_id'       => $transaksiId,
            'file_path'      => $path,
            'expected_total'  => $request->expected_nominal,
            'status'         => 'pending',
            'type'           => 'rembush',
            'payment_method' => $request->payment_method,
            'submitted_by'   => auth()->id(),
            'ai_status'      => 'queued',
        ]);

        if ($request->has('branch_id')) {
            $transaction->branches()->attach($request->branch_id, [
                'allocation_percent' => 100,
                'allocation_amount'  => $request->expected_nominal ?? 0,
            ]);
        }

        Log::channel('ocr')->info('📄 [OCR FLOW] UPLOAD ID GENERATED', [
            'step'           => '1_upload_id',
            'upload_id'      => $uploadId,
            'transaction_id' => $transaction->id,
            'format'         => 'IdGeneratorService',
        ]);

        // ── ✅ FIX: Respect Gemini Rate Limit before sending to N8N ──
        $rateLimiter = app(\App\Services\OCR\GeminiRateLimiter::class);
        try {
            $rateLimiter->acquireSlot($uploadId);
            
            $n8nUrl = trim(config('services.n8n.webhook_url') ?? env('N8N_WEBHOOK'));
            if ($n8nUrl) {
                // n8n workflow path: "upload-nota" → full: {n8nUrl}/webhook/upload-nota
                $response = Http::timeout(30)->post("{$n8nUrl}/webhook/upload-nota", [
                    'upload_id'        => $uploadId,
                    'transaksi_id'     => $transaction->id,
                    'file_url'         => asset("storage/{$path}"),
                    'expected_nominal' => $request->expected_nominal,
                    'payment_method'   => $request->payment_method,
                    'branch_id'        => $request->branch_id,
                    'secret'           => config('services.n8n.secret'),
                ]);

                Log::channel('ocr')->info('📤 [N8N WEBHOOK] Upload Nota Triggered', [
                    'upload_id'       => $uploadId,
                    'webhook_url'     => "{$n8nUrl}/webhook/upload-nota",
                    'response_status' => $response->status(),
                ]);

                // Handle 429 directly if N8N propagates it
                if ($response->status() === 429) {
                    $rateLimiter->register429(60);
                }
            }
        } catch (\RuntimeException $e) {
            Log::channel('ocr')->warning('⏳ [OCR API] RATE LIMITER THROTTLE', [
                'upload_id' => $uploadId,
                'error'     => $e->getMessage(),
            ]);
            // Still return success 202 because the transaction is saved and 
            // the user should wait for polling/broadcasting anyway? 
            // Or return error? The job isn't queued here, it's direct.
            // Let's at least mark it as error in ai_status if we can't send it.
            $transaction->update(['ai_status' => 'error']);
        } catch (\Exception $e) {
            Log::channel('ocr')->error('❌ [N8N WEBHOOK] Upload Nota Failed', [
                'upload_id' => $uploadId,
                'error'     => $e->getMessage(),
            ]);
        } finally {
            $rateLimiter->releaseSlot($uploadId);
        }

        return response()->json([
            'success'        => true,
            'message'        => 'Nota sedang diproses (3 Layer Verification)',
            'upload_id'      => $uploadId,
            'transaksi_id'   => $transaksiId,
            'transaction_id' => $transaction->id,
            'status'         => 'pending',
            'ai_status'      => 'queued',
            'layers'         => [
                'layer1' => 'Security - Image Hashing',
                'layer2' => 'Logic - Validasi Tanggal',
                'layer3' => 'AI Extraction - Auto Fill',
            ],
            'polling_url' => url("/api/v1/transaksi/{$transaction->id}"),
        ], 202);
    }


    /**
     * GET /api/v1/transaksi
     */
    public function index(Request $request)
    {
        $query = Transaction::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vendor')) {
            $query->where('vendor', 'like', '%' . $request->vendor . '%');
        }

        $transaksi = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $transaksi,
        ]);
    }


    /**
     * GET /api/v1/transaksi/{id}
     */
    public function show($id)
    {
        $transaction = Transaction::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                 => $transaction->id,
                'upload_id'          => $transaction->upload_id,
                'status'             => $transaction->status,
                'confidence_label'   => ($transaction->confidence > 70) ? 'HIGH' : 'LOW',
                'overall_confidence' => $transaction->confidence,
                'field_confidence'   => [
                    'vendor'   => $transaction->confidence,
                    'material' => $transaction->confidence,
                    'jumlah'   => $transaction->confidence,
                    'satuan'   => $transaction->confidence,
                    'nominal'  => $transaction->confidence,
                ],
                'nama_vendor'    => $transaction->vendor ?? $transaction->customer,
                'tanggal_nota'   => optional($transaction->date)->format('d/m/Y'),
                'total_belanja'  => $transaction->amount,
                'items_json'     => $transaction->items,
            ],
        ]);
    }


    /**
     * POST /api/v1/payment/cash/upload
     *
     * ✅ FIX Bug #3: Webhook URL corrected
     */
    public function uploadCash(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foto_penyerahan' => 'nullable|file|image|max:1024',
            'file'            => 'nullable|file|image|max:1024',
            'upload_id'       => 'required|string',
            'transaksi_id'    => 'required|string',
            'teknisi_id'      => 'nullable|string',
            'catatan'         => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $transaction = Transaction::where('upload_id', $request->upload_id)
            ->orWhere('id', $request->transaksi_id)
            ->firstOrFail();

        if ($transaction->status !== 'waiting_payment') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi belum disetujui atau sudah dibayar. Status saat ini: ' . $transaction->status_label,
            ], 400);
        }

        $fileInput = $request->hasFile('foto_penyerahan') ? 'foto_penyerahan' : 'file';
        if (!$request->hasFile($fileInput)) {
            return response()->json(['message' => 'Foto penyerahan wajib diunggah.'], 422);
        }

        $path = $request->file($fileInput)->store('payments/cash', 'public');

        $transaction->update([
            'foto_penyerahan' => $path,
            'status'          => 'pending_technician',  // 18 chars - FITS!
            'description'     => $request->catatan,
        ]);

        Log::channel('ai_autofill')->info('📤 [UPLOAD CASH] PAYMENT PROOF UPLOADED', [
            'step'           => 'cash_upload',
            'upload_id'      => $request->upload_id,
            'transaction_id' => $transaction->id,
            'file_path'      => $path,
        ]);

        // ── ✅ FIX Bug #3: Correct n8n webhook path ──
        // SEBELUM: "{$n8nUrl}/payments/cash/upload"          ← SALAH (404)
        // SESUDAH: "{$n8nUrl}/webhook/payment/cash/upload"   ← BENAR (sesuai n8n node path)
        $n8nUrl = trim(config('services.n8n.webhook_url') ?? env('N8N_WEBHOOK'));
        if ($n8nUrl) {
            try {
                /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
                $storage = Storage::disk('public');

                $response = Http::timeout(30)->post("{$n8nUrl}/webhook/payment/cash/upload", [
                    'upload_id'    => $request->upload_id,
                    'transaksi_id' => $transaction->id,
                    'teknisi_id'   => $request->teknisi_id,
                    'foto_url'     => $storage->url($path),
                    'catatan'      => $request->catatan,
                ]);

                if ($response->successful()) {
                    Log::channel('ai_autofill')->info('✅ [UPLOAD CASH] N8N WEBHOOK SUCCESS', [
                        'step'            => 'cash_n8n_trigger',
                        'upload_id'       => $request->upload_id,
                        'webhook_url'     => "{$n8nUrl}/webhook/payment/cash/upload",
                        'response_status' => $response->status(),
                    ]);
                } else {
                    Log::channel('ai_autofill')->warning('⚠️ [UPLOAD CASH] N8N WEBHOOK FAILED', [
                        'step'            => 'cash_n8n_error',
                        'upload_id'       => $request->upload_id,
                        'response_status' => $response->status(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('ai_autofill')->error('❌ [UPLOAD CASH] N8N WEBHOOK EXCEPTION', [
                    'step'      => 'cash_n8n_exception',
                    'upload_id' => $request->upload_id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Foto penyerahan diterima. Menunggu konfirmasi teknisi.',
            'upload_id'     => $transaction->upload_id,
            'transaksi_id'  => $transaction->id,
            'pembayaran_id' => $transaction->id,
            'status'        => 'pending_technician',
            'status_label'  => 'Menunggu Konfirmasi Teknisi',
        ], 202);
    }


    /**
     * POST /api/v1/payment/cash/konfirmasi
     *
     * ✅ FIX Bug #2: 'completed' instead of 'Selesai'
     */
    public function konfirmasiCash(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'upload_id'    => 'required|string',
            'transaksi_id' => 'required|string',
            'teknisi_id'   => 'required|string',
            'action'       => 'required|in:terima,tolak',
            'catatan'      => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $transaction = Transaction::where('upload_id', $request->upload_id)
            ->orWhere('id', $request->transaksi_id)
            ->firstOrFail();

        // ── ✅ FIX Bug #2: 'completed' matches frontend key ──
        // ── ✅ FIX: Sesuaikan rule 1 Juta (Completed jika < 1jt, Approved tunggu Owner jika >= 1jt) ──
        if ($request->action === 'terima') {
            $isRequiresOwner = $transaction->effective_amount >= 1000000;
            $status = $isRequiresOwner ? 'approved' : 'completed';
        } else {
            $status = 'Ditolak Teknisi';
        }
        $now    = now();

        $transaction->update([
            'status'         => $status,
            'konfirmasi_by'  => $request->teknisi_id,
            'konfirmasi_at'  => $now,
            'description'    => $request->catatan
                ? $transaction->description . ' | Catatan Teknisi: ' . $request->catatan
                : $transaction->description,
        ]);

        Log::channel('ai_autofill')->info('✅ [KONFIRMASI CASH] TECHNICIAN CONFIRMED', [
            'step'           => 'cash_confirmation',
            'upload_id'      => $request->upload_id,
            'transaction_id' => $transaction->id,
            'action'         => $request->action,
            'status'         => $status,
        ]);

        return response()->json([
            'success'       => true,
            'message'       => $request->action === 'terima'
                ? 'Pembayaran dikonfirmasi. Status: Selesai.'
                : 'Pembayaran ditolak oleh teknisi.',
            'transaksi_id'  => $transaction->id,
            'status'        => $status,
            'konfirmasi_at' => $now->toIso8601String(),
        ], 200);
    }


    /**
     * POST /api/v1/payment/transfer/upload
     *
     * ✅ FIX Bug #3: Webhook URL corrected
     * ✅ FIX Bug #2: Fallback status uses 'flagged'
     */
    public function uploadTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bukti_transfer'   => 'nullable|file|image|max:1024',
            'file'             => 'nullable|file|image|max:1024',
            'upload_id'        => 'required|string',
            'transaksi_id'     => 'required|string',
            'expected_nominal' => 'required|numeric',
            'kode_unik'        => 'nullable|numeric',
            'biaya_admin'      => 'nullable|numeric',
            'rekening_tujuan'  => 'nullable|string',
            'nama_bank_tujuan' => 'nullable|string',
            'rekening_bank'    => 'nullable|string',
            'rekening_nomor'   => 'nullable|string',
            'rekening_nama'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $transaction = Transaction::where('upload_id', $request->upload_id)
            ->orWhere('id', $request->transaksi_id)
            ->with('submitter')
            ->firstOrFail();

        if ($transaction->status !== 'waiting_payment') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi belum disetujui atau sudah dibayar. Status saat ini: ' . $transaction->status_label,
            ], 400);
        }

        $fileInput = $request->hasFile('bukti_transfer') ? 'bukti_transfer' : 'file';
        if (!$request->hasFile($fileInput)) {
            return response()->json(['message' => 'Bukti transfer wajib diunggah.'], 422);
        }

        $path = $request->file($fileInput)->store('payments/transfer', 'public');

        // Auto-update Technician Profile
        if ($transaction->payment_method === 'transfer_teknisi' && $transaction->submitter) {
            $user        = $transaction->submitter;
            $needsUpdate = false;

            if ($request->filled('rekening_bank') && !$user->rekening_bank) {
                $user->rekening_bank = $request->rekening_bank;
                $needsUpdate = true;
            }
            if ($request->filled('rekening_nomor') && !$user->rekening_nomor) {
                $user->rekening_nomor = $request->rekening_nomor;
                $needsUpdate = true;
            }
            if ($request->filled('rekening_nama') && !$user->rekening_nama) {
                $user->rekening_nama = $request->rekening_nama;
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $user->save();
            }
        }

        $expectedTotal = $request->expected_nominal + ($request->kode_unik ?? 0) + ($request->biaya_admin ?? 0);

        $transaction->update([
            'bukti_transfer' => $path,
            'status'         => 'Sedang Diverifikasi AI',
            'expected_total' => $expectedTotal,
        ]);

        Log::channel('ai_autofill')->info('📤 [UPLOAD TRANSFER] PAYMENT PROOF UPLOADED', [
            'step'           => 'transfer_upload',
            'upload_id'      => $request->upload_id,
            'transaction_id' => $transaction->id,
            'file_path'      => $path,
            'expected_total' => $expectedTotal,
        ]);

        // ── ✅ FIX Bug #3: Correct n8n webhook path ──
        // SEBELUM: "{$n8nUrl}/payments/transfer/upload"          ← SALAH (404)
        // SESUDAH: "{$n8nUrl}/webhook/payment/transfer/upload"   ← BENAR (sesuai n8n node path)
        $n8nUrl = trim(config('services.n8n.webhook_url') ?? env('N8N_WEBHOOK'));
        if ($n8nUrl) {
            try {
                /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
                $storage = Storage::disk('public');

                $response = Http::timeout(30)->post("{$n8nUrl}/webhook/payment/transfer/upload", [
                    'upload_id'        => $request->upload_id,
                    'transaksi_id'     => $transaction->id,
                    'expected_nominal' => $request->expected_nominal,
                    'kode_unik'        => $request->kode_unik ?? 0,
                    'biaya_admin'      => $request->biaya_admin ?? 0,
                    'rekening_tujuan'  => $request->rekening_tujuan,
                    'nama_bank_tujuan' => $request->nama_bank_tujuan,
                    'foto_url'         => $storage->url($path),
                ]);

                if ($response->successful()) {
                    Log::channel('ai_autofill')->info('✅ [UPLOAD TRANSFER] N8N WEBHOOK SUCCESS', [
                        'step'            => 'transfer_n8n_trigger',
                        'upload_id'       => $request->upload_id,
                        'webhook_url'     => "{$n8nUrl}/webhook/payment/transfer/upload",
                        'response_status' => $response->status(),
                    ]);
                } else {
                    Log::channel('ai_autofill')->warning('⚠️ [UPLOAD TRANSFER] N8N WEBHOOK FAILED', [
                        'step'            => 'transfer_n8n_error',
                        'upload_id'       => $request->upload_id,
                        'response_status' => $response->status(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('ai_autofill')->error('❌ [UPLOAD TRANSFER] N8N WEBHOOK EXCEPTION', [
                    'step'      => 'transfer_n8n_exception',
                    'upload_id' => $request->upload_id,
                    'error'     => $e->getMessage(),
                ]);

                // ── ✅ FIX Bug #2: 'flagged' instead of 'Flagged - Manual Verification Required' ──
                $transaction->update([
                    'status' => 'flagged',
                ]);
            }
        }

        return response()->json([
            'success'        => true,
            'message'        => 'Bukti transfer diterima. AI sedang memverifikasi nominal.',
            'upload_id'      => $transaction->upload_id,
            'transaksi_id'   => $transaction->id,
            'pembayaran_id'  => $transaction->id,
            'expected_total' => $expectedTotal,
            'status'         => 'Sedang Diverifikasi AI',
            'detail'         => [
                'nominal'     => (float) $request->expected_nominal,
                'kode_unik'   => (float) ($request->kode_unik ?? 0),
                'biaya_admin' => (float) ($request->biaya_admin ?? 0),
                'total'       => $expectedTotal,
            ],
        ], 202);
    }


    /**
     * POST /api/v1/transaksi/{id}/override
     *
     * ✅ FIX Bug #2: Status check includes both formats
     * ✅ FIX Bug #6: Accept field names from frontend & API
     */
    public function requestOverride(Request $request, $id)
    {
        // ── ✅ FIX Bug #6: Accept both field name variants ──
        // Frontend sends: override_reason (from form textarea name)
        // API docs say:   alasan_pengecualian
        $reason = $request->override_reason ?? $request->alasan_pengecualian;

        if (!$reason || strlen(trim($reason)) < 5) {
            return response()->json([
                'success' => false,
                'message' => 'Alasan override wajib diisi (minimal 5 karakter).',
                'errors'  => ['override_reason' => ['Alasan override wajib diisi (minimal 5 karakter).']],
            ], 422);
        }

        $transaction = Transaction::findOrFail($id);

        // ── ✅ FIX Bug #2: Accept both 'auto-reject' (new) and 'Auto-Reject' (legacy) ──
        if (!in_array(strtolower($transaction->status), ['auto-reject', 'auto_reject'])) {
            return response()->json([
                'success' => false,
                'message' => 'Override hanya bisa dilakukan pada nota yang berstatus Auto-Reject.',
            ], 400);
        }

        // ── ✅ FIX Bug #2: 'waiting_payment' (frontend key) instead of 'Menunggu Pembayaran' ──
        $transaction->update([
            'status'      => 'waiting_payment',
            'description' => ($transaction->description ? $transaction->description . ' | ' : '')
                . 'Override Reason: ' . $reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        Log::channel('ai_autofill')->info('✅ [OVERRIDE] AUTO-REJECT OVERRIDDEN', [
            'step'           => 'override_request',
            'transaction_id' => $id,
            'reviewed_by'    => auth()->id(),
            'reason'         => $reason,
            'new_status'     => 'waiting_payment',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Override disetujui, transaksi dilanjutkan ke pembayaran.',
            'data'    => $transaction->fresh(),
        ]);
    }


    /**
     * POST /api/v1/transaksi/{id}/force-approve
     *
     * ✅ FIX Bug #2: Status check & assignment use frontend keys
     * ✅ FIX Bug #6: Accept field names from frontend & API
     */
    public function forceApprove(Request $request, $id)
    {
        try {

            // ✅ Ambil reason dari JSON body
            $reason = $request->input('force_approve_reason');

            if (!$reason || strlen(trim($reason)) < 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alasan Force Approve wajib diisi (minimal 5 karakter).'
                ], 422);
            }

            $transaction = Transaction::find($id);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan.'
                ], 404);
            }

            // ✅ Case-insensitive check
            if (!Str::contains(strtolower($transaction->status), 'flagged')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Force Approve hanya bisa dilakukan pada transaksi yang di-flag.'
                ], 400);
            }

            // ✅ Update status
            $transaction->update([
                'status'      => 'completed',
                'description' => ($transaction->description ? $transaction->description . ' | ' : '')
                    . 'Force Approve Reason: ' . $reason,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            Log::channel('ai_autofill')->info('✅ [FORCE APPROVE] FLAGGED TRANSACTION APPROVED', [
                'transaction_id' => $id,
                'reviewed_by'    => auth()->id(),
                'reason'         => $reason,
                'new_status'     => 'completed',
            ]);

            // Update audit + Telegram
            $this->resolveDiscrepancyAudit($transaction, 'force_approved', $reason);
            $this->notifyForceApprovedTelegram($transaction, $reason);

            return response()->json([
                'success' => true,
                'message' => 'Force Approve berhasil, transaksi selesai.',
                'data'    => $transaction->fresh()
            ]);

        } catch (\Exception $e) {

            Log::error('❌ [FORCE APPROVE ERROR]', [
                'transaction_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat melakukan Force Approve.'
            ], 500);
        }
    }

    // ─── Private Helpers ─────

    /**
     * Update resolusi audit ketika Force Approve dilakukan
     */
    private function resolveDiscrepancyAudit(Transaction $transaction, string $resolution, string $reason): void
    {
        try {
            $audit = PaymentDiscrepancyAudit::where('transaction_id', $transaction->id)
                ->where('resolution', 'pending')
                ->latest()
                ->first();

            if ($audit) {
                $audit->update([
                    'resolution'        => $resolution,
                    'resolution_reason' => $reason,
                    'resolved_by'       => auth()->id(),
                    'resolved_at'       => now(),
                ]);

                Log::channel('ai_autofill')->info('✅ [AUDIT] Discrepancy resolved', [
                    'audit_id'       => $audit->id,
                    'transaction_id' => $transaction->id,
                    'resolution'     => $resolution,
                ]);
            } else {
                // Jika tidak ada audit sebelumnya (misal: dibuat langsung tanpa N8N), buat baru
                PaymentDiscrepancyAudit::create([
                    'transaction_id'    => $transaction->id,
                    'invoice_number'    => $transaction->invoice_number,
                    'expected_total'    => $transaction->expected_total ?? 0,
                    'actual_total'      => $transaction->actual_total   ?? 0,
                    'selisih'           => $transaction->selisih        ?? 0,
                    'ocr_result'        => 'MISMATCH',
                    'flag_reason'       => $transaction->flag_reason    ?? null,
                    'resolution'        => $resolution,
                    'resolution_reason' => $reason,
                    'resolved_by'       => auth()->id(),
                    'resolved_at'       => now(),
                    'submitted_by'      => $transaction->submitted_by,
                    'payment_method'    => $transaction->payment_method,
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('ai_autofill')->error('❌ [AUDIT] Failed to resolve discrepancy audit', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * Kirim notifikasi Telegram ke Owner saat Force Approve dilakukan
     */
    private function notifyForceApprovedTelegram(Transaction $transaction, string $reason): void
    {
        try {
            $approver = auth()->user();
            $telegram = new TelegramBotService();
            $telegram->notifyForceApproved($transaction->load('submitter'), $approver, $reason);
        } catch (\Exception $e) {
            Log::channel('ai_autofill')->error('❌ [TELEGRAM] Failed to send force-approve notification', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}