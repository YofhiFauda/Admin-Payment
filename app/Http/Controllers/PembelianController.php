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
 
class PembelianController extends Controller
{
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
            'nota'           => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'branches'       => 'required|array|min:1',
            'branches.*.branch_id' => 'required|exists:branches,id',
            'branches.*.allocation_percent' => 'required|numeric|min:0|max:100',
            // Parity with Rembush: Validate bank fields if Transfer to Seller
            'bank_name' => 'required_if:payment_method,transfer_penjual|string|max:100|nullable',
            'account_name' => 'required_if:payment_method,transfer_penjual|string|max:100|nullable',
            'account_number' => 'required_if:payment_method,transfer_penjual|string|max:100|nullable',
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

            // Attach branches
            $branchAttachData = [];
            $totalAllocated = 0;
            $effectiveAmount = $transaction->amount;

            foreach ($request->branches as $branchData) {
                $allocPercent = floatval($branchData['allocation_percent']);
                $allocAmount = intval(round(($effectiveAmount * $allocPercent) / 100));
                $totalAllocated += $allocAmount;

                $branchAttachData[] = [
                    'id'                 => $branchData['branch_id'],
                    'allocation_percent' => $allocPercent,
                    'allocation_amount'  => $allocAmount,
                ];
            }

            $diff = $effectiveAmount - $totalAllocated;
            if (count($branchAttachData) > 0 && $diff != 0) {
                $branchAttachData[count($branchAttachData) - 1]['allocation_amount'] += $diff;
            }

            foreach ($branchAttachData as $branch) {
                $transaction->branches()->attach($branch['id'], [
                    'allocation_percent' => $branch['allocation_percent'],
                    'allocation_amount'  => $branch['allocation_amount'],
                ]);
            }

            broadcast(new \App\Events\TransactionCreated($transaction));

            DB::commit();

            return redirect()->route('transactions.index')->with('success', 'Pembelian berhasil disimpan dan menunggu Review Management.');

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
