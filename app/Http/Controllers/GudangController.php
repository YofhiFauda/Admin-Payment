<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\IdGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GudangController extends Controller
{
    /**
     * Show loading transition before the form.
     */
    public function loading()
    {
        return view('transactions.gudang-loading');
    }

    /**
     * Show form for new Gudang transaction.
     */
    public function create()
    {
        $branches = Branch::all();
        // Since the user says "Form tersebut sama seperti Rembush", 
        // we might want to let them choose a category or just a default one.
        // Let's grab Rembush categories for now or keep it flexible.
        $categories = TransactionCategory::forRembush()->get();
        
        return view('transactions.gudang-form', compact('branches', 'categories'));
    }

    /**
     * Store new Gudang transaction.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date'           => 'required|date',
            'vendor'         => 'nullable|string|max:255',
            'category'       => 'required|string|max:255',
            'items'          => 'required|array|min:1',
            'items.*.name'   => 'required|string|max:255',
            'items.*.qty'    => 'required|numeric|min:1',
            'items.*.price'  => 'required|numeric|min:0',
            'items.*.unit'   => 'nullable|string|max:50',
            'items.*.desc'   => 'nullable|string|max:500',
            'amount'         => 'required|numeric|min:0',
            'description'    => 'nullable|string|max:2000',
            'payment_method' => 'required|string|in:cash,transfer_teknisi,transfer_penjual',
            'pengeluaran_siapa' => 'required|string|max:255',
            'nota'           => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'branches'       => 'required|array|min:1',
            'branches.*.branch_id' => 'required|exists:branches,id',
            'branches.*.allocation_percent' => 'required|numeric|min:0|max:100',
        ]);

        // Validate branch allocation
        $totalPercent = collect($request->branches)->sum('allocation_percent');
        if (abs($totalPercent - 100) > 0.1) {
            return back()->withErrors(['branches' => 'Total alokasi cabang harus 100%.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $seq = IdGeneratorService::nextSequence();
            $invoiceNumber = IdGeneratorService::buildInvoiceNumber($seq);
            $uploadId = IdGeneratorService::buildUploadId($seq);

            // Handle optional nota
            $filePath = null;
            if ($request->hasFile('nota')) {
                $file = $request->file('nota');
                $extension = $file->getClientOriginalExtension();
                $fileName = $uploadId . '.' . $extension;
                $filePath = $file->storeAs('uploads', $fileName, 'public');
            }

            // Combine descriptions as requested
            $fullDescription = $request->description;
            if ($request->pengeluaran_siapa) {
                $prefix = "[Oleh: " . $request->pengeluaran_siapa . "]";
                $fullDescription = $fullDescription ? $prefix . " " . $fullDescription : $prefix;
            }

            $transaction = Transaction::create([
                'type'           => Transaction::TYPE_GUDANG,
                'invoice_number' => $invoiceNumber,
                'vendor'         => $request->vendor,
                'category'       => $request->category,
                'description'    => $fullDescription,
                'amount'         => $request->amount,
                'items'          => $request->items,
                'date'           => $request->date,
                'file_path'      => $filePath,
                'status'         => 'pending', // Label: Review Management
                'payment_method' => $request->payment_method,
                'submitted_by'   => Auth::id(),
                'upload_id'      => $uploadId,
                'trace_id'       => Transaction::generateTraceId(),
            ]);

            // Attach branches
            foreach ($request->branches as $branchData) {
                $allocPercent = floatval($branchData['allocation_percent']);
                $allocAmount = intval(round(($transaction->amount * $allocPercent) / 100));

                $transaction->branches()->attach($branchData['branch_id'], [
                    'allocation_percent' => $allocPercent,
                    'allocation_amount'  => $allocAmount,
                ]);
            }

            broadcast(new \App\Events\TransactionCreated($transaction));

            DB::commit();

            return redirect()->route('transactions.index')->with('success', 'Belanja Gudang berhasil disimpan dan menunggu Review Management.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store Gudang transaction', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan transaksi: ' . $e->getMessage()]);
        }
    }
}
