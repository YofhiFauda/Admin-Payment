<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Transaction;
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
        $filePath = session('pengajuan_file_path');
        
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

        // Simpan ke disk PUBLIC
        $path = $request->file('file')->store('pengajuan', 'public');

        session([
            'pengajuan_file_path' => $path
        ]);

        return redirect()->route('pengajuan.form');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  STORE — Save Pengajuan Transaction
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function store(Request $request)
    {
        $uploadFilePath = session('pengajuan_file_path');

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

        // Move temp upload to permanent if exists
        $permanentPath = null;
        if ($uploadFilePath && Storage::disk('public')->exists($uploadFilePath)) {
            $permanentPath = str_replace('temp-uploads/', '', $uploadFilePath);
            Storage::disk('public')->move($uploadFilePath, $permanentPath);
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
            // Handle optional reference photo from form upload
            $filePath = $permanentPath;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = 'ref-' . round(microtime(true) * 1000) . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('nota-uploads', $fileName, 'public');
            }

            $transaction = Transaction::create([
                'type'            => Transaction::TYPE_PENGAJUAN,
                'invoice_number'  => Transaction::generateInvoiceNumber(),
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
                'submitted_by'    => Auth::id(),
            ]);

            // Attach branches if provided
            if ($request->branches && count($request->branches) > 0) {
                $effectiveAmount = $transaction->amount;
                foreach ($request->branches as $branchData) {
                    $allocPercent = floatval($branchData['allocation_percent']);
                    $allocAmount = isset($branchData['allocation_amount']) && $branchData['allocation_amount']
                        ? intval($branchData['allocation_amount'])
                        : intval(round(($effectiveAmount * $allocPercent) / 100));

                    $transaction->branches()->attach($branchData['branch_id'], [
                        'allocation_percent' => $allocPercent,
                        'allocation_amount'  => $allocAmount,
                    ]);
                }
            }

            DB::commit();

            session()->forget([
                'pengajuan_upload_id',
                'pengajuan_file_path',
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
