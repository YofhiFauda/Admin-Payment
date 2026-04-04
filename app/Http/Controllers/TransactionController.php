<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityLog;
use App\Notifications\TransactionStatusNotification;
use App\Notifications\OwnerApprovalNotification;
use Illuminate\Support\Facades\Cache;

// 🔔 TELEGRAM: Import TelegramBotService
use App\Services\Telegram\TelegramBotService;

class TransactionController extends Controller
{
    // 🔔 TELEGRAM: Inject service via constructor
    private TelegramBotService $telegram;

    public function __construct(TelegramBotService $telegram)
    {
        $this->telegram = $telegram;
    }

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
        if ($search = $request->input('search')) {
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
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Type filter (rembush / pengajuan)
        if ($type = $request->input('type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        // Category filter
        if ($category = $request->input('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        // Branch filter
        if ($branchId = $request->input('branch_id')) {
            if ($branchId !== 'all') {
                $query->whereHas('branches', function ($q) use ($branchId) {
                    $q->where('branches.id', $branchId);
                });
            }
        }

        // Date Range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $transactions = $query->paginate(20);

        // Fetch filter data
        $branches = Branch::orderBy('name')->get();
        // Merge Rembush & Pengajuan Categories from DB as unique sorted list
        $categories = TransactionCategory::active()
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();

        // Stats - scoped per role and cached
        $isTeknisi = Auth::user()->isTeknisi();
        $userId = Auth::id();
        $cacheKey = $isTeknisi ? "transactions_stats_teknisi_{$userId}" : "transactions_stats_global";

        $stats = Cache::remember($cacheKey, 300, function () use ($isTeknisi, $userId) {
            $statsQuery = $isTeknisi
                ? Transaction::where('submitted_by', $userId)
                : new Transaction;

            return [
                'count'     => (clone $statsQuery)->count(),
                'pending'   => (clone $statsQuery)->where('status', 'pending')->count(),
                'approved'  => (clone $statsQuery)->where('status', 'approved')->count(),
                'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
                'rejected'  => (clone $statsQuery)->where('status', 'rejected')->count(),
                'auto_reject' => (clone $statsQuery)->where('status', 'auto-reject')->count(),
                'waiting_payment' => (clone $statsQuery)->where('status', 'waiting_payment')->count(),
                'flagged'   => (clone $statsQuery)->where('status', 'flagged')->count(),
            ];
        });

        return view('transactions.index', compact('transactions', 'stats', 'branches', 'categories'));
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
        $t = Transaction::with(['submitter', 'reviewer', 'branches', 'editor', 'branchDebts.debtorBranch', 'branchDebts.creditorBranch'])->findOrFail($id);

        return response()->json([
            'id'              => $t->id,
            'type'            => $t->type,
            'type_label'      => $t->type_label,
            'invoice_number'  => $t->invoice_number,
            'customer'        => $t->customer,
            'vendor'          => $t->vendor,
            'category'        => $t->category,
            'category_label'  => $t->category_label,
            'description'     => $t->description,
            'payment_method'  => $t->payment_method,
            'payment_method_label' => $t->payment_method ? (Transaction::PAYMENT_METHODS[$t->payment_method] ?? $t->payment_method) : null,
            'amount'          => $t->amount,
            'formatted_amount'=> $t->formatted_amount,
            'items'           => $t->normalized_items,
            'date' => $t->date ? \Carbon\Carbon::parse($t->date)->format('d M Y') : null,
            'status'          => $t->status,
            'status_label'    => $t->status_label,
            'specs'           => $t->specs,
            'quantity'        => $t->quantity,
            'estimated_price' => $t->estimated_price,
            'purchase_reason' => $t->category,
            'purchase_reason_label' => $t->category_label,
            'ai_status'       => $t->ai_status,
            'upload_id'       => $t->upload_id,
            'file_path'       => $t->file_path,
            'image_url'       => $t->file_path ? route('transactions.image', $t->id) : null,
            'submitter'       => $t->submitter ? ['name' => $t->submitter->name] : null,
            'reviewer'        => $t->reviewer ? ['name' => $t->reviewer->name] : null,
            'reviewed_at'     => $t->reviewed_at ? $t->reviewed_at->format('d M Y H:i') : null,
            'rejection_reason'=> $t->rejection_reason,
            'branches'        => $t->branches->map(function($b) use ($t) {
                $allocAmount = $b->pivot->allocation_amount;
                if (!$allocAmount || $allocAmount <= 0) {
                    $allocAmount = round(($t->effective_amount * $b->pivot->allocation_percent) / 100);
                }
                return [
                    'name'    => $b->name,
                    'percent' => $b->pivot->allocation_percent,
                    'amount'  => 'Rp ' . number_format($allocAmount, 0, ',', '.'),
                    'amount_raw' => $allocAmount,
                ];
            }),
            'branches_raw'    => $t->branches->map(function($b) use ($t) {
                $allocAmount = $b->pivot->allocation_amount;
                if (!$allocAmount || $allocAmount <= 0) {
                    $allocAmount = round(($t->effective_amount * $b->pivot->allocation_percent) / 100);
                }
                return [
                    'id'               => $b->id,
                    'name'             => $b->name,
                    'allocation_amount'=> $allocAmount,
                    'allocation_percent'=> $b->pivot->allocation_percent,
                ];
            }),
            'effective_amount' => $t->effective_amount,
            'created_at'      => $t->created_at->format('d M Y H:i'),
            // Current user context for action buttons
            'user_role'       => Auth::user()->role,
            'can_manage'      => Auth::user()->canManageStatus(),
            'is_owner'        => Auth::user()->isOwner(),
            // ✅ Versioning fields untuk Detail Modal
            'is_edited_by_management' => (bool) $t->is_edited_by_management,
            'revision_count'  => $t->revision_count ?? 0,
            'edited_at'       => $t->edited_at ? $t->edited_at->format('d M Y, H:i') : null,
            'editor_name'     => $t->editor ? $t->editor->name : null,
            'items_snapshot'  => $t->items_snapshot, // Original version (frozen)
            // Invoice fields
            'invoice_file_path'      => $t->invoice_file_path,
            'invoice_file_url'       => $t->invoice_file_path ? asset('storage/' . $t->invoice_file_path) : null,
            'diskon_pengiriman'      => $t->diskon_pengiriman,
            'ongkir'                 => $t->ongkir,
            'biaya_layanan_1'        => $t->biaya_layanan_1,
            'biaya_layanan_2'        => $t->biaya_layanan_2,
            'voucher_diskon'         => $t->voucher_diskon,
            'sumber_dana_branch_id'  => $t->sumber_dana_branch_id,
            'sumber_dana_branch_name'=> $t->sumberDanaBranch ? $t->sumberDanaBranch->name : null,
            // ✅ Multi Sumber Dana & Hutang
            'sumber_dana_data'       => $t->sumber_dana_data,
            'branch_debts'           => $t->branchDebts->map(function($debt) {
                return [
                    'id'                  => $debt->id,
                    'debtor_branch_id'    => $debt->debtor_branch_id,
                    'debtor_branch_name'  => $debt->debtorBranch->name,
                    'creditor_branch_id'  => $debt->creditor_branch_id,
                    'creditor_branch_name'=> $debt->creditorBranch->name,
                    'amount'              => $debt->amount,
                    'formatted_amount'    => $debt->formatted_amount,
                    'status'              => $debt->status,
                    'paid_at'             => $debt->paid_at ? $debt->paid_at->format('d M Y H:i') : null,
                ];
            }),
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
        $transaction = Transaction::with(['branches', 'editor'])->findOrFail($id);
        $branches = Branch::all();
        $user = Auth::user();

        // ✅ Proteksi: Status 'completed' (Selesai)
        $isCompleted = ($transaction->status === 'completed');

        // ✅ Calculate item count based on transaction type
        if ($transaction->isPengajuan()) {
            $itemCount = is_array($transaction->items) ? count($transaction->items) : 1;
        } else {
            $itemCount = $transaction->items ? count($transaction->items) : 0;
        }

        // ✅ Role-Based Access: Admin = read-only, Teknisi = diblokir, Management = full edit (unless completed)
        if ($transaction->isPengajuan()) {
            if ($user->isTeknisi()) {
                // Teknisi tidak bisa akses edit page pengajuan sama sekali
                return redirect()->route('transactions.index')
                    ->with('error', 'Anda tidak memiliki akses untuk mengedit Pengajuan.');
            }

            // Admin selalu read-only. Atasan & Owner read-only JIKA status selesai
            $isReadOnly = $user->isAdmin() || $isCompleted;

            $pengajuanCategories = TransactionCategory::forPengajuan()->active()->get();

            return view('transactions.edit-pengajuan', compact(
                'transaction', 'branches', 'itemCount', 'isReadOnly', 'isCompleted', 'pengajuanCategories'
            ));
        }

        // Rembush — semua management role bisa edit
        $isReadOnly = false;
        $rembushCategories = TransactionCategory::forRembush()->get();
        return view('transactions.edit-rembush', compact('transaction', 'branches', 'itemCount', 'isReadOnly', 'rembushCategories'));
    }

    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        $user = Auth::user();

        // ✅ Guard: Status 'completed' (Selesai) — semua role diblokir
        if ($transaction->status === 'completed') {
            return response()->json(['error' => 'Transaksi yang sudah Selesai tidak dapat diubah.'], 403);
        }

        // ✅ Guard: Pengajuan — hanya Owner dan Atasan yang bisa update
        if ($transaction->isPengajuan()) {
            $isManagement = $user->isOwner() || $user->isAtasan();
            if (!$isManagement) {
                return redirect()->route('transactions.index')
                    ->with('error', 'Anda tidak memiliki akses untuk mengubah Pengajuan.');
            }
        }

        // Validation depends on type
        if ($transaction->isPengajuan()) {
            $request->validate([
                'items'           => 'required|array|min:1',
                'items.*.customer'=> 'required|string|max:255',
                'items.*.vendor'  => 'nullable|string|max:255',
                'items.*.link'    => 'nullable|url|max:1000',
                'items.*.category'=> ['required', 'string', function($attr, $val, $fail) {
                    $exists = TransactionCategory::where('name', $val)
                        ->where('type', 'pengajuan')
                        ->where('is_active', true)
                        ->exists();
                    if (!$exists) $fail('Alasan/Kategori tidak valid.');
                }],
                'items.*.description' => 'nullable|string|max:2000',
                'items.*.specs'   => 'nullable|array',
                'items.*.quantity'=> 'required|integer|min:1',
                'items.*.estimated_price' => 'required|numeric|min:0',
                'estimated_price' => 'nullable|numeric|min:0',
                'branches'        => 'nullable|array',
                'branches.*.branch_id' => 'required_with:branches|exists:branches,id',
                'branches.*.allocation_percent' => 'required_with:branches|numeric|min:0|max:100',
                'branches.*.allocation_amount' => 'nullable|numeric|min:0',
            ], [
                'items.*.link.url' => 'Terdapat Link/Referensi Barang yang tidak valid. Pastikan formatnya benar (contoh: https://...).',
                'items.*.customer.required' => 'Nama Barang/Jasa pada salah satu daftar barang wajib diisi.',
                'items.*.quantity.required' => 'Jumlah barang wajib diisi.',
                'items.*.estimated_price.required' => 'Estimasi harga satuan wajib diisi.',
            ]);
        } else {
            $request->validate([
                'customer'       => 'nullable|string|max:255',
                'category'       => ['required', 'string', function($attr, $val, $fail) {
                    $exists = TransactionCategory::where('name', $val)
                        ->where('type', 'rembush')
                        ->where('is_active', true)
                        ->exists();
                    if (!$exists) $fail('Kategori tidak valid.');
                }],
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
            // ───────────────────────────────────────────────────────
            // ✅ VERSIONING LOGIC: Freeze snapshot saat edit pertama
            // ───────────────────────────────────────────────────────
            if ($transaction->isPengajuan()) {
                $user = Auth::user();
                $isManagement = $user->isOwner() || $user->role === 'atasan';
                
                if ($isManagement) {
                    // Jika ini edit pertama kali oleh management
                    if (!$transaction->is_edited_by_management) {
                        // Freeze snapshot data asli (versi pengaju)
                        $transaction->items_snapshot = $transaction->items;
                        
                        Log::info('[VERSIONING] First management edit - snapshot frozen', [
                            'transaction_id' => $transaction->id,
                            'invoice_number' => $transaction->invoice_number,
                            'edited_by' => $user->id,
                            'snapshot_items_count' => count($transaction->items_snapshot ?? []),
                        ]);
                    }
                    
                    // Mark sebagai diedit oleh management
                    $transaction->is_edited_by_management = true;
                    $transaction->edited_by = $user->id;
                    $transaction->edited_at = now();
                    $transaction->revision_count = ($transaction->revision_count ?? 0) + 1;
                    
                    // Atau gunakan helper method:
                    // $transaction->markAsEditedByManagement($user->id);
                }
            }
            if ($transaction->isPengajuan()) {
                $items = array_map(function ($item) {
                    return [
                        'customer'        => $item['customer']        ?? '',
                        'vendor'          => $item['vendor']          ?? '',
                        'link'            => $item['link']            ?? '',
                        'category'        => $item['category']        ?? '', 
                        'description'     => $item['description']     ?? '',
                        'specs'           => $item['specs']           ?? [],
                        'estimated_price' => intval($item['estimated_price'] ?? 0),
                        'quantity'        => intval($item['quantity']        ?? 1),
                    ];
                }, $request->items);

                $firstItem = $items[0] ?? [];
                $totalAmount = collect($items)->sum(function($item) {
                    return ($item['estimated_price'] ?? 0) * ($item['quantity'] ?? 1);
                });

                $transaction->update([
                    'customer'        => $firstItem['customer'] ?? 'Multiple Items',
                    'vendor'          => $firstItem['vendor'] ?? null,
                    'link'            => $firstItem['link'] ?? null,
                    'description'     => $request->global_notes ?? $firstItem['description'] ?? null,
                    'specs'           => $firstItem['specs'] ?? null,
                    'quantity'        => $firstItem['quantity'] ?? 1,
                    'estimated_price' => $firstItem['estimated_price'] ?? 0,
                    'category'        => $firstItem['category'] ?? null,
                    'amount'          => $totalAmount,
                    'items'           => $items,
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
            // Log activity
                $log = ActivityLog::create([
                    'user_id'        => Auth::id(),
                    'action'         => 'edit',
                    'transaction_id' => $transaction->id,
                    'target_id'      => $transaction->invoice_number,
                    'description'    => "Mengedit data Pengajuan " . $transaction->invoice_number . 
                                    ($transaction->is_edited_by_management ? " (Revisi ke-{$transaction->revision_count})" : ""),
                ]);
            broadcast(new \App\Events\ActivityLogged($log));
            broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));

            return redirect()->route('transactions.index')
                ->with('success', "Pengajuan {$transaction->invoice_number} berhasil diperbarui." . 
                                ($transaction->is_edited_by_management ? " (Revisi Management)" : ""));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[VERSIONING] Update failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);
            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui pengajuan: ' . $e->getMessage()]);
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
            $transaction = Transaction::with('submitter')->findOrFail($id);
            $oldStatus = $transaction->status;

            // ─── Approval Logic ───────────────────────────
            if ($transaction->isPengajuan()) {
                // PENGAJUAN LOGIC (Revised)
                // - Only Atasan/Owner can approve. Admin is excluded.
                // - Status moves to 'waiting_payment' after approval.
                
                if ($newStatus === 'approved') {
                    if ($user->isAdmin()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Admin tidak berwenang menyetujui Pengajuan. Hanya Atasan dan Owner yang dapat menyetujui.',
                        ], 403);
                    }

                    if ($oldStatus === 'pending') {
                        $newStatus = 'waiting_payment';
                    }
                }
            } else {
                // REMBUSH LOGIC
                // Admin/Atasan approving a pending transaction → 'waiting_payment'
                if ($newStatus === 'approved' && !$user->isOwner() && $oldStatus === 'pending') {
                    $newStatus = 'waiting_payment';
                    
                    // 🔔 TELEGRAM: Notifikasi ke TEKNISI bahwa transaksi disetujui, sedang diproses pembayaran
                    try {
                        $this->telegram->notifyPaymentProcessing($transaction);
                    } catch (\Exception $e) {
                        Log::error('[TELEGRAM] Failed to send payment processing notification', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Owner approving a pending transaction → 'waiting_payment'
                if ($newStatus === 'approved' && $user->isOwner() && $oldStatus === 'pending') {
                    $newStatus = 'waiting_payment';
                    
                    // 🔔 TELEGRAM: Notifikasi ke TEKNISI bahwa transaksi disetujui owner, sedang diproses pembayaran
                    try {
                        $this->telegram->notifyPaymentProcessing($transaction);
                    } catch (\Exception $e) {
                        Log::error('[TELEGRAM] Failed to send payment processing notification', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Owner approving an already-approved (waiting owner / setelah TF >= 1jt) → completed
                if ($newStatus === 'approved' && $user->isOwner() && $oldStatus === 'approved') {
                    $newStatus = 'completed';
                    
                    // 🔔 TELEGRAM: Notifikasi ke TEKNISI bahwa transaksi selesai (disetujui owner final)
                    try {
                        $this->telegram->notifyForceApprovedToTechnician($transaction);
                    } catch (\Exception $e) {
                        Log::error('[TELEGRAM] Failed to send completion notification', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
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

            // Notify submitter if status changed to approved, rejected, completed, or waiting_payment
            if (in_array($newStatus, ['approved', 'rejected', 'completed', 'waiting_payment']) && $oldStatus !== $newStatus) {
                if ($transaction->submitter) {
                    $transaction->submitter->notify(new TransactionStatusNotification($transaction, $newStatus));
                }
            }

            // Notify all owners when admin approves >= 1jt and it goes to 'approved' status (waiting owner).
            if ($newStatus === 'approved' && $oldStatus !== 'approved' && !$user->isOwner()) {
                /** @var \Illuminate\Database\Eloquent\Collection<\App\Models\User> $owners */
                $owners = User::where('role', 'owner')->get();
                foreach ($owners as $owner) {
                    $owner->notify(new OwnerApprovalNotification($transaction, $user->name));
                }
            }

            // Build toast info for JSON response
            $toastType = match($newStatus) {
                'completed' => 'success',
                'approved'  => 'warning',
                'rejected'  => 'error',
                default     => 'info',
            };

            $toastMessage = match($newStatus) {
                'completed' => "Transaksi {$transaction->invoice_number} selesai!",
                'approved'  => "Transaksi {$transaction->invoice_number} disetujui. Menunggu persetujuan Owner.",
                'rejected'  => "Transaksi {$transaction->invoice_number} ditolak.",
                'waiting_payment' => "Transaksi {$transaction->invoice_number} berlanjut ke pembayaran.",
                default     => "Status transaksi diubah.",
            };

            $statusLabel = match($newStatus) {
                'approved'  => 'DISETUJUI (Menunggu Owner)',
                'rejected'  => 'DITOLAK',
                'completed' => 'SELESAI',
                'pending'   => 'PENDING',
                'waiting_payment' => 'MENUNGGU PEMBAYARAN',
                default     => strtoupper($newStatus),
            };

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'       => true,
                    'message'       => "Nota {$transaction->invoice_number} diubah ke: {$statusLabel}",
                    'status'        => $newStatus,
                    'toast_type'    => $toastType,
                    'toast_message' => $toastMessage,
                    'transaction'   => $transaction->fresh()->toSearchArray(),
                ]);
            }

            return back()->with('success', "Nota {$transaction->invoice_number} diubah ke: {$statusLabel}");

        } catch (\Exception $e) {
            Log::error('Status update failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $id,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal mengubah status'], 500);
            }

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

            ActivityLog::create([
                'user_id'     => Auth::id(),
                'action'      => 'delete',
                'target_id'   => $invoiceNumber,
                'description' => "Menghapus secara permanen transaksi " . $invoiceNumber,
            ]);

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
    //  BRANCH DEBT SETTLEMENT
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function settleBranchDebt(Request $request, $id)
    {
        try {
            $debt = \App\Models\BranchDebt::with(['debtorBranch', 'creditorBranch', 'transaction'])->findOrFail($id);

            if ($debt->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hutang ini sudah lunas.',
                ], 400);
            }

            $debt->markAsPaid($request->notes);

            // Log activity
            ActivityLog::create([
                'user_id'        => Auth::id(),
                'action'         => 'settle_debt',
                'transaction_id' => $debt->transaction_id,
                'target_id'      => $debt->transaction->invoice_number ?? '-',
                'description'    => "Melunaskan hutang cabang {$debt->debtorBranch->name} ke {$debt->creditorBranch->name} sebesar {$debt->formatted_amount}" . ($request->notes ? " (Catatan: {$request->notes})" : ''),
            ]);

            Log::info('[DEBT] Branch debt settled', [
                'debt_id'        => $debt->id,
                'transaction_id' => $debt->transaction_id,
                'debtor'         => $debt->debtorBranch->name,
                'creditor'       => $debt->creditorBranch->name,
                'amount'         => $debt->amount,
                'settled_by'     => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Hutang {$debt->debtorBranch->name} → {$debt->creditorBranch->name} ({$debt->formatted_amount}) berhasil dilunaskan.",
            ]);
        } catch (\Exception $e) {
            Log::error('[DEBT] Settlement failed', [
                'debt_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal melunaskan hutang: ' . $e->getMessage(),
            ], 500);
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
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Type filter
        if ($type = $request->input('type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        // Category filter
        if ($category = $request->input('category')) {
            if ($category !== 'all') {
                $query->where(function($q) use ($category) {
                    $q->where('category', $category)
                      ->orWhere('purchase_reason', $category);
                });
            }
        }

        // Branch filter
        if ($branchId = $request->input('branch_id')) {
            if ($branchId !== 'all') {
                $query->whereHas('branches', function ($q) use ($branchId) {
                    $q->where('branches.id', $branchId);
                });
            }
        }

        // Date Range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Auto-cleanup stuck processing (timeout > 5 minutes)
        Transaction::where('ai_status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(5))
            ->update([
                'ai_status' => 'error',
                'description' => DB::raw('CONCAT(COALESCE(description, ""), " | AI Timeout: N8N Callback tidak diterima")')
            ]);

        // Get all transactions (limited to a safer amount for performance)
        $transactions = $query->limit(1500)->get();

        // Format data for client-side search menggunakan helper model (DRY)
        $data = $transactions->map(function (Transaction $t) {
            return $t->toSearchArray();
        });

        return response()->json($data);
    }


}