<?php

namespace App\Http\Controllers;

use App\Jobs\OcrProcessingJob;
use App\Models\Branch;
use App\Models\Transaction;
use App\Services\IdGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class RembushController extends Controller
{
    // Per-user upload throttle (selain rate limit di Nginx)
    private const MAX_UPLOADS_PER_MIN = 5;

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  UPLOAD — Handle nota upload + dispatch OCR Job
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function processUpload(Request $request)
    {
        // 📝 LOG: Start Upload Process
        Log::channel('ocr')->info('🚀 [OCR FLOW] START REMBUSH UPLOAD', [
            'step' => '1_upload',
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // ① Per-user upload throttle via Redis
        $throttleKey = 'upload:user:' . Auth::id();
        $uploadCount = (int) Redis::incr($throttleKey);
        if ($uploadCount === 1) {
            Redis::expire($throttleKey, 60);
        }

        if ($uploadCount > self::MAX_UPLOADS_PER_MIN) {
            $ttl = Redis::ttl($throttleKey);
            
            // 📝 LOG: Upload Throttled
            Log::channel('ocr')->warning('⚠️ [OCR FLOW] UPLOAD THROTTLED', [
                'step' => '1_upload_throttled',
                'user_id' => Auth::id(),
                'upload_count' => $uploadCount,
                'ttl_seconds' => $ttl,
            ]);
            
            return back()->withErrors([
                'error' => "Terlalu banyak upload. Tunggu {$ttl} detik."
            ]);
        }

        // ② Validasi file
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:1024'
        ]);

        $file     = $request->file('file');

        // Generate sequence ONCE — both upload_id & invoice_number will share this number
        $seq      = IdGeneratorService::nextSequence();
        $uploadId = IdGeneratorService::buildUploadId($seq);

        // 📝 LOG: Upload ID Generated
        Log::channel('ocr')->info('📄 [OCR FLOW] UPLOAD ID GENERATED', [
            'step' => '1_upload_id',
            'upload_id' => $uploadId,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
            'file_size_kb' => round($file->getSize() / 1024, 2),
        ]);

        // ③ Simpan file ke temp storage
        $extension   = $file->getClientOriginalExtension();
        $fileName    = $uploadId . '.' . $extension;
        $storagePath = 'temp-uploads/' . $fileName;

        try {
            Storage::disk('public')->put($storagePath, file_get_contents($file));
            
            // 📝 LOG: File Saved to Temp Storage
            Log::channel('ocr')->info('💾 [OCR FLOW] FILE SAVED TO TEMP', [
                'step' => '1_file_saved',
                'upload_id' => $uploadId,
                'storage_path' => $storagePath,
                'full_path' => storage_path('app/public/' . $storagePath),
                'disk' => 'public',
            ]);
        } catch (\Exception $e) {
            // 📝 LOG: File Save Failed
            Log::channel('ocr')->error('❌ [OCR FLOW] FILE SAVE FAILED', [
                'step' => '1_file_save_error',
                'upload_id' => $uploadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Gagal menyimpan file']);
        }

        // ④ Simpan base64 & mime di session
        $base64 = base64_encode(file_get_contents($file));
        $mime   = $file->getMimeType();

        session([
            'upload_id'          => $uploadId,
            'upload_seq'         => $seq,          // ← shared sequence number
            'upload_file_path'   => $storagePath,
            'upload_file_base64' => $base64,
            'upload_file_mime'   => $mime,
        ]);

        // 📝 LOG: Session Data Stored
        Log::channel('ocr')->info('📦 [OCR FLOW] SESSION DATA STORED', [
            'step' => '1_session',
            'upload_id' => $uploadId,
            'session_keys' => ['upload_id', 'upload_file_path', 'upload_file_base64', 'upload_file_mime'],
        ]);

        // 📝 LOG: Upload Complete - Redirect to Form
        Log::channel('ocr')->info('✅ [OCR FLOW] UPLOAD COMPLETE - REDIRECT TO FORM', [
            'step'       => '1_complete',
            'upload_id'  => $uploadId,
            'next_route' => 'rembush.form',
        ]);

        // ✅ NOTE: OCR Job di-dispatch di store() setelah transaksi dibuat dan file dipindah ke permanent.
        // Dispatch di sini (saat upload) menyebabkan race condition: transaction_id=null & file masih di temp.

        return redirect()->route('rembush.form');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  LOADING — Wait for OCR (tidak berubah)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function loading()
    {
        $uploadId = session('upload_id');

        if (!$uploadId) {
            // 📝 LOG: Loading Page - No Upload ID
            Log::channel('ocr')->warning('⚠️ [OCR FLOW] LOADING PAGE - NO UPLOAD ID', [
                'step' => '2_loading_error',
                'user_id' => Auth::id(),
            ]);
            return redirect()->route('transactions.create');
        }

        // 📝 LOG: Loading Page Accessed
        Log::channel('ocr')->info('⏳ [OCR FLOW] LOADING PAGE ACCESSED', [
            'step' => '2_loading',
            'upload_id' => $uploadId,
            'user_id' => Auth::id(),
        ]);

        return view('transactions.loading', compact('uploadId'));
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  FORM — Rembush form after OCR (tidak berubah)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function showForm()
    {
        $uploadId = session('upload_id');
        $base64   = session('upload_file_base64');
        $mime     = session('upload_file_mime');

                // 📝 LOG: Form Page Accessed
        Log::channel('ocr')->info('📝 [OCR FLOW] FORM PAGE ACCESSED', [
            'step' => '3_form',
            'upload_id' => $uploadId,
            'user_id' => Auth::id(),
            'has_base64' => !empty($base64),
            'mime_type' => $mime,
        ]);

        // Jika ada session upload, coba load AI cache
        // ✅ Coba load AI cache jika ada upload
        $aiData = null;
        $cacheHit = false;
        if ($uploadId && $base64) {
            $cacheKey = "ai_autofill:{$uploadId}";
            $aiData = Cache::get($cacheKey);
            $cacheHit = $aiData !== null;
            
            // 📝 LOG: AI Cache Check
            Log::channel('ai_autofill')->info($cacheHit ? '🎯 [AI CACHE] CACHE HIT' : '❌ [AI CACHE] CACHE MISS', [
                'step' => '3_form_cache',
                'upload_id' => $uploadId,
                'cache_key' => $cacheKey,
                'cache_hit' => $cacheHit,
                'ai_status' => $aiData['status'] ?? null,
                'confidence' => $aiData['confidence'] ?? null,
            ]);
            
            if ($aiData) {
                session(['ai_data' => $aiData]);
            }
        }
 
        $branches = Branch::all();

        return view('transactions.form', compact(
            'branches', 'base64', 'mime', 'uploadId', 'aiData'
        ));
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  STORE — Save Rembush Transaction (tidak berubah)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer'       => 'nullable|string|max:255',
            'category'       => 'required|string|in:' . implode(',', array_keys(Transaction::CATEGORIES)),
            'amount'         => 'nullable|numeric|min:0',
            'description'    => 'nullable|string|max:2000',
            'payment_method' => 'required|string|in:' . implode(',', array_keys(Transaction::PAYMENT_METHODS)),
            'items'          => 'nullable|array',
            'date'           => 'nullable|date',
            'branches'       => 'nullable|array',
            'branches.*.branch_id'           => 'required_with:branches|exists:branches,id',
            'branches.*.allocation_percent'  => 'required_with:branches|numeric|min:0|max:100',
            'branches.*.allocation_amount'   => 'nullable|numeric|min:0',
            // Bank details for transfer_penjual
            'bank_name'      => 'nullable|string|max:255',
            'account_name'   => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
        ]);

        // Validasi branch allocation
        if ($request->branches && count($request->branches) > 0) {
            $branchIds = collect($request->branches)->pluck('branch_id');
            if ($branchIds->count() !== $branchIds->unique()->count()) {
                return back()->withErrors(['branches' => 'Cabang tidak boleh duplikat.'])->withInput();
            }
            $totalPercent = collect($request->branches)->sum('allocation_percent');
            if (abs($totalPercent - 100) > 1) {
                return back()->withErrors(['branches' => 'Total alokasi harus 100%.'])->withInput();
            }
        }

        // Validate transfer_penjual fields
        if ($request->payment_method === 'transfer_penjual') {
            if (!$request->bank_name || !$request->account_name || !$request->account_number) {
                 return back()->withErrors(['bank_details' => 'Nama Bank, Nama Rekening, dan Nomor Rekening wajib diisi untuk Transfer ke Penjual.'])->withInput();
            }
        }

        $specs = [];
        if ($request->payment_method === 'transfer_penjual') {
            $specs = [
                'bank_name' => $request->bank_name,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
            ];
        }

        $uploadId       = session('upload_id') ?? IdGeneratorService::buildUploadId(IdGeneratorService::nextSequence());
        $uploadFilePath = session('upload_file_path');
        // Derive invoice number from the SAME sequence used for upload_id
        $seq            = session('upload_seq') ?? IdGeneratorService::nextSequence();
        $invoiceNumber  = IdGeneratorService::buildInvoiceNumber($seq);

        // 📝 LOG: Store Process Started
        Log::channel('ocr')->info('💾 [OCR FLOW] STORE PROCESS STARTED', [
            'step' => '4_store_start',
            'upload_id' => $uploadId,
            'user_id' => Auth::id(),
            'request_data' => [
                'customer' => $request->customer,
                'category' => $request->category,
                'amount' => $request->amount,
                'items_count' => is_array($request->items) ? count($request->items) : 0,
                'branches_count' => is_array($request->branches) ? count($request->branches) : 0,
            ],
        ]);

                DB::beginTransaction();
        try {
            $permanentPath = str_replace('temp-uploads/', '', $uploadFilePath);

            if (Storage::disk('public')->exists($uploadFilePath)) {
                $fileContent = Storage::disk('public')->get($uploadFilePath);
                Storage::disk('public')->put($permanentPath, $fileContent);
                Storage::disk('public')->delete($uploadFilePath);

                Log::channel('ocr')->info('📁 [OCR FLOW] FILE MOVED TO PERMANENT', [
                    'step'      => '4_file_move',
                    'upload_id' => $uploadId,
                    'from_path' => $uploadFilePath,
                    'to_path'   => $permanentPath,
                ]);
            } else {
                $permanentPath = $uploadFilePath;
            }

            $transaction = Transaction::create([
                'type'           => Transaction::TYPE_REMBUSH,
                'invoice_number' => $invoiceNumber,
                'customer'       => $request->customer,
                'category'       => $request->category,
                'description'    => $request->description,
                'payment_method' => $request->payment_method,
                'specs'          => empty($specs) ? null : $specs,
                'amount'         => $request->amount,
                'items'          => $request->items,
                'date'           => $request->date ?? now()->format('Y-m-d'),
                'file_path'      => $permanentPath,
                'status'         => 'pending',
                'ai_status'      => $permanentPath ? 'queued' : 'skipped',
                'upload_id'      => $uploadId,
                'trace_id'       => Transaction::generateTraceId(),
                'submitted_by'   => Auth::id(),
            ]);

            Log::channel('ocr')->info('🆕 [OCR FLOW] TRANSACTION CREATED', [
                'step'           => '4_transaction_created',
                'upload_id'      => $uploadId,
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'ai_status'      => $transaction->ai_status,
                'amount'         => $transaction->amount,
            ]);

            // ✅ FIX: Dispatch OCR Job DI SINI setelah transaksi ada dan file di permanent path.
            // Job sebelumnya di-dispatch saat upload (race condition: transaction_id=null, file di temp).
            // ✅ CRITICAL: Specify queue explicitly
            OcrProcessingJob::dispatch(
                $uploadId,
                $permanentPath,
                'normal',
                $transaction->id
            )->onQueue('ocr_normal'); // ← WAJIB tambahkan ini

            Log::channel('ocr')->info('🔄 [OCR FLOW] OCR JOB DISPATCHED', [
                'step'           => '4_job_dispatched',
                'upload_id'      => $uploadId,
                'transaction_id' => $transaction->id,
                'relative_path'  => $permanentPath,
                'priority'       => 'normal',
                'queue'          => 'ocr_normal',
            ]);


            // Attach branches
            if ($request->branches && count($request->branches) > 0) {
                foreach ($request->branches as $branchData) {
                    $allocPercent = floatval($branchData['allocation_percent']);
                    $allocAmount  = intval(round(($transaction->amount * $allocPercent) / 100));

                    $transaction->branches()->attach($branchData['branch_id'], [
                        'allocation_percent' => $allocPercent,
                        'allocation_amount'  => $allocAmount,
                    ]);
                }
                // 📝 LOG: Branches Attached
                Log::channel('ocr')->info('🏢 [OCR FLOW] BRANCHES ATTACHED', [
                    'step' => '4_branches',
                    'transaction_id' => $transaction->id,
                    'branches_count' => count($request->branches),
                ]);
            }
            
            broadcast(new \App\Events\TransactionCreated($transaction));

            DB::commit();

            // Bersihkan session
            session()->forget([
                'upload_id',
                'upload_seq',
                'upload_file_path',
                'upload_file_base64',
                'upload_file_mime',
                'ai_data',
            ]);

            // 📝 LOG: Session Cleaned & Store Complete
            Log::channel('ocr')->info('✅ [OCR FLOW] STORE COMPLETE', [
                'step' => '4_complete',
                'upload_id' => $uploadId,
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'session_cleared' => true,
                'next_route' => 'transactions.confirm',
            ]);


            return redirect()->route('transactions.confirm', $transaction->id);

        } catch (\Exception $e) {
            DB::rollBack();
            // 📝 LOG: Store Failed
            Log::channel('ocr')->error('❌ [OCR FLOW] STORE FAILED', [
                'step' => '4_store_error',
                'upload_id' => $uploadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return back()->withInput()
                ->withErrors(['error' => 'Gagal menyimpan transaksi: ' . $e->getMessage()]);
        }
    }
}