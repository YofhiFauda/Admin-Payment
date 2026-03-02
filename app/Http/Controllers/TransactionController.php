<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityLog;
use App\Notifications\TransactionStatusNotification;


class TransactionController extends Controller
{
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  INDEX — Riwayat Transaksi (Rembush + Pengajuan)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function index(Request $request)
    {
        $query = Transaction::with(['submitter', 'reviewer', 'branches'])->latest();

        // Teknisi hanya melihat transaksi sendiri
        if (Auth::user()->isTeknisi()) {
            $query->where('submitted_by', Auth::id());
        }

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhereHas('submitter', function ($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%");
                  });

                $dateFormats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'd M Y'];
                foreach ($dateFormats as $format) {
                    try {
                        $parsed = \Carbon\Carbon::createFromFormat($format, $search);
                        if ($parsed) {
                            $q->orWhereDate('date', $parsed->toDateString())
                          ->orWhereDate('created_at', $parsed->toDateString());
                            break;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Type filter (rembush / pengajuan)
        if ($type = $request->get('type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        // Category filter
        if ($category = $request->get('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        $transactions = $query->paginate(20);

        // Stats - scoped per role
        $statsQuery = Auth::user()->isTeknisi()
            ? Transaction::where('submitted_by', Auth::id())
            : new Transaction;

        $stats = [
            'count'     => (clone $statsQuery)->count(),
            'pending'   => (clone $statsQuery)->where('status', 'pending')->count(),
            'approved'  => (clone $statsQuery)->where('status', 'approved')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'rejected'  => (clone $statsQuery)->where('status', 'rejected')->count(),
        ];

        return view('transactions.index', compact('transactions', 'stats'));
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  CREATE — Halaman Pilih Jenis (Rembush / Pengajuan)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function create()
    {
        return view('transactions.create');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  DETAIL / CONFIRMATION
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function show($id)
    {
        try {
            $transaction = Transaction::with(['submitter', 'reviewer', 'branches'])->findOrFail($id);
            $fileExists = $transaction->file_path && Storage::disk('public')->exists($transaction->file_path);

            return view('transactions.show', compact('transaction', 'fileExists'));
        } catch (\Exception $e) {
            return redirect()->route('transactions.index')
                ->withErrors(['error' => 'Transaksi tidak ditemukan']);
        }
    }

    public function detailJson($id)
    {
        $t = Transaction::with(['submitter', 'reviewer', 'branches'])->findOrFail($id);

        return response()->json([
            'id'              => $t->id,
            'type'            => $t->type,
            'type_label'      => $t->type_label,
            'invoice_number'  => $t->invoice_number,
            'customer'        => $t->customer,
            'vendor'          => $t->vendor,
            'category'        => $t->category,
            'category_label'  => $t->category ? (Transaction::CATEGORIES[$t->category] ?? $t->category) : null,
            'description'     => $t->description,
            'payment_method'  => $t->payment_method,
            'payment_method_label' => $t->payment_method ? (Transaction::PAYMENT_METHODS[$t->payment_method] ?? $t->payment_method) : null,
            'amount'          => $t->amount,
            'formatted_amount'=> $t->formatted_amount,
            'items'           => $t->items,
            'date' => $t->date ? \Carbon\Carbon::parse($t->date)->format('d M Y') : null,
            'status'          => $t->status,
            'status_label'    => $t->status_label,
            'specs'           => $t->specs,
            'quantity'        => $t->quantity,
            'estimated_price' => $t->estimated_price,
            'purchase_reason' => $t->purchase_reason,
            'purchase_reason_label' => $t->purchase_reason ? (Transaction::PURCHASE_REASONS[$t->purchase_reason] ?? $t->purchase_reason) : null,
            'ai_status'       => $t->ai_status,
            'upload_id'       => $t->upload_id,
            'file_path'       => $t->file_path,
            'image_url'       => $t->file_path ? route('transactions.image', $t->id) : null,
            'submitter'       => $t->submitter ? ['name' => $t->submitter->name] : null,
            'reviewer'        => $t->reviewer ? ['name' => $t->reviewer->name] : null,
            'reviewed_at'     => $t->reviewed_at ? $t->reviewed_at->format('d M Y H:i') : null,
            'rejection_reason'=> $t->rejection_reason,
            'branches'        => $t->branches->map(fn($b) => [
                'name'    => $b->name,
                'percent' => $b->pivot->allocation_percent,
                'amount'  => 'Rp ' . number_format($b->pivot->allocation_amount, 0, ',', '.'),
            ]),
            'effective_amount' => $t->effective_amount,
            'created_at'      => $t->created_at->format('d M Y H:i'),
            // Current user context for action buttons
            'user_role'       => Auth::user()->role,
            'can_manage'      => Auth::user()->canManageStatus(),
            'is_owner'        => Auth::user()->isOwner(),
        ]);
    }

    public function confirmation($id)
    {
        try {
            $transaction = Transaction::with(['submitter', 'branches'])->findOrFail($id);
            $fileExists = $transaction->file_path && Storage::disk('public')->exists($transaction->file_path);

            return view('transactions.confirm', compact('transaction', 'fileExists'));
        } catch (\Exception $e) {
            return redirect()->route('transactions.index')
                ->withErrors(['error' => 'Transaksi tidak ditemukan']);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  EDIT / UPDATE
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function edit($id)
    {
        $transaction = Transaction::with('branches')->findOrFail($id);
        $branches = Branch::all();

        if ($transaction->isPengajuan()) {
            return view('transactions.edit-pengajuan', compact('transaction', 'branches'));
        }

        return view('transactions.edit-rembush', compact('transaction', 'branches'));
    }

    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        // Validation depends on type
        if ($transaction->isPengajuan()) {
            $request->validate([
                'customer'        => 'required|string|max:255',
                'vendor'          => 'nullable|string|max:255',
                'specs'           => 'nullable|array',
                'quantity'        => 'required|integer|min:1',
                'estimated_price' => 'required|numeric|min:1',
                'purchase_reason' => 'required|string|in:' . implode(',', array_keys(Transaction::PURCHASE_REASONS)),
                'branches'        => 'nullable|array',
                'branches.*.branch_id' => 'required_with:branches|exists:branches,id',
                'branches.*.allocation_percent' => 'required_with:branches|numeric|min:0|max:100',
                'branches.*.allocation_amount' => 'nullable|numeric|min:0',
            ]);
        } else {
            $request->validate([
                'customer'       => 'nullable|string|max:255',
                'category'       => 'required|string|in:' . implode(',', array_keys(Transaction::CATEGORIES)),
                'amount'         => 'nullable|numeric|min:0',
                'description'    => 'nullable|string|max:2000',
                'payment_method' => 'nullable|string|in:' . implode(',', array_keys(Transaction::PAYMENT_METHODS)),
                'items'          => 'nullable|array',
                'items.*.nama_barang' => 'nullable|string',
                'items.*.qty' => 'nullable|numeric',
                'items.*.satuan' => 'nullable|string',
                'items.*.harga_satuan' => 'nullable|numeric',
                'items.*.total_harga' => 'nullable|numeric',
                'date'           => 'nullable|date',
                'branches'       => 'nullable|array',
                'branches.*.branch_id' => 'required_with:branches|exists:branches,id',
                'branches.*.allocation_percent' => 'required_with:branches|numeric|min:0|max:100',
                'branches.*.allocation_amount' => 'nullable|numeric|min:0',
            ]);
        }

        // Validate branches if provided
        if ($request->branches && count($request->branches) > 0) {
            $totalPercent = collect($request->branches)->sum('allocation_percent');
            if (abs($totalPercent - 100) > 1) {
                return back()->withErrors(['branches' => 'Total alokasi harus 100%.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            if ($transaction->isPengajuan()) {
                $transaction->update([
                    'customer'        => $request->customer,
                    'vendor'          => $request->vendor,
                    'specs'           => $request->specs,
                    'quantity'        => $request->quantity,
                    'estimated_price' => $request->estimated_price,
                    'purchase_reason' => $request->purchase_reason,
                    'amount'          => $request->estimated_price * $request->quantity,
                ]);
            } else {
                $transaction->update([
                    'customer'       => $request->customer,
                    'category'       => $request->category,
                    'description'    => $request->description,
                    'payment_method' => $request->payment_method,
                    'amount'         => $request->amount,
                    'items'          => $request->items,
                    'date'           => $request->date ?? $transaction->date,
                ]);
            }

            // Sync branches
            $transaction->branches()->detach();
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

            // Log activity
            $log = ActivityLog::create([
                'user_id'        => Auth::id(),
                'action'         => 'edit',
                'transaction_id' => $transaction->id,
                'target_id'      => $transaction->invoice_number,
                'description'    => "Mengedit data Nota " . $transaction->invoice_number,
            ]);
            broadcast(new \App\Events\ActivityLogged($log));
            broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));

            return redirect()->route('transactions.index')
                ->with('success', "Transaksi {$transaction->invoice_number} berhasil diperbarui.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui transaksi: ' . $e->getMessage()]);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  STATUS UPDATE — Approval Logic
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //
    //  REMBUSH:
    //    - Berapapun nominal → Admin/Atasan approve → completed
    //
    //  PENGAJUAN:
    //    - < 1jt  → Admin approve → completed
    //    - >= 1jt → Admin approve → approved (menunggu Owner) → Owner approve → completed
    //
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        $newStatus = $request->status;

        $allowedStatuses = $user->isOwner()
            ? ['pending', 'approved', 'completed', 'rejected']
            : ['approved', 'rejected'];

        $request->validate([
            'status' => 'required|in:' . implode(',', $allowedStatuses),
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:1000',
        ]);

        try {
            $transaction = Transaction::findOrFail($id);
            $oldStatus = $transaction->status;

            // ─── Approval Logic ───────────────────────────
            // Admin/Atasan approving a pending transaction:
            //   - Amount < 1jt  → langsung 'completed'
            //   - Amount >= 1jt → stays 'approved' (menunggu Owner)
            if ($newStatus === 'approved' && !$user->isOwner() && $oldStatus === 'pending') {
                $amount = $transaction->effective_amount;
                if ($amount < 1000000) {
                    $newStatus = 'completed';
                }
                // >= 1jt: stays 'approved', waiting for Owner
            }

            // Owner approving a pending transaction → langsung completed
            if ($newStatus === 'approved' && $user->isOwner() && $oldStatus === 'pending') {
                $newStatus = 'completed';
            }

            // Owner approving an already-approved (waiting owner) → completed
            if ($newStatus === 'approved' && $user->isOwner() && $oldStatus === 'approved') {
                $newStatus = 'completed';
            }

            $updateData = [
                'status'      => $newStatus,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ];

            if ($newStatus === 'rejected') {
                $updateData['rejection_reason'] = $request->rejection_reason;
            } else {
                $updateData['rejection_reason'] = null;
            }

            $transaction->update($updateData);

            // Log activity
            $actionLabel = $newStatus === 'rejected' ? 'Reject' : 'Approve';
            $description = $newStatus === 'rejected' 
                ? "Menolak status Transaksi " . $transaction->invoice_number . " dengan alasan: " . $request->rejection_reason
                : "Menyetujui status Transaksi " . $transaction->invoice_number;

            $log = ActivityLog::create([
                'user_id'        => Auth::id(),
                'action'         => strtolower($actionLabel),
                'transaction_id' => $transaction->id,
                'target_id'      => $transaction->invoice_number,
                'description'    => $description,
            ]);
            broadcast(new \App\Events\ActivityLogged($log));
            broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));

            Log::info('Transaction status updated', [
                'transaction_id'   => $transaction->id,
                'invoice_number'   => $transaction->invoice_number,
                'type'             => $transaction->type,
                'old_status'       => $oldStatus,
                'new_status'       => $newStatus,
                'reviewed_by'      => Auth::id(),
                'rejection_reason' => $request->rejection_reason,
            ]);

            // Notify submitter if status changed to approved, rejected, or completed
            if (in_array($newStatus, ['approved', 'rejected', 'completed']) && $oldStatus !== $newStatus) {
                if ($transaction->submitter) {
                    $transaction->submitter->notify(new TransactionStatusNotification($transaction, $newStatus));
                }
            }

            $statusLabel = match($newStatus) {
                'approved'  => 'DISETUJUI (Menunggu Owner)',
                'rejected'  => 'DITOLAK',
                'completed' => 'SELESAI',
                'pending'   => 'PENDING',
                default     => strtoupper($newStatus),
            };

            return back()->with('success', "Nota {$transaction->invoice_number} diubah ke: {$statusLabel}");

        } catch (\Exception $e) {
            Log::error('Status update failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $id,
            ]);

            return back()->withErrors(['error' => 'Gagal mengubah status']);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  DELETE
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function destroy($id)
    {
        abort_if(Auth::user()->isAdmin(), 403, 'Unauthorized action.');

        try {
            $transaction = Transaction::findOrFail($id);
            $invoiceNumber = $transaction->invoice_number;
            $filePath = $transaction->file_path;

            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            $transaction->delete();

            Log::info('Transaction deleted', [
                'transaction_id' => $id,
                'invoice_number' => $invoiceNumber,
                'deleted_by' => Auth::id(),
            ]);

            return back()->with('success', "Transaksi {$invoiceNumber} berhasil dihapus");

        } catch (\Exception $e) {
            Log::error('Transaction deletion failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $id,
            ]);

            return back()->withErrors(['error' => 'Gagal menghapus transaksi']);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  IMAGE SERVING
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function serveImage($id)
    {
        $transaction = Transaction::findOrFail($id);

        if (!$transaction->file_path || !Storage::disk('public')->exists($transaction->file_path)) {
            abort(404, 'Gambar tidak ditemukan');
        }

        $file = Storage::disk('public')->get($transaction->file_path);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($file);

        return response($file, 200)->header('Content-Type', $mimeType);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  GET ALL TRANSACTIONS FOR CLIENT-SIDE SEARCH
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function getAllForSearch(Request $request)
    {
        $query = Transaction::with(['submitter', 'reviewer', 'branches'])->latest();

        // Teknisi hanya melihat transaksi sendiri
        if (Auth::user()->isTeknisi()) {
            $query->where('submitted_by', Auth::id());
        }

        // Status filter
        if ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Type filter
        if ($type = $request->get('type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        // Category filter
        if ($category = $request->get('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        // Get all transactions (limited to reasonable amount for performance)
        $transactions = $query->limit(5000)->get();

        // Format data for client-side search
        $data = $transactions->map(function ($t) {
            return [
                'id' => $t->id,
                'invoice_number' => $t->invoice_number,
                'submitter_name' => $t->submitter->name ?? '-',
                'customer' => $t->customer ?? '',
                'vendor' => $t->vendor ?? '',
                'type' => $t->type,
                'type_label' => $t->type_label,
                'category' => $t->category,
                'category_label' => $t->type === 'pengajuan' 
                    ? (Transaction::PURCHASE_REASONS[$t->purchase_reason] ?? '-')
                    : (Transaction::CATEGORIES[$t->category] ?? '-'),
                'status' => $t->status,
                'status_label' => $t->status_label,
                'amount' => $t->amount,
                'formatted_amount' => number_format($t->amount ?? 0, 0, ',', '.'),
                'date' => $t->date ? \Carbon\Carbon::parse($t->date)->format('d M Y') : null,
                'created_at' => $t->created_at->format('d M Y'),
                'created_at_search' => $t->created_at->format('d-m-Y Y-m-d'),
                'ai_status' => $t->ai_status,
                'upload_id' => $t->upload_id,
                'confidence' => $t->confidence,
                'rejection_reason' => $t->rejection_reason,
                'effective_amount' => $t->effective_amount,
                'purchase_reason' => $t->purchase_reason,
                'purchase_reason_label' => $t->purchase_reason ? (Transaction::PURCHASE_REASONS[$t->purchase_reason] ?? '') : '',
                // Search string untuk matching
                'search_text' => strtolower(
                    ($t->submitter->name ?? '') . ' ' .
                    $t->invoice_number . ' ' .
                    ($t->customer ?? '') . ' ' .
                    ($t->vendor ?? '') . ' ' .
                    $t->created_at->format('d M Y d-m-Y Y-m-d')
                ),
            ];
        });

        return response()->json($data);
    }
}