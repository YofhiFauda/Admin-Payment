<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transaction;
use App\Models\PaymentDiscrepancyAudit;
use App\Services\IdGeneratorService;

// 🔔 TELEGRAM: Import TelegramBotService
use App\Services\Telegram\TelegramBotService;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\UserBankAccount;

/**
 * ═══════════════════════════════════════════════════════════════
 *  OcrNotaController — DENGAN INTEGRASI TELEGRAM
 *
 *  🔔 TELEGRAM INTEGRATION POINTS:
 *  1. uploadCash()      → notifyPaymentCash() (dengan tombol)
 *  2. konfirmasiCash()  → (teknisi klik tombol di Telegram)
 *  3. uploadTransfer()  → (n8n callback akan trigger notifikasi)
 *  4. forceApprove()    → notifyForceApproved() + notifyForceApprovedToTechnician()
 * ═══════════════════════════════════════════════════════════════
 */
class OcrNotaController extends Controller
{
    // 🔔 TELEGRAM: Inject service via constructor
    private TelegramBotService $telegram;

    public function __construct(TelegramBotService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * POST /api/v1/nota/upload
     */
    public function uploadNota(Request $request)
    {
        // ── ✅ NEW: Rate Limiting per User (1 upload / 5 detik) ──
        $userId = auth()->id() ?? $request->ip();
        $key    = 'ocr_upload_limit:' . $userId;

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            Log::channel('ocr')->warning('⏳ [OCR LIMIT] Rate limit hit', ['user' => $userId, 'seconds' => $seconds]);
            return response()->json([
                'success' => false,
                'message' => "Terlalu cepat. Harap tunggu {$seconds} detik lagi.",
            ], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 5);
        $validator = Validator::make($request->all(), [
            'foto_nota'        => 'required|file|image|max:10240',
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

        $rateLimiter = app(\App\Services\OCR\GeminiRateLimiter::class);
        try {
            $rateLimiter->acquireSlot($uploadId);
            
            $n8nUrl = trim(config('services.n8n.webhook_url') ?? env('N8N_WEBHOOK'));
            if ($n8nUrl) {
                $response = Http::timeout(30)->post("{$n8nUrl}/webhook/upload-nota", [
                    'upload_id'        => $uploadId,
                    'transaksi_id'     => $transaction->id,
                    'file_url'         => asset("storage/{$path}"),
                    'expected_nominal' => $request->expected_nominal,
                    'payment_method'   => $request->payment_method,
                    'branch_id'        => $request->branch_id,
                    'secret'           => config('services.n8n.secret'),
                    'callback_url'     => url('/api/ai/auto-fill'),
                ]);

                Log::channel('ocr')->info('📤 [N8N WEBHOOK] Upload Nota Triggered', [
                    'upload_id'       => $uploadId,
                    'webhook_url'     => "{$n8nUrl}/webhook/upload-nota",
                    'response_status' => $response->status(),
                ]);

                if ($response->status() === 429) {
                    $rateLimiter->register429(60);
                }
            }
        } catch (\RuntimeException $e) {
            Log::channel('ocr')->warning('⏳ [OCR API] RATE LIMITER THROTTLE', [
                'upload_id' => $uploadId,
                'error'     => $e->getMessage(),
            ]);
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
        $query = Transaction::query()
            ->withExists(['branches as has_branch_with_debt' => function($q) {
                $q->whereHas('debtsAsDebtor', function($sq) {
                    $sq->where('status', 'pending');
                });
            }]);

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
     * POST /api/v1/payment/pengajuan/upload
     * 
     * Upload invoice and payment details for approved Pengajuan.
     * Supports multi sumber dana (multiple source branches) with
     * automatic inter-branch debt calculation.
     */
    public function uploadPengajuanInvoice(Request $request)
    {
        // ═══════════════════════════════════════════════════════════
        // PREPARE / SANITIZE INPUTS BEFORE VALIDATION
        // Remove formatting dots from nominal inputs sent from frontend
        // ═══════════════════════════════════════════════════════════
        $input = $request->all();
        $fieldsToUnformat = ['diskon_pengiriman', 'ongkir', 'dpp_lainnya', 'tax_amount', 'biaya_layanan_1', 'biaya_layanan_2', 'voucher_diskon'];
        foreach ($fieldsToUnformat as $field) {
            if (!empty($input[$field])) {
                $input[$field] = (int) preg_replace('/\D/', '', (string) $input[$field]);
            }
        }
        if (isset($input['sumber_dana']) && is_array($input['sumber_dana'])) {
            foreach ($input['sumber_dana'] as $key => $sd) {
                if (isset($sd['amount']) && $sd['amount'] !== '') {
                    $input['sumber_dana'][$key]['amount'] = (int) preg_replace('/\D/', '', (string) $sd['amount']);
                }
            }
        }
        $request->replace($input);

        $validator = Validator::make($request->all(), [
            'invoice_file'       => 'required|file|image|max:5120',
            'transaksi_id'       => 'required|string',
            'diskon_pengiriman'  => 'nullable|numeric|min:0',
            'ongkir'             => 'nullable|numeric|min:0',
            'dpp_lainnya'        => 'nullable|numeric|min:0',
            'tax_amount'         => 'nullable|numeric|min:0',
            'biaya_layanan_1'    => 'nullable|numeric|min:0',
            'biaya_layanan_2'    => 'nullable|numeric|min:0',
            'voucher_diskon'     => 'nullable|numeric|min:0',
            'sumber_dana'        => 'required|array|min:1',
            'sumber_dana.*.branch_id' => 'required|integer|exists:branches,id',
            'sumber_dana.*.amount'    => 'required|numeric|min:0',
            'catatan'            => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $transaction = Transaction::with('branches')->findOrFail($request->transaksi_id);

        if (!$transaction->isPengajuan()) {
            return response()->json([
                'success' => false,
                'message' => 'Bukan merupakan transaksi Pengajuan.',
            ], 400);
        }

        if ($transaction->status !== 'waiting_payment') {
            return response()->json([
                'success' => false,
                'message' => 'Status transaksi tidak valid untuk upload invoice: ' . $transaction->status_label,
            ], 400);
        }

        // ═══════════════════════════════════════════════════════════
        //  Validate: Total sumber dana harus = total transaksi (Final)
        // ═══════════════════════════════════════════════════════════
        $sumberDanaList = collect($request->sumber_dana);
        $totalDana = (int) $sumberDanaList->sum('amount');
        
        // Calculate Final Amount: base + ongkir + fees - discounts
        $baseAmount = (int) $transaction->amount;
        $ongkir     = (int) ($request->ongkir ?? 0);
        $dppLainnya = (int) ($request->dpp_lainnya ?? 0);
        $layanan1   = (int) ($request->biaya_layanan_1 ?? 0);
        $taxAmount  = (int) ($request->tax_amount ?? 0);
        $layanan2   = (int) ($request->biaya_layanan_2 ?? 0);
        $diskonOngkir = (int) ($request->diskon_pengiriman ?? 0);
        $voucher      = (int) ($request->voucher_diskon ?? 0);

        $totalTransaksi = $baseAmount + $ongkir + $dppLainnya + $taxAmount + $layanan1 + $layanan2 - $diskonOngkir - $voucher;

        if (abs($totalDana - $totalTransaksi) > 1) {
            return response()->json([
                'success' => false,
                'message' => "Total sumber dana (Rp " . number_format($totalDana, 0, ',', '.') . ") harus sama dengan total tagihan akhir (Rp " . number_format($totalTransaksi, 0, ',', '.') . "). Pastikan rincian biaya & diskon sudah benar.",
            ], 422);
        }

        // ═══════════════════════════════════════════════════════════
        //  Validate: Sumber dana harus dari cabang yang ada di pembagian
        // ═══════════════════════════════════════════════════════════
        $transactionBranchIds = $transaction->branches->pluck('id')->toArray();
        foreach ($sumberDanaList as $sd) {
            if (!in_array($sd['branch_id'], $transactionBranchIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sumber dana harus dari cabang yang ada dalam pembagian biaya pengajuan.',
                ], 422);
            }
        }

        // Store the file
        $path = $request->file('invoice_file')->store('invoices', 'public');

        // ═══════════════════════════════════════════════════════════
        //  1. Persist Transaction Changes (Amount, Fees, Proof) First
        // ═══════════════════════════════════════════════════════════
        
        // Build sumber_dana_data JSON
        $sumberDanaData = $sumberDanaList->map(fn($sd) => [
            'branch_id' => (int) $sd['branch_id'],
            'amount'    => (int) $sd['amount'],
        ])->values()->toArray();

        // Update transaction model with the final invoice values
        // We do this first so calculateAndCreateDebts sees the FINAL amount.
        $transaction->update([
            'invoice_file_path'     => $path,
            'amount'                => $totalTransaksi, 
            'diskon_pengiriman'     => $request->diskon_pengiriman ?? 0,
            'ongkir'                => $request->ongkir ?? 0,
            'dpp_lainnya'           => $request->dpp_lainnya ?? 0,
            'tax_amount'            => $request->tax_amount ?? 0,
            'biaya_layanan_1'       => $request->biaya_layanan_1 ?? 0,
            'biaya_layanan_2'       => $request->biaya_layanan_2 ?? 0,
            'voucher_diskon'        => $request->voucher_diskon ?? 0,
            'sumber_dana_branch_id' => $sumberDanaData[0]['branch_id'] ?? null,
            'sumber_dana_data'      => $sumberDanaData,
            'paid_by'               => auth()->id(),
            'paid_at'               => now(),
            'description'           => ($transaction->description ? $transaction->description . ' | ' : '') . $request->catatan,
        ]);

        // 💰 DEBT CALCULATION — Hitung hutang berdasarkan nominal FINAL
        $this->calculateAndCreateDebts($transaction->fresh(), $sumberDanaData);

        // Check if there are still pending debts after calculation
        $hasPendingDebts = \App\Models\BranchDebt::where('transaction_id', $transaction->id)
            ->where('status', 'pending')
            ->exists();

        // ═══════════════════════════════════════════════════════════
        //  2. Determine Final Status & Broadcast
        // ═══════════════════════════════════════════════════════════
        $finalStatus = $hasPendingDebts ? 'waiting_payment' : 'completed';
        $transaction->update(['status' => $finalStatus]);
        
        $transaction = $transaction->fresh();

        // ═══════════════════════════════════════════════════════════
        //  Update Branch Allocations to reflect shared adjustments
        // ═══════════════════════════════════════════════════════════
        foreach ($transaction->branches as $branch) {
            $newAlloc = (int) round(($totalTransaksi * $branch->pivot->allocation_percent) / 100);
            $transaction->branches()->updateExistingPivot($branch->id, [
                'allocation_amount' => $newAlloc
            ]);
        }

        // Broadcast update
        broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));

        // Log Activity
        $sumberNames = \App\Models\Branch::whereIn('id', $sumberDanaList->pluck('branch_id'))->pluck('name')->join(', ');
        $logDescription = "Mengunggah Invoice untuk Pengajuan " . $transaction->invoice_number . " (" . ($finalStatus === 'completed' ? 'Selesai' : 'Pending Hutang') . "). Sumber Dana: " . $sumberNames;

        \App\Models\ActivityLog::create([
            'user_id'        => auth()->id() ?? \App\Models\User::where('role', 'admin')->first()->id,
            'action'         => 'upload_invoice',
            'transaction_id' => $transaction->id,
            'target_id'      => $transaction->invoice_number,
            'description'    => $logDescription,
        ]);

        // 🔔 Notifikasi Telegram ke Teknisi bahwa invoice sudah diupload dan transaksi selesai
        try {
            if ($transaction->submitter) {
                $this->telegram->notifyForceApprovedToTechnician($transaction);
            }
        } catch (\Exception $e) {
            Log::error('[PENGAJUAN] Failed to send telegram completion notification', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Invoice berhasil diunggah dan transaksi diperbarui menjadi Selesai.',
            'transaksi_id'  => $transaction->id,
            'status'        => $finalStatus,
            'transaction'   => $transaction->fresh()->toSearchArray(),
        ], 200);
    }

    /**
     * ═══════════════════════════════════════════════════════════════
     *  Calculate and create inter-branch debt records
     * 
     *  Logika:
     *  1. Setiap cabang punya allocation_amount dari pembagian biaya
     *  2. Sumber dana memasukkan jumlah yang dibayarkan (paid_amount)
     *  3. Jika paid_amount > allocation → cabang ini kreditor (excess)
     *  4. Jika paid_amount < allocation → cabang ini debtor (deficit)
     *  5. Cabang bukan sumber dana → full debtor (allocation penuh)
     *  6. Hutang didistribusikan ke kreditor proporsional berdasarkan excess
     * ═══════════════════════════════════════════════════════════════
     */
    private function calculateAndCreateDebts(Transaction $transaction, array $sumberDanaData): void
    {
        // Delete existing debts for this transaction (in case of re-upload)
        \App\Models\BranchDebt::where('transaction_id', $transaction->id)->delete();

        $branches = $transaction->branches;
        if ($branches->isEmpty()) return;

        // Map sumber dana: branch_id => paid_amount
        $paidMap = collect($sumberDanaData)->keyBy('branch_id')->map(fn($sd) => (int) $sd['amount']);

        // Calculate each branch's excess or deficit
        $creditors = []; // branch_id => excess_amount
        $debtors = [];   // branch_id => debt_amount

        foreach ($branches as $branch) {
            // Always recalculate allocation based on final amount and percentage
            // This ensures adjustments (ongkir, etc.) are shared among all branches
            $allocation = (int) round(($transaction->amount * $branch->pivot->allocation_percent) / 100);

            $paid = $paidMap->get($branch->id, 0); // 0 jika bukan sumber dana

            if ($paid > $allocation) {
                // Cabang ini kreditor (membayar lebih dari alokasinya)
                $creditors[$branch->id] = $paid - $allocation;
            } elseif ($paid < $allocation) {
                // Cabang ini debtor (membayar kurang dari alokasinya atau tidak membayar)
                $debtors[$branch->id] = $allocation - $paid;
            }
            // Jika paid == allocation → tidak ada hutang untuk cabang ini
        }

        // Jika tidak ada kreditor atau debtor, selesai
        if (empty($creditors) || empty($debtors)) return;

        // Total excess dari semua kreditor
        $totalExcess = array_sum($creditors);

        // Create debt records: distribute each debtor's debt proportionally to creditors
        foreach ($debtors as $debtorId => $debtAmount) {
            foreach ($creditors as $creditorId => $creditorExcess) {
                // Proporsi hutang ke kreditor ini = (excess kreditor / total excess) * debt amount
                $proportion = $creditorExcess / $totalExcess;
                $debtToCreditor = (int) round($debtAmount * $proportion);

                if ($debtToCreditor > 0) {
                    \App\Models\BranchDebt::create([
                        'transaction_id'    => $transaction->id,
                        'debtor_branch_id'  => $debtorId,
                        'creditor_branch_id'=> $creditorId,
                        'amount'            => $debtToCreditor,
                        'status'            => 'pending',
                    ]);
                }
            }
        }

        Log::info('[DEBT] Branch debts calculated', [
            'transaction_id' => $transaction->id,
            'creditors'      => $creditors,
            'debtors'        => $debtors,
            'total_excess'   => $totalExcess,
        ]);
    }

    /**
     * POST /api/v1/payment/cash/upload
     * 
     * 🔔 TELEGRAM POINT #1: Kirim notifikasi CASH dengan tombol ke teknisi
     */
    public function uploadCash(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foto_penyerahan' => 'nullable|file|image|max:5120',
            'file'            => 'nullable|file|image|max:5120',
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
            ->with('submitter')  // 🔔 TELEGRAM: Load teknisi
            ->firstOrFail();

        // 🛡️ VALIDASI: Cek pendaftaran Telegram teknisi (Lewati untuk Gudang/Internal)
        if ($transaction->type !== 'gudang' && (!$transaction->submitter || !$transaction->submitter->telegram_chat_id)) {
            return response()->json([
                'success' => false,
                'message' => '❌ Gagal: Teknisi (' . ($transaction->submitter->name ?? 'Unknown') . ') BELUM mendaftarkan akun Telegram. Pembayaran CASH tidak dapat diproses sampai teknisi mendaftar via bot.',
            ], 422);
        }

        if ($transaction->status !== 'waiting_payment') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi belum disetujui atau sudah dibayar. Status saat ini: ' . $transaction->status_label,
            ], 400);
        }

        $fileInput = $request->hasFile('foto_penyerahan') ? 'foto_penyerahan' : 'file';
        $path = $request->hasFile($fileInput) 
            ? $request->file($fileInput)->store('payments/cash', 'public') 
            : null;

        $isGudang = $transaction->type === 'gudang';

        $transaction->update([
            'foto_penyerahan' => $path,
            'status'          => $isGudang ? 'completed' : 'pending_technician',
            'paid_by'         => auth()->id(),
            'paid_at'         => now(),
            'description'     => $request->catatan,
        ]);

        // 🔔 Broadcast update untuk UI teknisi
        broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));

        \App\Models\ActivityLog::create([
            'user_id'        => auth()->id() ?? \App\Models\User::where('role', 'admin')->first()->id,
            'action'         => 'upload_payment',
            'transaction_id' => $transaction->id,
            'target_id'      => $transaction->invoice_number,
            'description'    => "Mengunggah bukti penyerahan Cash" . ($request->catatan ? ". Catatan: " . $request->catatan : ""),
        ]);

        // 🔔 Kirim notifikasi sistem ke Teknisi
        if ($transaction->submitter) {
            $transaction->submitter->notify(new \App\Notifications\TransactionStatusNotification($transaction, 'pending_technician'));
        }

        Log::channel('ai_autofill')->info('📤 [UPLOAD CASH] PAYMENT PROOF UPLOADED', [
            'step'           => 'cash_upload',
            'upload_id'      => $request->upload_id,
            'transaction_id' => $transaction->id,
            'file_path'      => $path,
        ]);

        // ═══════════════════════════════════════════════════════════
        //  🔔 TELEGRAM #1: KIRIM NOTIFIKASI CASH KE TEKNISI
        //  (Lewati untuk Gudang/Internal)
        // ═══════════════════════════════════════════════════════════
        if ($transaction->type !== 'gudang') {
            try {
                $this->telegram->notifyPaymentCash($transaction);
                
                Log::channel('ai_autofill')->info('✅ [TELEGRAM] Cash notification sent', [
                    'transaction_id' => $transaction->id,
                    'teknisi_id'     => $transaction->submitter?->id,
                ]);
            } catch (\Exception $e) {
                Log::channel('ai_autofill')->error('❌ [TELEGRAM] Failed to send cash notification', [
                    'transaction_id' => $transaction->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        // // n8n webhook (optional - jika masih ada workflow lama)
        // $n8nUrl = trim(config('services.n8n.webhook_url') ?? env('N8N_WEBHOOK'));
        // if ($n8nUrl) {
        //     try {
        //         /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        //         $storage = Storage::disk('public');

        //         $response = Http::timeout(30)->post("{$n8nUrl}/webhook/payment/cash/upload", [
        //             'upload_id'    => $request->upload_id,
        //             'transaksi_id' => $transaction->id,
        //             'teknisi_id'   => $request->teknisi_id,
        //             'foto_url'     => $storage->url($path),
        //             'catatan'      => $request->catatan,
        //             'secret'       => config('services.n8n.secret'),
        //             'callback_url' => url('/api/payment/verify'),
        //         ]);

        //         if ($response->successful()) {
        //             Log::channel('ai_autofill')->info('✅ [UPLOAD CASH] N8N WEBHOOK SUCCESS', [
        //                 'step'            => 'cash_n8n_trigger',
        //                 'upload_id'       => $request->upload_id,
        //                 'webhook_url'     => "{$n8nUrl}/webhook/payment/cash/upload",
        //                 'response_status' => $response->status(),
        //             ]);
        //         } else {
        //             Log::channel('ai_autofill')->warning('⚠️ [UPLOAD CASH] N8N WEBHOOK FAILED', [
        //                 'step'            => 'cash_n8n_error',
        //                 'upload_id'       => $request->upload_id,
        //                 'response_status' => $response->status(),
        //             ]);
        //         }
        //     } catch (\Exception $e) {
        //         Log::channel('ai_autofill')->error('❌ [UPLOAD CASH] N8N WEBHOOK EXCEPTION', [
        //             'step'      => 'cash_n8n_exception',
        //             'upload_id' => $request->upload_id,
        //             'error'     => $e->getMessage(),
        //         ]);
        //     }
        // }

        return response()->json([
            'success'       => true,
            'message'       => $isGudang ? 'Bukti penyerahan diterima. Transaksi Selesai.' : 'Foto penyerahan diterima. Menunggu konfirmasi teknisi.',
            'upload_id'     => $transaction->upload_id,
            'transaksi_id'  => $transaction->id,
            'pembayaran_id' => $transaction->id,
            'status'        => $isGudang ? 'completed' : 'pending_technician',
            'status_label'  => $isGudang ? 'Selesai' : 'Menunggu Konfirmasi Teknisi',
        ], 202);
    }

    /**
     * POST /api/v1/payment/cash/konfirmasi
     * 
     * 🔔 TELEGRAM POINT #2: Endpoint ini di-trigger dari TelegramWebhookController
     *                       saat teknisi klik tombol "✅ Terima" di Telegram
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

        // 🛡️ Prevent double-clicking / multiple confirmations
        if (in_array($transaction->status, ['completed', 'approved', 'Ditolak Teknisi'])) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini sudah dikonfirmasi sebelumnya.'
            ], 400);
        }

        // Determine final status based on amount and action
        if ($request->action === 'terima') {
            $isRequiresOwner = $transaction->effective_amount >= 1000000;
            $status = $isRequiresOwner ? 'approved' : 'completed';
        } else {
            $status = 'Ditolak Teknisi';
        }
        $now = now();

        $transaction->update([
            'status'         => $status,
            'konfirmasi_by'  => $request->teknisi_id,
            'konfirmasi_at'  => $now,
            'description'    => $request->catatan
                ? $transaction->description . ' | Catatan Teknisi: ' . $request->catatan
                : $transaction->description,
        ]);

        // 🔔 Broadcast update untuk UI
        broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));

        if ($request->action === 'tolak') {
            \App\Models\ActivityLog::create([
                'user_id'        => $request->teknisi_id,
                'action'         => 'reject_payment',
                'transaction_id' => $transaction->id,
                'target_id'      => $transaction->invoice_number,
                'description'    => "Teknisi menolak penerimaan uang Cash" . ($request->catatan ? " dengan alasan: " . $request->catatan : ""),
            ]);
        }

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
     * 🔔 TELEGRAM POINT #3: n8n akan callback ke PaymentVerificationController
     *                       yang akan trigger notifikasi transfer
     */
    public function uploadTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bukti_transfer'   => 'nullable|file|image|max:5120',
            'file'             => 'nullable|file|image|max:5120',
            'upload_id'        => 'required|string',
            'transaksi_id'     => 'required|string',
            'expected_nominal' => 'required|numeric',
            'kode_unik'        => 'nullable|numeric',
            'biaya_admin'      => 'nullable|numeric',
            'rekening_tujuan'  => 'nullable|string',
            'nama_bank_tujuan' => 'nullable|string',
            'rekening_bank'    => 'nullable|string',
            'rekening_nomor'   => 'nullable|numeric|digits_between:5,30',
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

        // 🛡️ VALIDASI: Cek pendaftaran Telegram teknisi (Lewati untuk Gudang/Internal)
        if ($transaction->type !== 'gudang' && (!$transaction->submitter || !$transaction->submitter->telegram_chat_id)) {
            return response()->json([
                'success' => false,
                'message' => '❌ Gagal: Teknisi (' . ($transaction->submitter->name ?? 'Unknown') . ') BELUM mendaftarkan Telegram. Bukti transfer tidak dapat diproses sampai teknisi mendaftar via bot.',
            ], 422);
        }

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

        // Auto-update Technician Profile (New Implementation)
        if ($transaction->payment_method === 'transfer_teknisi' && $transaction->submitter) {
            $user = $transaction->submitter;
            
            if ($request->filled('rekening_bank') && $request->filled('rekening_nomor')) {
                $bankName      = strtoupper($request->rekening_bank);
                $accountNumber = $request->rekening_nomor;
                $accountName   = strtoupper($request->rekening_nama ?? $user->name);

                // Check if this account already exists for the user
                $exists = UserBankAccount::where('user_id', $user->id)
                    ->where('account_number', $accountNumber)
                    ->exists();

                if (!$exists) {
                    UserBankAccount::create([
                        'user_id'        => $user->id,
                        'bank_name'      => $bankName,
                        'account_number' => $accountNumber,
                        'account_name'   => $accountName,
                    ]);

                    Log::channel('ai_autofill')->info('🏦 [USER BANK] New account saved from upload', [
                        'user_id' => $user->id,
                        'bank'    => $bankName,
                    ]);
                }
            }
        }

        $expectedTotal = $request->expected_nominal + ($request->kode_unik ?? 0) + ($request->biaya_admin ?? 0);

        $isGudang = $transaction->type === 'gudang';

        $transaction->update([
            'bukti_transfer' => $path,
            'status'         => $isGudang ? 'completed' : 'Sedang Diverifikasi AI',
            'expected_total' => $expectedTotal,
            'paid_by'        => auth()->id(),
            'paid_at'        => now(),
        ]);

        \App\Models\ActivityLog::create([
            'user_id'        => auth()->id() ?? \App\Models\User::where('role', 'admin')->first()->id,
            'action'         => 'upload_payment',
            'transaction_id' => $transaction->id,
            'target_id'      => $transaction->invoice_number,
            'description'    => "Mengunggah bukti Transfer. Total Transfer: Rp " . number_format($expectedTotal, 0, ',', '.'),
        ]);

        Log::channel('ai_autofill')->info('📤 [UPLOAD TRANSFER] PAYMENT PROOF UPLOADED', [
            'step'           => 'transfer_upload',
            'upload_id'      => $request->upload_id,
            'transaction_id' => $transaction->id,
            'file_path'      => $path,
            'expected_total' => $expectedTotal,
        ]);

        // ═══════════════════════════════════════════════════════════
        //  🔔 TELEGRAM #3: n8n OCR bukti transfer (Lewati untuk Gudang)
        // ═══════════════════════════════════════════════════════════
        if (!$isGudang) {
            $n8nUrl = trim(config('services.n8n.webhook_url') ?? env('N8N_WEBHOOK'));
            if ($n8nUrl) {
                try {
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
                    $storage  = Storage::disk('public');
                    $fileContents = $storage->get($path);
                    $fileName     = basename($path);
                    $mimeType     = $storage->mimeType($path) ?: 'image/jpeg';

                    // ⚠️ PENTING: N8N webhook mengharapkan binary file (multipart/form-data),
                    // bukan JSON body. Gunakan Http::attach() agar gambar terkirim sebagai binary
                    // sehingga N8N bisa membacanya via $input.first().binary.
                    $response = Http::timeout(60)
                        ->attach('data', $fileContents, $fileName, ['Content-Type' => $mimeType])
                        ->post("{$n8nUrl}/webhook/payment/transfer/upload", [
                            'upload_id'        => $request->upload_id,
                            'transaksi_id'     => $transaction->id,
                            'expected_nominal' => $request->expected_nominal,
                            'kode_unik'        => $request->kode_unik ?? 0,
                            'biaya_admin'      => $request->biaya_admin ?? 0,
                            'rekening_tujuan'  => $request->rekening_tujuan,
                            'nama_bank_tujuan' => $request->nama_bank_tujuan,
                            'secret'           => config('services.n8n.secret'),
                            'callback_url'     => url('/api/payment/verify'),
                        ]);

                    if ($response->successful()) {
                        Log::channel('ai_autofill')->info('✅ [UPLOAD TRANSFER] N8N WEBHOOK SUCCESS', [
                            'step'            => 'transfer_n8n_trigger',
                            'upload_id'       => $request->upload_id,
                            'webhook_url'     => "{$n8nUrl}/webhook/payment/transfer/upload",
                            'response_status' => $response->status(),
                            'file_sent'       => $fileName,
                        ]);
                    } else {
                        Log::channel('ai_autofill')->warning('⚠️ [UPLOAD TRANSFER] N8N WEBHOOK FAILED', [
                            'step'            => 'transfer_n8n_error',
                            'upload_id'       => $request->upload_id,
                            'response_status' => $response->status(),
                            'response_body'   => $response->body(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::channel('ai_autofill')->error('❌ [UPLOAD TRANSFER] N8N WEBHOOK EXCEPTION', [
                        'step'      => 'transfer_n8n_exception',
                        'upload_id' => $request->upload_id,
                        'error'     => $e->getMessage(),
                    ]);

                    $transaction->update([
                        'status' => 'flagged',
                    ]);
                }
            }
        }

        return response()->json([
            'success'        => true,
            'message'        => $isGudang ? 'Bukti transfer diterima. Transaksi Selesai.' : 'Bukti transfer diterima. AI sedang memverifikasi nominal.',
            'upload_id'      => $transaction->upload_id,
            'transaksi_id'   => $transaction->id,
            'pembayaran_id'  => $transaction->id,
            'expected_total' => $expectedTotal,
            'status'         => $isGudang ? 'completed' : 'Sedang Diverifikasi AI',
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
     */
    public function requestOverride(Request $request, $id)
    {
        $reason = $request->override_reason ?? $request->alasan_pengecualian;

        if (!$reason || strlen(trim($reason)) < 5) {
            return response()->json([
                'success' => false,
                'message' => 'Alasan override wajib diisi (minimal 5 karakter).',
                'errors'  => ['override_reason' => ['Alasan override wajib diisi (minimal 5 karakter).']],
            ], 422);
        }

        $transaction = Transaction::findOrFail($id);

        if (!in_array(strtolower($transaction->status), ['auto-reject', 'auto_reject'])) {
            return response()->json([
                'success' => false,
                'message' => 'Override hanya bisa dilakukan pada nota yang berstatus Auto-Reject.',
            ], 400);
        }

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
     * 🔔 TELEGRAM POINT #4: Kirim notifikasi force approve ke owner + teknisi
     */
    public function forceApprove(Request $request, $id)
    {
        try {
            $reason = $request->input('force_approve_reason');

            if (!$reason || strlen(trim($reason)) < 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alasan Force Approve wajib diisi (minimal 5 karakter).'
                ], 422);
            }

            $transaction = Transaction::with('submitter')->find($id);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan.'
                ], 404);
            }

            if (!Str::contains(strtolower($transaction->status), 'flagged')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Force Approve hanya bisa dilakukan pada transaksi yang di-flag.'
                ], 400);
            }

            $transaction->update([
                'status'      => 'completed',
                'description' => ($transaction->description ? $transaction->description . ' | ' : '')
                    . 'Force Approve Reason: ' . $reason,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            // 🔔 Broadcast update untuk UI
            broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));

            \App\Models\ActivityLog::create([
                'user_id'        => auth()->id(),
                'action'         => 'force_approve',
                'transaction_id' => $transaction->id,
                'target_id'      => $transaction->invoice_number,
                'description'    => "Force Approve dari Flagged (Selisih) menjadi Selesai. Alasan: " . $reason,
            ]);

            // 🔔 Notifikasi Sistem untuk Teknisi
            if ($transaction->submitter) {
                $transaction->submitter->notify(new \App\Notifications\TransactionStatusNotification($transaction, 'force_approved'));
            }

            Log::channel('ai_autofill')->info('✅ [FORCE APPROVE] FLAGGED TRANSACTION APPROVED', [
                'transaction_id' => $id,
                'reviewed_by'    => auth()->id(),
                'reason'         => $reason,
                'new_status'     => 'completed',
            ]);

            // Update audit
            $this->resolveDiscrepancyAudit($transaction, 'force_approved', $reason);
            
            // ═══════════════════════════════════════════════════════════
            //  🔔 TELEGRAM #4: KIRIM NOTIFIKASI FORCE APPROVE
            //  1. Ke SEMUA OWNER → konfirmasi force approve dilakukan
            //  2. Ke TEKNISI terkait → transaksi disetujui owner
            // ═══════════════════════════════════════════════════════════
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
     * 🔔 TELEGRAM: Kirim notifikasi Force Approve ke Owner + Teknisi
     */
    private function notifyForceApprovedTelegram(Transaction $transaction, string $reason): void
    {
        try {
            $approver = auth()->user();
            
            // 1. Notifikasi ke SEMUA OWNER (konfirmasi)
            $this->telegram->notifyForceApproved($transaction, $approver, $reason);
            
            // 2. Notifikasi ke TEKNISI terkait (transaksi selesai)
            $this->telegram->notifyForceApprovedToTechnician($transaction);
            
            Log::channel('ai_autofill')->info('✅ [TELEGRAM] Force approve notifications sent', [
                'transaction_id' => $transaction->id,
                'approver_id'    => $approver->id,
            ]);
        } catch (\Exception $e) {
            Log::channel('ai_autofill')->error('❌ [TELEGRAM] Failed to send force-approve notification', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}