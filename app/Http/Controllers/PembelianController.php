<?php
 
namespace App\Http\Controllers;
 
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\IdGeneratorService;
use App\Services\ImageCompressionService;
use App\Support\BranchAllocation;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
 
class PembelianController extends Controller
{
    private ImageCompressionService $compression;

    public function __construct(ImageCompressionService $compression)
    {
        $this->compression = $compression;
    }

    /**
     * Show loading transition before the form.
     */
    public function loading()
    {
        return view('transactions.pembelian-loading');
    }

    /**
     * Show form for new Pembelian transaction.
     */
    public function create()
    {
        $branches = Branch::all();
        $categories = TransactionCategory::forRembush()->get();
        
        $technicians = collect();
        if (!Auth::user()->isTeknisi()) {
            $technicians = \App\Models\User::where('role', 'teknisi')->with('bankAccounts')->get();
        }
        
        return view('transactions.form-pembelian', compact('branches', 'categories', 'technicians'));
    }

    /**
     * Store new Pembelian transaction.
     */
    public function store(Request $request)
    {
        // Debug: Log request data untuk troubleshooting
        Log::info('[PEMBELIAN] Store request received', [
            'has_items' => $request->has('items'),
            'items_count' => $request->has('items') ? count($request->items) : 0,
            'has_branches' => $request->has('branches'),
            'branches_count' => $request->has('branches') ? count($request->branches) : 0,
            'amount' => $request->input('amount'),
            'payment_method' => $request->input('payment_method'),
            'category' => $request->input('category'),
            'date' => $request->input('date'),
        ]);

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
            'technician_id' => 'nullable|exists:users,id',
            'technician_bank_account_id' => 'nullable|exists:user_bank_accounts,id',
            'nota'           => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'branches'       => 'required|array|min:1',
            'branches.*.branch_id' => 'required|exists:branches,id',
            'branches.*.allocation_percent' => 'required|numeric|min:0|max:100',
            // Parity with Rembush: Validate bank fields if Transfer to Seller
            'bank_name' => 'required_if:payment_method,transfer_penjual|string|max:100|nullable',
            'account_name' => 'required_if:payment_method,transfer_penjual|string|max:100|nullable',
            'account_number' => 'required_if:payment_method,transfer_penjual|string|max:100|nullable',
        ], [
            'date.required' => 'Tanggal pembelian wajib diisi.',
            'category.required' => 'Kategori wajib dipilih.',
            'items.required' => 'Minimal harus ada 1 item barang.',
            'items.*.name.required' => 'Nama barang pada baris :position wajib diisi.',
            'items.*.qty.required' => 'Jumlah barang pada baris :position wajib diisi.',
            'items.*.price.required' => 'Harga satuan pada baris :position wajib diisi.',
            'amount.required' => 'Total amount wajib diisi.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'branches.required' => 'Minimal harus memilih 1 cabang.',
            'branches.*.branch_id.required' => 'ID cabang pada alokasi :position wajib diisi.',
            'branches.*.allocation_percent.required' => 'Persentase alokasi cabang :position wajib diisi.',
        ]);

        // Validate branch allocation
        $branches = BranchAllocation::normalize($request->branches);
        $totalPercent = collect($branches)->sum('allocation_percent');
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

                // 🗜️ Kompresi gambar setelah disimpan (lewati PDF)
                $this->compression->compressUpload(Storage::disk('public')->path($filePath));
            }

            // Determine submitter and description prefix
            $submittedBy = Auth::id();
            $technicianName = null;
            if ($request->technician_id) {
                $tech = \App\Models\User::find($request->technician_id);
                if ($tech) {
                    $submittedBy = $tech->id;
                    $technicianName = $tech->name;
                }
            }

            // Combine descriptions
            $fullDescription = $request->description;
            if ($technicianName) {
                $prefix = "[Oleh: " . $technicianName . "]";
                $fullDescription = $fullDescription ? $prefix . " " . $fullDescription : $prefix;
            }

            // Bank details (specs)
            $specs = null;
            if ($request->payment_method === 'transfer_teknisi' && $request->technician_bank_account_id) {
                $bankAcc = \App\Models\UserBankAccount::find($request->technician_bank_account_id);
                if ($bankAcc) {
                    $specs = [
                        'bank_name'      => strtoupper($bankAcc->bank_name),
                        'account_name'   => strtoupper($bankAcc->account_name),
                        'account_number' => $bankAcc->account_number,
                        'bank_account_id' => $bankAcc->id,
                    ];
                }
            } elseif ($request->payment_method === 'transfer_penjual') {
                $specs = [
                    'bank_name'      => strtoupper($request->bank_name),
                    'account_name'   => strtoupper($request->account_name),
                    'account_number' => $request->account_number,
                ];
            }

            $transaction = Transaction::create([
                'type'           => Transaction::TYPE_GUDANG, // Keep the model constant for now
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
                'technician_id'  => $request->technician_id,
                'specs'          => $specs,
                'submitted_by'   => $submittedBy,
                'upload_id'      => $uploadId,
                'trace_id'       => Transaction::generateTraceId(),
            ]);

            $transaction->branches()->sync(
                BranchAllocation::toSyncData($branches, $transaction->amount)
            );

            DB::commit();

            // Broadcast setelah commit — kegagalan broadcast tidak membatalkan transaksi
            try {
                broadcast(new \App\Events\TransactionCreated($transaction));
            } catch (\Exception $broadcastEx) {
                Log::warning('Broadcast TransactionCreated gagal (non-fatal)', [
                    'transaction_id' => $transaction->id,
                    'error' => $broadcastEx->getMessage(),
                ]);
            }

            return redirect()->route('transactions.confirm', $transaction->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store Pembelian transaction', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan transaksi: ' . $e->getMessage()]);
        }
    }
}
