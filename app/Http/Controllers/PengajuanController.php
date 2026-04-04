<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Transaction;
use App\Models\TransactionCategory;
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
        $branches = Branch::all();
        $pengajuanCategories = TransactionCategory::forPengajuan()->active()->get();

        return view('transactions.form-pengajuan', compact('branches', 'pengajuanCategories'));
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
        $role = Auth::user()->role;
        
        $request->validate([
            'type'                              => 'required|in:pengajuan',
            'file'                              => 'nullable|image|max:2048',
            'items'                             => 'required|array|min:1',
            'items.*.customer'                  => 'required|string|max:255',
            'items.*.category'                  => ['required', 'string', function($attr, $val, $fail) {
                $exists = \App\Models\TransactionCategory::where('name', $val)
                    ->where('type', 'pengajuan')
                    ->where('is_active', true)
                    ->exists();
                if (!$exists) $fail('Alasan/Kategori tidak valid.');
            }],
            'items.*.estimated_price'           => 'required|integer|min:0',
            'items.*.quantity'                  => 'required|integer|min:1',
            'branches'                          => 'nullable|array',
            'branches.*.branch_id'              => 'required_with:branches|exists:branches,id',
            'branches.*.allocation_percent'     => 'required_with:branches|numeric|min:0|max:100',
            'branches.*.allocation_amount'      => 'nullable|numeric|min:0',
            'global_notes'                      => 'nullable|string|max:2000',
            'estimated_price'                   => 'nullable|numeric|min:0',
        ], [
            'items.*.link.url' => 'Terdapat Link/Referensi Barang yang tidak valid. Pastikan formatnya benar (contoh: https://...).',
            'items.*.customer.required' => 'Nama Barang/Jasa pada salah satu daftar barang wajib diisi.',
            'items.*.quantity.required' => 'Jumlah barang wajib diisi.',
            'items.*.estimated_price.required' => 'Estimasi harga satuan wajib diisi.',
            'estimated_price.min' => 'Total estimasi biaya harus lebih dari 0.',
        ]);

        // Generate identifiers
        $seq = IdGeneratorService::nextSequence();
        $uploadId = IdGeneratorService::buildUploadId($seq);
        $invoiceNumber = IdGeneratorService::buildInvoiceNumber((int) $seq);

        $filePath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $fileName   = $uploadId . '.' . $extension;   // UP-20260302-00002.jpeg
            Storage::disk('public')->put($fileName, file_get_contents($file));
            $filePath = $fileName;
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
            $items = array_map(function ($item) {
                return [
                    'customer'        => $item['customer']        ?? '',
                    'vendor'          => $item['vendor']          ?? '',
                    'link'            => $item['link']            ?? '',
                    'category'        => $item['category']        ?? '',  // unified field
                    'description'     => $item['description']     ?? '',
                    'specs'           => $item['specs']           ?? [],
                    'estimated_price' => intval($item['estimated_price'] ?? 0),
                    'quantity'        => intval($item['quantity']        ?? 1),
                ];
            }, $request->items);

            $totalAmount = collect($items)->sum(function($item) {
                return ($item['estimated_price'] ?? 0) * ($item['quantity'] ?? 1);
            });

            $transaction = Transaction::create([
                'type'            => Transaction::TYPE_PENGAJUAN,
                'invoice_number'  => $invoiceNumber,
                'customer'        => $items[0]['customer'] ?? 'Multiple Items',
                'vendor'          => $items[0]['vendor'] ?? null,
                'link'            => $items[0]['link'] ?? null,
                'description'     => $request->global_notes,
                'amount'          => $totalAmount,
                'items'           => $items,
                'file_path'       => $filePath,
                'date'            => now()->format('Y-m-d'),
                'status'          => 'pending',
                'upload_id'       => $uploadId,
                'trace_id'        => Transaction::generateTraceId(),
                'submitted_by'    => Auth::id(),
            ]);


            // ✅ SNAPSHOT data asli pengaju (immutable)
            $transaction->items_snapshot = $transaction->items;
            $transaction->save();

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

            return redirect()->route('transactions.confirm', $transaction->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pengajuan store failed', ['error' => $e->getMessage()]);
            return back()->withInput()
                ->withErrors(['error' => 'Gagal menyimpan pengajuan: ' . $e->getMessage()]);
        }
    }
}