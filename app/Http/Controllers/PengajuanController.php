<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Transaction;
use App\Services\IdGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PengajuanController extends Controller
{
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  FORM — Pengajuan form (no OCR)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function showForm()
    {
        $uploadId = session('pengajuan_upload_id');
        $filePath = session('pengajuan_file_path');
        $base64   = session('pengajuan_file_base64');  // ← Ambil base64
        $mime     = session('pengajuan_file_mime');     // ← Ambil mime
        
        // Fallback jika file tidak exist di disk
        if ($filePath && !Storage::disk('public')->exists($filePath)) {
            $filePath = null;
        }
        
        $branches = Branch::all();
        return view('transactions.form-pengajuan', compact('filePath', 'branches'));
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  UPLOAD PHOTO — Optional reference photo
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png|max:1024',
        ]);

        $file = $request->file('file');

        // Generate sequence ONCE — both upload_id & invoice_number will share this number
        $seq       = IdGeneratorService::nextSequence();
        $uploadId  = IdGeneratorService::buildUploadId($seq);
        $extension = $file->getClientOriginalExtension();
        $fileName   = $uploadId . '.' . $extension;   // UP-20260302-00002.jpeg
        $storedPath = $fileName;                       // root of public disk
        $base64 = base64_encode(file_get_contents($file));
        $mime   = $file->getMimeType();

        // Save with predictable name at storage root
        Storage::disk('public')->put($storedPath, file_get_contents($file));


        session([
            'pengajuan_seq'           => $seq,
            'pengajuan_upload_id'     => $uploadId,
            'pengajuan_file_path'     => $storedPath,
            'pengajuan_file_base64'   => $base64,      // ← NEW
            'pengajuan_file_mime'     => $mime,        // ← NEW
        ]);

        return redirect()->route('pengajuan.form');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  STORE — Save Pengajuan Transaction
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function store(Request $request)
    {
        $uploadFilePath = session('pengajuan_file_path');
        $uploadId       = session('pengajuan_upload_id');
        // Derive invoice number from the SAME sequence used for upload_id
        $seq            = session('pengajuan_seq');
        $invoiceNumber  = $seq
            ? IdGeneratorService::buildInvoiceNumber((int) $seq)
            : IdGeneratorService::nextInvoiceNumber(); // fallback if no upload happened

        $request->validate([
            'customer'        => 'required|string|max:255',  // Nama barang/jasa
            'vendor'          => 'nullable|string|max:255',
            'specs'           => 'nullable|array',
            'specs.merk'      => 'nullable|string|max:255',
            'specs.tipe'      => 'nullable|string|max:255',
            'specs.ukuran'    => 'nullable|string|max:255',
            'specs.warna'     => 'nullable|string|max:255',
            'quantity'        => 'required|integer|min:1',
            'estimated_price' => 'required|numeric|min:1',
            'purchase_reason' => 'required|string|in:' . implode(',', array_keys(Transaction::PURCHASE_REASONS)),
            'branches'        => 'nullable|array',
            'branches.*.branch_id' => 'required_with:branches|exists:branches,id',
            'branches.*.allocation_percent' => 'required_with:branches|numeric|min:0|max:100',
            'branches.*.allocation_amount' => 'nullable|numeric|min:0',
        ]);

        // File sudah final di path 'pengajuan/' (tidak perlu dipindah)
        $permanentPath = null;
        if ($uploadFilePath && Storage::disk('public')->exists($uploadFilePath)) {
            $permanentPath = $uploadFilePath; // already in permanent location
        }

        // Validate branch allocation if provided
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

        DB::beginTransaction();

        try {
            // $filePath sudah di-set dari session oleh uploadPhoto()
            // dengan nama UP-YYYYMMDD-XXXXX — tidak perlu upload ulang di sini
            $filePath = $permanentPath;

            $transaction = Transaction::create([
                'type'            => Transaction::TYPE_PENGAJUAN,
                'invoice_number'  => $invoiceNumber,
                'customer'        => $request->customer,  // nama barang/jasa
                'vendor'          => $request->vendor,
                'specs'           => $request->specs,
                'quantity'        => $request->quantity,
                'estimated_price' => $request->estimated_price,
                'purchase_reason' => $request->purchase_reason,
                'amount'          => $request->estimated_price * $request->quantity,
                'file_path'       => $filePath,
                'date'            => now()->format('Y-m-d'),
                'status'          => 'pending',
                'upload_id'       => $uploadId,
                'trace_id'        => Transaction::generateTraceId(),
                'submitted_by'    => Auth::id(),
            ]);

            // Attach branches if provided
            if ($request->branches && count($request->branches) > 0) {
                $effectiveAmount = $transaction->amount;
                foreach ($request->branches as $branchData) {
                    $allocPercent = floatval($branchData['allocation_percent']);
                    // Selalu hitung ulang di backend berdasarkan persen untuk akurasi
                    $allocAmount = intval(round(($effectiveAmount * $allocPercent) / 100));

                    $transaction->branches()->attach($branchData['branch_id'], [
                        'allocation_percent' => $allocPercent,
                        'allocation_amount'  => $allocAmount,
                    ]);
                }
            }

            broadcast(new \App\Events\TransactionCreated($transaction));
            DB::commit();

            session()->forget([
                'pengajuan_seq',
                'pengajuan_upload_id',
                'pengajuan_file_path',
                'pengajuan_file_base64',   // ← ADDED
                'pengajuan_file_mime',     // ← ADDED
            ]);

            return redirect()->route('transactions.confirm', $transaction->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pengajuan store failed', ['error' => $e->getMessage()]);
            return back()->withInput()
                ->withErrors(['error' => 'Gagal menyimpan pengajuan: ' . $e->getMessage()]);
        }
    }
}