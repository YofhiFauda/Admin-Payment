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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;


// 🔔 TELEGRAM: Import TelegramBotService
use App\Services\Telegram\TelegramBotService;
use App\Jobs\PriceIndex\CalculatePriceIndexJob;

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

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  INDEX — Riwayat Transaksi (Rembush + Pengajuan)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function index(Request $request)
    {
        $query = Transaction::with(['submitter.bankAccounts', 'reviewer', 'branches'])->latest();

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

        // Type filter (rembush / pengajuan)
        if ($type = $request->input('type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        // Category filter
        if ($category = $request->input('category')) {
            if ($category !== 'all') {
                $query->where(function ($q) use ($category) {
                    $q->where('category', $category);
                    
                    // Also match legacy code if this is a known category name
                    $cat = TransactionCategory::where('name', $category)->first();
                    if ($cat && $cat->code) {
                        $q->orWhere('category', $cat->code);
                    }
                    
                    // Legacy purchase_reason column removed
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

        $query->withExists(['branches as has_branch_with_debt' => function($q) {
            $q->whereHas('debtsAsDebtor', function($sq) {
                $sq->where('status', 'pending');
            });
        }]);

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
        $t = Transaction::with(['submitter.bankAccounts', 'reviewer', 'branches', 'editor', 'branchDebts.debtorBranch', 'branchDebts.creditorBranch', 'konfirmator', 'payer'])->findOrFail($id);

        $paymentAt = ($t->konfirmasi_at instanceof \Carbon\Carbon) ? $t->konfirmasi_at->format('d M Y H:i') : null;
        if (!$paymentAt && $t->isCompleted() && $t->status === 'completed') {
            $paymentAt = $t->updated_at->format('d M Y H:i');
        }

        $submitterData = null;
        if ($t->submitter) {
            $primaryAccount = $t->submitter->bankAccounts->first();
            $submitterData = [
                'name'           => $t->submitter->name,
                'rekening_bank'  => $primaryAccount ? $primaryAccount->bank_name : '-',
                'rekening_nomor' => $primaryAccount ? $primaryAccount->account_number : '-',
                'rekening_nama'  => $primaryAccount ? $primaryAccount->account_name : '-',
            ];
        }

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
            'expected_total'  => $t->expected_total,
            'actual_total'    => $t->actual_total,
            'selisih'         => $t->selisih,
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
            'submitter'       => $submitterData,
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
            // ✅ Payment History (Riwayat Pembayaran) fields
            'payment_at'         => ($t->paid_at instanceof \Carbon\Carbon) ? $t->paid_at->format('d M Y H:i') : null,
            'paid_by_name'       => $t->payer ? $t->payer->name : ($t->reviewer ? $t->reviewer->name : 'Finance'),
            'paid_by_role'       => $t->payer ? ucfirst($t->payer->role) : ($t->reviewer ? ucfirst($t->reviewer->role) : 'Admin'),
            'recipient_name'     => $t->submitter ? $t->submitter->name : '-',
            'recipient_role'     => $t->submitter ? ucfirst($t->submitter->role) : 'Teknisi',
            'konfirmasi_by_name' => $t->konfirmator ? $t->konfirmator->name : null,
            'konfirmasi_by_role' => ($t->konfirmator && $t->konfirmator->role) ? ucfirst($t->konfirmator->role) : 'Teknisi',
            'konfirmasi_at'      => ($t->konfirmasi_at instanceof \Carbon\Carbon) ? $t->konfirmasi_at->format('d M Y H:i') : null,
            'payment_proof_url'  => ($t->bukti_transfer ?? $t->foto_penyerahan ?? $t->invoice_file_path) ? asset('storage/' . ($t->bukti_transfer ?? $t->foto_penyerahan ?? $t->invoice_file_path)) : null,
            'payment_type'       => $t->bukti_transfer ? 'Transfer' : ($t->foto_penyerahan ? 'Tunai' : ($t->invoice_file_path ? 'Invoice' : null)),
            'is_paid'            => (bool)($t->status === 'completed' || $t->status === 'approved' || $t->bukti_transfer || $t->foto_penyerahan || $t->invoice_file_path),
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

            // ✅ Role-Based Access: Admin = restricted (branch only), Teknisi = diblokir, Management = full edit (unless completed)
            $isAdminOnlyBranch = $user->isAdmin() && !$isCompleted;
            $isReadOnly = $user->isAdmin() || $isCompleted;

            $pengajuanCategories = TransactionCategory::forPengajuan()->active()->get();

            return view('transactions.edit-pengajuan', compact(
                'transaction', 'branches', 'itemCount', 'isReadOnly', 'isCompleted', 'pengajuanCategories', 'isAdminOnlyBranch'
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

        // ✅ Guard: Pengajuan — Owner, Atasan, dan Admin bisa update
        if ($transaction->isPengajuan()) {
            $canUpdate = $user->isOwner() || $user->isAtasan() || $user->isAdmin();
            if (!$canUpdate) {
                return redirect()->route('transactions.index')
                    ->with('error', 'Anda tidak memiliki akses untuk mengubah Pengajuan.');
            }
        }

        // Validation depends on type
        if ($transaction->isPengajuan()) {
            $rules = [
                'branches'        => 'nullable|array',
                'branches.*.branch_id' => 'required_with:branches|exists:branches,id',
                'branches.*.allocation_percent' => 'required_with:branches|numeric|min:0|max:100',
                'branches.*.allocation_amount' => 'nullable|numeric|min:0',
                'dpp_lainnya'     => 'nullable|integer|min:0',
                'tax_amount'      => 'nullable|integer|min:0',
                'biaya_layanan_1' => 'nullable|integer|min:0',
            ];

            // ✅ Only require items if NOT Admin (Management can edit everything)
            if (!$user->isAdmin()) {
                $rules = array_merge($rules, [
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
                ]);
            }

            $request->validate($rules, [
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
                // Bank details for transfer_penjual
                'bank_name'      => 'nullable|string|max:255',
                'account_name'   => 'nullable|string|max:255',
                'account_number' => 'nullable|numeric|digits_between:5,30',
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
                }
            }

            if ($transaction->isPengajuan()) {
                if (!$user->isAdmin()) {
                    $matchingService = app(\App\Services\PriceIndex\ItemMatchingService::class);
                    $items = array_map(function ($item) use ($matchingService) {
                        $rawCustomer = trim(preg_replace('/\s+/', ' ', $item['customer'] ?? ''));
                        $masterItemId = $item['master_item_id'] ?? null;
                        $categoryId = $item['category'] ?? null;

                        if (empty($masterItemId) && !empty($rawCustomer)) {
                            $bestMatch = $matchingService->findBestMatch($rawCustomer, $categoryId);
                            if ($bestMatch) {
                                $masterItemId = $bestMatch->id;
                            } else {
                                $newItem = $matchingService->createPendingItem($rawCustomer, $categoryId, Auth::id());
                                $masterItemId = $newItem->id;
                            }
                        }

                        if (!empty($masterItemId)) {
                            $master = \App\Models\MasterItem::find($masterItemId);
                            if ($master) {
                                $rawCustomer = $master->display_name;
                            }
                        }

                        return [
                            'master_item_id'  => $masterItemId,
                            'customer'        => $rawCustomer,
                            'vendor'          => $item['vendor']          ?? '',
                            'link'            => $item['link']            ?? '',
                            'category'        => $categoryId, 
                            'description'     => $item['description']     ?? '',
                            'specs'           => $item['specs']           ?? [],
                            'estimated_price' => intval($item['estimated_price'] ?? 0),
                            'quantity'        => intval($item['quantity']        ?? 1),
                        ];
                    }, $request->items);

                    $firstItem = $items[0] ?? [];
                    
                    $dppLainnya = intval($request->dpp_lainnya ?? 0);
                    $ppn = intval($request->tax_amount ?? 0);
                    $layanan1 = intval($request->biaya_layanan_1 ?? 0);

                    $totalAmount = collect($items)->sum(function($item) {
                        return ($item['estimated_price'] ?? 0) * ($item['quantity'] ?? 1);
                    }) + $dppLainnya + $ppn + $layanan1;

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
                        'dpp_lainnya'     => $dppLainnya,
                        'tax_amount'      => $ppn,
                        'biaya_layanan_1' => $layanan1,
                        'items'           => $items,
                    ]);
                } else {
                    Log::info('[ADMIN-EDIT] Admin updated branches for pengajuan', [
                        'transaction_id' => $transaction->id,
                        'admin_id'       => $user->id,
                    ]);
                }
            } else {
                $specs = null;
                if ($request->payment_method === 'transfer_penjual') {
                    $specs = [
                        'bank_name' => strtoupper($request->bank_name),
                        'account_name' => strtoupper($request->account_name),
                        'account_number' => $request->account_number,
                    ];
                }

                $transaction->update([
                    'customer'       => $request->customer,
                    'category'       => $request->category,
                    'description'    => $request->description,
                    'payment_method' => $request->payment_method,
                    'specs'          => $specs,
                    'amount'         => $request->amount,
                    'items'          => $request->items,
                    'date'           => $request->date ?? $transaction->date,
                ]);
            }

            // Sync branches
            $transaction->branches()->detach();
            if ($request->branches && count($request->branches) > 0) {
                $effectiveAmount = $transaction->amount;
                $totalAllocated  = 0;

                // 🔒 SECURITY FIX: Never trust allocation_amount from request.
                // Always recalculate from allocation_percent to prevent manipulation
                // where a user submits a valid percent (50%+50%=100%) but a tampered
                // amount (20k+20k ≠ 100k) causing untracked funds.
                $branchAttachData = [];
                foreach ($request->branches as $branchData) {
                    $allocPercent = floatval($branchData['allocation_percent']);
                    $allocAmount  = intval(round(($effectiveAmount * $allocPercent) / 100));
                    $totalAllocated += $allocAmount;

                    $branchAttachData[] = [
                        'id'                 => $branchData['branch_id'],
                        'allocation_percent' => $allocPercent,
                        'allocation_amount'  => $allocAmount,
                    ];
                }

                // ✅ Integrity check: Handle rounding differences on last branch caused by percentages (e.g. 33.33% x 3 = 99.99%)
                $diff = $effectiveAmount - $totalAllocated;
                
                // Tolerance: max of Rp 10,000 or 1% of transaction amount to cover percentage rounding gaps
                $tolerance = max(10000, $effectiveAmount * 0.01);
                
                if (abs($diff) > $tolerance) {
                    // Possible manipulation or frontend bug (sum of percentages is way off)
                    DB::rollBack();
                    return back()->withErrors([
                        'branches' => 'Total nominal alokasi cabang (Rp ' . number_format($totalAllocated) . ') terlalu jauh bedanya dengan total transaksi (Rp ' . number_format($effectiveAmount) . ').'
                    ])->withInput();
                }

                if (count($branchAttachData) > 0 && $diff != 0) {
                    $branchAttachData[count($branchAttachData) - 1]['allocation_amount'] += $diff;
                }

                foreach ($branchAttachData as $branch) {
                    $transaction->branches()->attach($branch['id'], [
                        'allocation_percent' => $branch['allocation_percent'],
                        'allocation_amount'  => $branch['allocation_amount'],
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
                // PENGAJUAN LOGIC — Dual-Gate untuk ≥ Rp 1.000.000
                // - Admin  : tidak berwenang approve
                // - < 1jt  : Single-gate → Atasan/Owner approve → waiting_payment
                // - ≥ 1jt  : Dual-gate:
                //     Gate 1: Atasan approve pending       → 'approved' (Menunggu Approve Owner)
                //     Gate 2: Owner  approve approved      → waiting_payment

                if ($newStatus === 'approved') {
                    if ($user->isAdmin()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Admin tidak berwenang menyetujui Pengajuan. Hanya Atasan dan Owner yang dapat menyetujui.',
                        ], 403);
                    }

                    $isLargeAmount = ($transaction->amount ?? 0) >= 1_000_000;

                    if ($isLargeAmount && !$user->isOwner() && $oldStatus === 'pending') {
                        // [GATE 1] Atasan approve pengajuan besar → eskalasi ke Owner
                        $newStatus = 'approved'; // tetap 'approved' = "Menunggu Approve Owner"

                        // 🔔 TELEGRAM: Notifikasi ke TEKNISI bahwa Admin/Atasan sudah OK, tinggal tunggu Owner
                        try {
                            $this->telegram->notifyWaitingOwnerApproval($transaction);
                        } catch (\Exception $e) {
                            Log::error('[TELEGRAM] Failed to send waiting owner approval notification', [
                                'transaction_id' => $transaction->id,
                                'error'          => $e->getMessage(),
                            ]);
                        }

                        Log::info('📋 [PENGAJUAN] Atasan approve ≥1jt → escalate to Owner', [
                            'transaction_id' => $transaction->id,
                            'amount'         => $transaction->amount,
                            'approved_by'    => $user->id,
                        ]);

                    } elseif ($isLargeAmount && $user->isOwner() && $oldStatus === 'approved') {
                        // [GATE 2] Owner approve setelah Atasan → lanjut ke pembayaran
                        $newStatus = 'waiting_payment';

                        // 🔔 TELEGRAM: Notif Teknisi bahwa Owner sudah approve
                        try {
                            $this->telegram->notifyPaymentProcessing($transaction);
                        } catch (\Exception $e) {
                            Log::error('[TELEGRAM] Failed to send owner-approved payment notification', [
                                'transaction_id' => $transaction->id,
                                'error'          => $e->getMessage(),
                            ]);
                        }

                        Log::info('✅ [PENGAJUAN] Owner approve gate 2 → waiting_payment', [
                            'transaction_id' => $transaction->id,
                            'approved_by'    => $user->id,
                        ]);

                    } else {
                        // [SINGLE GATE] Amount < 1jt atau Owner approve langsung dari pending
                        $newStatus = 'waiting_payment';

                        // 🔔 TELEGRAM: Notif Teknisi bahwa pengajuan disetujui
                        try {
                            $this->telegram->notifyPaymentProcessing($transaction);
                        } catch (\Exception $e) {
                            Log::error('[TELEGRAM] Failed to send single-gate approval notification', [
                                'transaction_id' => $transaction->id,
                                'error'          => $e->getMessage(),
                            ]);
                        }

                        Log::info('✅ [PENGAJUAN] Single-gate approve → waiting_payment', [
                            'transaction_id' => $transaction->id,
                            'amount'         => $transaction->amount,
                            'approved_by'    => $user->id,
                        ]);
                    }
                }

                // Status akan tetap Waiting Payment jika suatu transaksi pengajuan masih belum lunas
                // (belum ada invoice ATAU masih ada hutang antar cabang yang belum bayar)
                if ($newStatus === 'completed') {
                    $hasPendingDebts = \App\Models\BranchDebt::where('transaction_id', $transaction->id)
                        ->where('status', 'pending')
                        ->exists();

                    if (empty($transaction->invoice_file_path) || $hasPendingDebts) {
                        $newStatus = 'waiting_payment';
                    }
                }
            } elseif ($transaction->isGudang()) {
                // GUDANG LOGIC
                // Approval moves to 'waiting_payment' (Pembelanjaan Belum di bayar)
                if ($newStatus === 'approved') {
                    $newStatus = 'waiting_payment';
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

            // ✅ PRICE INDEX: Dispatch recalculation job saat Pengajuan disetujui
            // Trigger saat status berubah ke waiting_payment (setelah approve)
            // - oldStatus 'pending'  = single-gate (< 1jt) atau Owner approve langsung
            // - oldStatus 'approved' = gate 2 (Owner approve setelah Atasan, untuk ≥ 1jt)
            if ($transaction->isPengajuan() && $newStatus === 'waiting_payment'
                && in_array($oldStatus, ['pending', 'approved'])) {
                foreach (($transaction->items ?? []) as $item) {
                    $itemName = trim($item['customer'] ?? '');
                    if ($itemName !== '') {
                        dispatch(new CalculatePriceIndexJob($itemName, $item['category'] ?? null))
                            ->delay(now()->addSeconds(5))
                            ->onQueue('default');
                    }
                }
                Log::info('📊 [PriceIndex] Recalculation queued after pengajuan approval', [
                    'transaction_id' => $transaction->id,
                    'items_count'    => count($transaction->items ?? []),
                ]);
            }

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

            if ($newStatus === 'approved' && $transaction->isPengajuan()) {
                $statusLabelStr = 'MENUNGGU APPROVE OWNER';
            } else {
                $statusLabelStr = match($newStatus) {
                    'approved'  => 'MENUNGGU OWNER',
                    'rejected'  => 'DITOLAK',
                    'completed' => 'SELESAI',
                    'pending'   => 'PENDING',
                    'waiting_payment' => 'MENUNGGU PEMBAYARAN',
                    default     => strtoupper($newStatus),
                };
            }
            $statusLabel = $statusLabelStr;

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
    /**
     * Remove the specified transaction from storage.
     * Authorized for non-admin management (Owner, Atasan).
     */
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

            // Log activity
            $log = ActivityLog::create([
                'user_id'     => Auth::id(),
                'action'      => 'delete',
                'target_id'   => $invoiceNumber,
                'description' => "Menghapus secara permanen transaksi " . $invoiceNumber,
            ]);
            broadcast(new \App\Events\ActivityLogged($log));

            Log::info('Transaction deleted', [
                'transaction_id' => $id,
                'invoice_number' => $invoiceNumber,
                'deleted_by' => Auth::id(),
            ]);

            if (request()->ajax() || request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Transaksi {$invoiceNumber} berhasil dihapus"
                ]);
            }

            return back()->with('success', "Transaksi {$invoiceNumber} berhasil dihapus");

        } catch (\Exception $e) {
            Log::error('Transaction deletion failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $id,
            ]);

            if (request()->ajax() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus transaksi: ' . $e->getMessage()
                ], 500);
            }

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

            $request->validate([
                'payment_proof'          => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'notes'                  => 'nullable|string',
                'bank_account_id'        => 'required|exists:branch_bank_accounts,id',
                'sender_bank_account_id' => 'required|exists:branch_bank_accounts,id',
            ], [
                'payment_proof.required'          => 'Bukti transfer wajib diunggah.',
                'payment_proof.max'               => 'Bukti transfer maksimal 1MB.',
                'bank_account_id.required'        => 'Rekening tujuan wajib dipilih.',
                'sender_bank_account_id.required' => 'Rekening pengirim wajib dipilih.',
            ]);

            DB::beginTransaction();

            $path = null;
            if ($request->hasFile('payment_proof')) {
                $file = $request->file('payment_proof');
                $filename = 'debt_' . $debt->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('payment_proofs/debts', $filename, 'public');
            }

            $debt->markAsPaid($request->notes, $path, auth()->id(), $request->bank_account_id, $request->sender_bank_account_id);

            // Log activity
            ActivityLog::create([
                'user_id'        => auth()->id(),
                'action'         => 'settle_debt',
                'transaction_id' => $debt->transaction_id,
                'target_id'      => $debt->transaction->invoice_number ?? '-',
                'description'    => "Melunaskan hutang {$debt->debtorBranch->name} ke {$debt->creditorBranch->name} sebesar {$debt->formatted_amount}" . ($request->notes ? " (Catatan: {$request->notes})" : '') . " | Diproses oleh: " . auth()->user()->name,
            ]);

            // ✅ AUTO-COMPLETE TRANSACTION LOGIC
            // Jika ini adalah hutang terakhir yang dilunaskan, tandai transaksi induk sebagai Selesai
            $transaction = $debt->transaction;
            if ($transaction && $transaction->isPengajuan() && $transaction->status === 'waiting_payment') {
                $hasOtherPendingDebts = \App\Models\BranchDebt::where('transaction_id', $transaction->id)
                    ->where('status', 'pending')
                    ->exists();

                if (!$hasOtherPendingDebts && !empty($transaction->invoice_file_path)) {
                    $transaction->update(['status' => 'completed']);
                    
                    // Log completion
                    ActivityLog::create([
                        'user_id'        => auth()->id(),
                        'action'         => 'approve',
                        'transaction_id' => $transaction->id,
                        'target_id'      => $transaction->invoice_number,
                        'description'    => "Transaksi {$transaction->invoice_number} otomatis Selesai karena semua hutang antar cabang telah dilunaskan.",
                    ]);

                    broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));
                }
            }

            DB::commit();

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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors'  => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
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

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  GET ALL TRANSACTIONS FOR CLIENT-SIDE SEARCH
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * 🚀 NEW: Count endpoint for auto-mode detection
     * Returns total count based on filters WITHOUT loading data
     */
    // app/Http/Controllers/TransactionController.php

    /**
     * 🆕 Count endpoint - untuk auto-detection
     */
    public function count(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $query = Transaction::query();
        
        if ($user->role === 'teknisi') {
            $query->where('submitted_by', $user->id);
        } elseif ($user->role === 'atasan') {
            $query->where(function($q) use ($user) {
                $q->where('type', 'pengajuan')
                ->orWhere(function($subQ) use ($user) {
                    $subQ->whereIn('type', ['rembush', 'gudang'])
                        ->whereHas('submitter', function($userQ) use ($user) {
                            $userQ->where('atasan_id', $user->id);
                        });
                });
            });
        }

        $this->applyFilters($query, $request);
        return response()->json(['count' => $query->count()]);
    }

    public function stats(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $query = Transaction::query();

        if ($user->role === 'teknisi') {
            // ✅ Perbaikan: submitted_by bukan submitter_id
            $query->where('submitted_by', $user->id);
        } elseif ($user->role === 'atasan') {
            $query->where(function($q) use ($user) {
                $q->where('type', 'pengajuan')
                ->orWhere(function($subQ) use ($user) {
                    $subQ->whereIn('type', ['rembush', 'gudang'])
                        ->whereHas('submitter', function($userQ) use ($user) {
                            $userQ->where('atasan_id', $user->id);
                        });
                });
            });
        }

        $this->applyFilters($query, $request);

        $stats = [
            'all' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
            'waiting_payment' => (clone $query)->where('status', 'waiting_payment')->count(),
            'flagged' => (clone $query)->where('status', 'flagged')->count(),
            'auto_reject' => (clone $query)->where('status', 'auto-reject')->count(),
        ];

        return response()->json($stats);
    }
 
    /**
     * 🔄 OPTIMIZED: Search with pagination for server-side mode
     * Used when dataset > 5000 records
     */
    // app/Http/Controllers/TransactionController.php

    /**
     * 🆕 Server-side search dengan pagination
     */
    public function search(Request $request)
    {
        // ✅ Guard: Cek authentication
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $query = Transaction::with([
                'submitter:id,name,telegram_chat_id', 
                'branches:id,name'
            ])
            ->withExists(['branches as has_branch_with_debt' => function($q) {
                $q->whereHas('debtsAsDebtor', function($sq) {
                    $sq->where('status', 'pending');
                });
            }])
            ->select([
                'id', 'invoice_number', 'type', 'status',
                'amount', 'category',
                'created_at', 'submitted_by',
                'has_price_anomaly',
                'ai_status', 'upload_id', 'confidence',
                'payment_method', 'rejection_reason', 'specs'
            ]);

        // Role-based filtering
        if ($user->role === 'teknisi') {
            $query->where('submitted_by', $user->id);
        } elseif ($user->role === 'atasan') {
            $query->where(function($q) use ($user) {
                $q->where('type', 'pengajuan')
                ->orWhere(function($subQ) use ($user) {
                    $subQ->whereIn('type', ['rembush', 'gudang'])
                        ->whereHas('submitter', function($userQ) use ($user) {
                            $userQ->where('atasan_id', $user->id);
                        });
                });
            });
        }

        // Apply filters
        $this->applyFilters($query, $request);

        // Server-side search
        if ($request->filled('search')) {
            $searchTerm = strtolower($request->search);
            
            $query->where(function($q) use ($searchTerm) {
                $q->whereRaw('LOWER(invoice_number) LIKE ?', ["%{$searchTerm}%"])
                ->orWhereRaw('LOWER(category) LIKE ?', ["%{$searchTerm}%"])
                ->orWhereHas('submitter', function($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                })
                ->orWhereHas('branches', function($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                });
            });
        }

        // Paginate
        $perPage = min((int)$request->input('per_page', 20), 100); // ✅ Batasi max per_page
        $result = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform dengan error handling
        $result->getCollection()->transform(function($t) {
            try {
                return $this->transformTransaction($t);
            } catch (\Exception $e) {
                \Log::error('[transformTransaction] Error: ' . $e->getMessage(), [
                    'transaction_id' => $t->id ?? 'unknown'
                ]);
                // Return minimal data agar tidak crash
                return [
                    'id' => $t->id,
                    'invoice_number' => $t->invoice_number ?? 'N/A',
                    'search_text' => '',
                ];
            }
        });

        return response()->json($result);
    }
 
    /**
     * 🔄 OPTIMIZED: Search data for client-side mode
     * Used when dataset < 5000 records
     * Includes limit to prevent memory overflow
     */
    public function getAllForSearch(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $query = Transaction::with(['submitter:id,name,telegram_chat_id', 'branches:id,name'])
            ->withExists(['branches as has_branch_with_debt' => function($q) {
                $q->whereHas('debtsAsDebtor', function($sq) {
                    $sq->where('status', 'pending');
                });
            }])
            ->select([
                'id', 'invoice_number', 'type', 'status',
                'amount', 'category', 'date',
                'customer', 'vendor',
                'created_at', 'submitted_by',
                'has_price_anomaly', // ✅ Price Index
                'ai_status', 'upload_id', 'confidence',
                'payment_method', 'rejection_reason', 'specs',
                // ✅ Payment proof fields (required by status_label & frontend)
                'invoice_file_path', 'bukti_transfer', 'foto_penyerahan',
            ]);

        // Role-based filtering
        if ($user->role === 'teknisi') {
            $query->where('submitted_by', $user->id);
        } elseif ($user->role === 'atasan') {
            $query->where(function($q) use ($user) {
                $q->where('type', 'pengajuan')
                  ->orWhere(function($subQ) use ($user) {
                      $subQ->whereIn('type', ['rembush', 'gudang'])
                           ->whereHas('submitter', function($userQ) use ($user) {
                               $userQ->where('atasan_id', $user->id);
                           });
                  });
            });
        }
 
        // Apply filters
        $this->applyFilters($query, $request);
 
        // ⚠️ SAFETY LIMIT: Max 10k records for client-side
        $transactions = $query->orderBy('created_at', 'desc')
            ->limit(10000)
            ->get()
            ->map(function($t) {
                return $this->transformTransaction($t);
            });
 
        return response()->json($transactions);
    }
 
    /**
     * Helper: Apply common filters
     */
    /**
     * 🆕 Helper: Apply filters
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('branch_id')) {
            $branchIds = is_array($request->branch_id) ? $request->branch_id : [$request->branch_id];
            // Filter out 'all' strings just in case
            $branchIds = array_filter($branchIds, fn($id) => $id !== 'all');
            
            if (!empty($branchIds)) {
                $query->whereHas('branches', function($q) use ($branchIds) {
                    $q->whereIn('branches.id', $branchIds);
                });
            }
        }

        if ($request->filled('category')) {
            $categories = is_array($request->category) ? $request->category : [$request->category];
            $categories = array_filter($categories, fn($cat) => $cat !== 'all');
            
            if (!empty($categories)) {
                $query->where(function ($q) use ($categories) {
                    $q->whereIn('category', $categories);
                    
                    // Legacy support matching logic
                    foreach ($categories as $catName) {
                        $cat = TransactionCategory::where('name', $catName)->first();
                        if ($cat && $cat->code) {
                            $q->orWhere('category', $cat->code);
                        }
                        
                        // Legacy purchase_reason column removed
                    }
                });
            }
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
    }
 
    /**
     * Helper: Transform transaction for response
     */
    /**
     * 🆕 Helper: Transform transaction
     */
    private function transformTransaction($t)
    {
        $typeLabels = [
            'rembush' => 'Reimbursement',
            'pengajuan' => 'Pengajuan', 
            'gudang' => 'Belanja Gudang'
        ];

        $paymentLabels = [
            'transfer_teknisi' => 'Transfer ke Teknisi', 'transfer_penjual' => 'Transfer ke Penjual',
            'cash' => 'Tunai (Cash)', 'invoice' => 'Invoice (Pengajuan)'
        ];

        // ✅ Null-safe access untuk submitter
        $submitterName = $t->submitter?->name ?? '-';
        $submitterHasTelegram = $t->submitter?->telegram_chat_id ? true : false;
        
        // ✅ Null-safe access untuk branches
        $branchNames = $t->branches ? $t->branches->pluck('name')->toArray() : [];

        return [
            'id' => $t->id,
            'invoice_number' => $t->invoice_number,
            'type' => $t->type,
            'type_label' => $typeLabels[$t->type] ?? $t->type,
            'status' => $t->status,
            'status_label' => $t->status_label,
            'amount' => $t->amount,
            'effective_amount' => $t->effective_amount,
            'formatted_amount' => number_format($t->amount ?? 0, 0, ',', '.'),
            'category' => $t->category,
            'category_label' => $t->category_label,
            'created_at' => $t->created_at?->format('d M Y') ?? '-',
            'submitter_name' => $submitterName,
            'submitter_has_telegram' => $submitterHasTelegram,
            'submitter' => $t->submitter ? [
                'id' => $t->submitter->id,
                'name' => $t->submitter->name,
            ] : null,
            'branches' => $branchNames,
            'has_price_anomaly' => $t->has_price_anomaly ?? false,
            'ai_status' => $t->ai_status,
            'upload_id' => $t->upload_id,
            'confidence' => $t->confidence,
            'payment_method' => $t->payment_method,
            'payment_method_label' => $paymentLabels[$t->payment_method] ?? ucfirst(str_replace('_', ' ', $t->payment_method ?? '')),
            'rejection_reason' => $t->rejection_reason,
            'specs' => $t->specs,
            // Payment proof fields for frontend logic
            'invoice_file_path' => $t->invoice_file_path,
            'bukti_transfer'    => $t->bukti_transfer,
            'foto_penyerahan'   => $t->foto_penyerahan,
            // ✅ Search text optimized
            'search_text' => strtolower(implode(' ', array_filter([
                $t->invoice_number ?? '',
                $submitterName,
                $t->category_label ?? '',
                $typeLabels[$t->type] ?? $t->type ?? '',
                ...$branchNames
            ])))
        ];
    }
    
    // Helper methods (same as before)
   private function getTypeLabel($type)
    {
        return ['rembush' => 'Reimbursement', 'pengajuan' => 'Pengajuan', 'gudang' => 'Belanja Gudang'][$type] ?? $type;
    }

    private function getStatusLabel($status, $type = null)
    {
        $labels = [
            'pending' => $type === 'gudang' ? 'Review Management' : 'Pending',
            'approved' => 'Menunggu Owner',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
            'waiting_payment' => $type === 'gudang' ? 'Belum Dibayar' : 'Menunggu Pembayaran',
            'flagged' => 'Flagged (Selisih)',
            'auto-reject' => 'Auto Reject (AI)',
        ];
        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    private function getCategoryLabel($category)
    {
        $labels = ['bbm' => 'BBM', 'material' => 'Material', 'tools' => 'Tools & Equipment', 'service' => 'Service', 'transport' => 'Transport', 'other' => 'Lainnya'];
        return $labels[$category] ?? ucfirst($category);
    }

    private function getPaymentMethodLabel($method)
    {
        $labels = ['transfer_teknisi' => 'Transfer ke Teknisi', 'transfer_penjual' => 'Transfer ke Penjual', 'cash' => 'Tunai (Cash)', 'invoice' => 'Invoice (Pengajuan)'];
        return $labels[$method] ?? ucfirst(str_replace('_', ' ', $method));
    }

    /**
     * Get summary counts for each status based on current filters.
     */
    public function getTransactionStats(Request $request)
    {
        try {
            $query = Transaction::query();

            // Apply same filters as search (except status)
            if ($type = $request->input('type')) {
                if ($type !== 'all') $query->where('type', $type);
            }
            if ($category = $request->input('category')) {
                if ($category !== 'all') $query->where('category', $category);
            }
            if ($branchId = $request->input('branch_id')) {
                if ($branchId !== 'all') {
                    $query->whereHas('branches', function ($q) use ($branchId) {
                        $q->where('branches.id', $branchId);
                    });
                }
            }
            if ($startDate = $request->input('start_date')) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate = $request->input('end_date')) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $stats = $query->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $stats['all'] = array_sum($stats);

            return response()->json($stats);

        } catch (\Throwable $e) {
            Log::error('[getTransactionStats] Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  EXPORT — Laporan Bulanan Transaksi (Excel/XLSX)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    /**
     * Export transaksi sebagai Excel (.xlsx) dengan rumus pada kolom kalkulasi.
     *
     * Strategi:
     *  - REMBUSH / GUDANG / ALL : Menggunakan 17 kolom (Format Rembush)
     *  - PENGAJUAN              : Menggunakan 23 kolom (Format Pengajuan)
     *
     * Kolom kalkulasi (Total Estimasi, Grand Total, dll) menggunakan
     * rumus Excel agar interaktif saat dibuka di Excel/Google Sheets.
     */
    public function export(Request $request)
    {
        // ── Authorization ─────────────────────────────
        $user = Auth::user();

        // ── Filter Parameters ─────────────────────────
        $month    = $request->input('month');
        $year     = $request->input('year', now()->year);
        $type     = $request->input('type'); // rembush|pengajuan|gudang|all
        $status   = $request->input('status');
        $branchId = $request->input('branch_id');

        // ── Determine Export Format ───────────────────
        $exportFormat = ($type === 'pengajuan') ? 'pengajuan' : 'rembush';

        // ── Query ─────────────────────────────────────
        $query = Transaction::with(['submitter', 'reviewer', 'branches', 'payer', 'sumberDanaBranch'])
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc');

        if ($user->isTeknisi()) {
            $query->where('submitted_by', Auth::id());
        }
        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        $query->whereYear('created_at', $year);
        if ($month) {
            $query->whereMonth('created_at', $month);
        }
        if ($branchId && $branchId !== 'all') {
            $query->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            });
        }

        // ── Filename ──────────────────────────────────
        $periodLabel = $month
            ? \Carbon\Carbon::create($year, $month)->translatedFormat('F_Y')
            : "Full_{$year}";
        $formatLabel = ($exportFormat === 'pengajuan') ? '_Pengajuan' : '_Rembush';
        $filename    = "Laporan_Transaksi{$formatLabel}_{$periodLabel}.xlsx";

        // ── Build Spreadsheet ─────────────────────────
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('WHUSNET Admin Payment')
            ->setTitle("Laporan Transaksi {$formatLabel} {$periodLabel}")
            ->setSubject('Laporan Keuangan WHUSNET')
            ->setDescription('Generated by WHUSNET Admin Payment System');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Transaksi');

        // ── Column Definitions ────────────────────────
        $headers = $this->getExportHeaders($exportFormat);
        $colCount = count($headers);

        // ── Style Constants ───────────────────────────
        $headerBg    = 'FF1E3A5F'; // dark navy
        $headerFont  = 'FFFFFFFF'; // white
        $altRowBg    = 'FFEFF6FF'; // light blue
        $summaryBg   = 'FFFFE082'; // amber
        $formulaBg   = 'FFE8F5E9'; // light green — calculated columns
        $borderColor = 'FFB0BEC5';

        // ── Write Header Row ──────────────────────────
        foreach ($headers as $colIdx => $heading) {
            $col  = Coordinate::stringFromColumnIndex($colIdx + 1);
            $cell = $col . '1';
            $sheet->setCellValue($cell, $heading);
        }

        // Header styling
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => $headerFont], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $headerBg]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $borderColor]]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ── Freeze header pane ────────────────────────
        $sheet->freezePane('A2');

        // ── Write Data Rows ───────────────────────────
        $currentRow = 2;
        $totalTransactions   = 0;
        [$calcColLetters, $totalColLetter] = $this->getCalcColumnLetters($exportFormat);

        foreach ($query->cursor() as $t) {
            $totalTransactions++;
            $items = is_array($t->items) && count($t->items) > 0 ? $t->items : [[]];
            $transactionStartRow = $currentRow;

            foreach ($items as $idx => $item) {
                $isFirstRow = ($idx === 0);

                $rowData = $this->mapTransactionToRowData($t, $exportFormat, $item, $idx, $isFirstRow);

                // Write each cell
                foreach ($rowData as $colIdx => $value) {
                    $col  = Coordinate::stringFromColumnIndex($colIdx + 1);
                    $cell = $col . $currentRow;

                    // Inject Excel formula for calculated columns
                    $heading = $headers[$colIdx] ?? '';
                    if ($this->isCalculatedColumn($heading, $exportFormat)) {
                        $formula = $this->buildFormula($heading, $exportFormat, $currentRow, $isFirstRow);
                        if ($formula) {
                            $sheet->setCellValue($cell, $formula);
                            // Format as number
                            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($formulaBg);
                        } else {
                            $sheet->setCellValue($cell, $value);
                        }
                    } elseif (is_numeric($value) && $value !== '' && in_array($heading, $this->getCurrencyColumns($exportFormat))) {
                        $sheet->setCellValueExplicit($cell, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    } else {
                        $sheet->setCellValue($cell, $value);
                    }
                }

                // Alternate row color
                $rowBg = ($totalTransactions % 2 === 0) ? $altRowBg : 'FFFFFFFF';
                // Only apply bg to non-formula cells (formula cells already have formulaBg)
                foreach ($rowData as $colIdx => $value) {
                    $heading = $headers[$colIdx] ?? '';
                    if (!$this->isCalculatedColumn($heading, $exportFormat)) {
                        $col = Coordinate::stringFromColumnIndex($colIdx + 1);
                        $sheet->getStyle($col . $currentRow)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($rowBg);
                    }
                }

                // Row border
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setARGB($borderColor);

                $currentRow++;
            }

            // No longer need grandTotalAmounts collection as we sum the column range
        }

        $dataLastRow = $currentRow - 1;

        // ── Auto Column Width ─────────────────────────
        foreach (range(1, $colCount) as $colIdx) {
            $col = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Summary Section ───────────────────────────
        $summaryRow = $currentRow + 1;
        $sheet->setCellValue("A{$summaryRow}", '── RINGKASAN ──');
        $sheet->getStyle("A{$summaryRow}:{$lastCol}{$summaryRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $summaryBg]],
        ]);

        $sheet->setCellValue('A' . ($summaryRow + 1), 'Total Transaksi');
        $sheet->setCellValue('B' . ($summaryRow + 1), $totalTransactions);
        $sheet->getStyle('B' . ($summaryRow + 1))->getFont()->setBold(true);

        // Grand Total — use SUM formula if data rows exist
        $sheet->setCellValue('A' . ($summaryRow + 2), 'Grand Total');
        if ($totalColLetter && $dataLastRow >= 2) {
            $sheet->setCellValue('B' . ($summaryRow + 2), "=SUM({$totalColLetter}2:{$totalColLetter}{$dataLastRow})");
        } else {
            $sheet->setCellValue('B' . ($summaryRow + 2), 0);
        }
        $sheet->getStyle('B' . ($summaryRow + 2))->getNumberFormat()->setFormatCode('"Rp "#,##0');
        $sheet->getStyle('A' . ($summaryRow + 2) . ':B' . ($summaryRow + 2))->getFont()->setBold(true);
        $sheet->getStyle('A' . ($summaryRow + 2) . ':B' . ($summaryRow + 2))->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($summaryBg);

        // ── Stream Response ───────────────────────────
        $httpHeaders = [
            'Content-Type'                  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition'           => "attachment; filename=\"{$filename}\"",
            'Pragma'                        => 'no-cache',
            'Cache-Control'                 => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'                       => '0',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
        ];

        $writer = new Xlsx($spreadsheet);

        return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, $httpHeaders);
    }

    /**
     * Kembalikan [calcColLetters[], totalColLetter] berdasarkan format.
     * calcColLetters : kolom-kolom yang berisi FORMULA
     * totalColLetter : kolom "Total Nominal" / main amount untuk SUM grand total
     */
    private function getCalcColumnLetters($format): array
    {
        if ($format === 'pengajuan') {
            // Headers: A=Sumber Dana, B=Cabang Berhutang, C=Invoice, D=tanggal, E=bulan,
            // F=kategori, G=Nama Vendor, H=Link, I=Merk, J=Tipe/Seri, K=Ukuran, L=Warna,
            // M=Keterangan, N=Harga Satuan, O=Jumlah, P=Total,
            // Q=Ongkir, R=Diskon Pengiriman, S=Voucher, T=Biaya Layanan 1, U=Biaya Layanan 2,
            // V=DPP Lainnya, W=PPN, X=Total Estimasi, Y=cash/bank, Z=status
            return [['Total', 'Total Estimasi'], 'X']; // Grand Total sums column X (Total Estimasi)
        }

        // Rembush: A=Cabang, B=Invoice, C=tanggal, D=bulan, E=kategori, F=nama vendor,
        // G=Metode, H=Nama Barang, I=Qty, J=Satuan, K=Harga Satuan, L=Total,
        // M=Deskripsi, N=Total Nominal, O=Met. Distribusi, P=cash/bank, Q=status
        return [['Total'], 'N']; // Grand Total sums column N (Total Nominal)
    }

    /**
     * Apakah heading ini adalah kolom kalkulasi (akan diisi rumus Excel)?
     */
    private function isCalculatedColumn(string $heading, string $format): bool
    {
        $calculated = [
            'pengajuan' => ['Total', 'Total Estimasi'],
            'rembush'   => ['Total'],
        ];
        return in_array($heading, $calculated[$format] ?? []);
    }

    /**
     * Build Excel formula untuk kolom kalkulasi.
     *
     * Pengajuan:
     *  - Total Estimasi (P)  = Harga Satuan (N) × Jumlah (O)  →  =N{row}*O{row}
     *
     * Rembush:
     *  - Total (L)           = Qty (I) × Harga Satuan (K)     →  =I{row}*K{row}
     */
    private function buildFormula(string $heading, string $format, int $row, bool $isFirstRow = true): ?string
    {
        if ($format === 'pengajuan') {
            if ($heading === 'Total') return "=N{$row}*O{$row}";
            
            if ($heading === 'Total Estimasi') {
                // If it's the first row of a transaction, include adjustments
                // Total Estimasi (X) = Total (P) + Ongkir (Q) - Diskon (R) - Voucher (S) + Service1 (T) + Service2 (U) + DPP (V) + PPN (W)
                if ($isFirstRow) {
                    return "=P{$row}+Q{$row}-R{$row}-S{$row}+T{$row}+U{$row}+V{$row}+W{$row}";
                }
                // Subsequent rows only contribute their base price * qty
                return "=P{$row}";
            }
        } else {
            // rembush
            if ($heading === 'Total') return "=I{$row}*K{$row}";
        }
        return null;
    }

    /**
     * Kolom yang formatnya currency (Rupiah) — hanya yang bukan formula.
     */
    private function getCurrencyColumns(string $format): array
    {
        if ($format === 'pengajuan') {
            return ['Harga Satuan', 'Total', 'Ongkir', 'Diskon Pengiriman', 'Voucher Diskon', 'Biaya Layanan 1', 'Biaya Layanan 2', 'DPP Lainnya', 'PPN', 'Total Estimasi'];
        }
        return ['Harga Satuan', 'Total Nominal'];
    }

    /**
     * Get Excel headers based on export format
     */
    private function getExportHeaders($format)
    {
        if ($format === 'pengajuan') {
            return [
                'Sumber Dana Cabang',   // A
                'Cabang Berhutang',     // B
                'Invoice Number',       // C
                'tanggal',             // D
                'bulan',               // E
                'kategori',            // F
                'Nama Vendor',         // G
                'Link Rekomendasi',    // H
                'Merk',                // I
                'Tipe/Seri',           // J
                'Ukuran',              // K
                'Warna',               // L
                'Keterangan',          // M
                'Harga Satuan',        // N
                'Jumlah',              // O
                'Total',               // P  ← FORMULA: =N*O
                'Ongkir',              // Q
                'Diskon Pengiriman',   // R
                'Voucher Diskon',      // S
                'Biaya Layanan 1',     // T
                'Biaya Layanan 2',     // U
                'DPP Lainnya',         // V
                'PPN',                 // W
                'Total Estimasi',      // X  ← FORMULA: =P+Q-R-S+T+U+V+W
                'cash/bank pembayar',  // Y
                'status',              // Z
            ];
        }

        // Default: Rembush format (17 columns)
        return [
            'Cabang',             // A
            'Invoice Number',     // B
            'tanggal',           // C
            'bulan',             // D
            'kategori',          // E
            'nama vendor',       // F
            'Metode Pencairan',  // G
            'Nama Barang',       // H
            'Qty',               // I
            'Satuan',            // J
            'Harga Satuan',      // K
            'Total',             // L  ← FORMULA: =I*K
            'Deskripsi',         // M
            'Total Nominal',     // N
            'Metode Distribusi', // O
            'cash/bank pembayar',// P
            'status',            // Q
        ];
    }

    /**
     * Map transaction data to array of values (no formula — formulas injected separately).
     * For calculated columns, we return the raw numeric value as fallback,
     * but the export() method will override with the Excel formula.
     */
    private function mapTransactionToRowData($t, $format, $item, $idx, bool $isFirstRow): array
    {
        $dateStr   = $t->date ? $t->date->format('d/m/Y') : '-';
        $monthStr  = $t->date ? $t->date->translatedFormat('F') : '-';
        $payerName = $t->payer->name ?? ($t->reviewer->name ?? '-');

        if ($format === 'pengajuan') {
            $cabangBerhutang = $t->branches
                ->filter(fn($b) => $b->id != $t->sumber_dana_branch_id)
                ->pluck('name')
                ->join(', ');

            $specs = $item['specs'] ?? [];
            $qty   = (float) ($item['quantity'] ?? ($item['qty'] ?? 0));
            $price = (float) ($item['estimated_price'] ?? ($item['harga_satuan'] ?? 0));

            return [
                $isFirstRow ? ($t->sumberDanaBranch->name ?? '-') : '',  // A
                $isFirstRow ? ($cabangBerhutang ?: '-') : '',             // B
                $isFirstRow ? $t->invoice_number : '',                    // C
                $isFirstRow ? $dateStr : '',                              // D
                $isFirstRow ? $monthStr : '',                             // E
                $isFirstRow ? $t->category_label : '',                    // F
                $item['vendor'] ?? '-',                                   // G
                $item['link'] ?? '-',                                     // H
                $specs['merk'] ?? '-',                                    // I
                $specs['tipe'] ?? ($specs['tipe_seri'] ?? '-'),           // J
                $specs['ukuran'] ?? '-',                                  // K
                $specs['warna'] ?? '-',                                   // L
                $item['description'] ?? ($item['customer'] ?? '-'),       // M
                $price,                                                   // N  Harga Satuan
                $qty,                                                     // O  Jumlah
                $price * $qty,                                            // P  Total (overridden by formula)
                $isFirstRow ? (float) ($t->ongkir ?? 0) : '',            // Q
                $isFirstRow ? (float) ($t->diskon_pengiriman ?? 0) : '', // R
                $isFirstRow ? (float) ($t->voucher_diskon ?? 0) : '',    // S
                $isFirstRow ? (float) ($t->biaya_layanan_1 ?? 0) : '',   // T
                $isFirstRow ? (float) ($t->biaya_layanan_2 ?? 0) : '',   // U
                $isFirstRow ? (float) ($t->dpp_lainnya ?? 0) : '',       // V
                $isFirstRow ? (float) ($t->tax_amount ?? 0) : '',        // W
                0,                                                        // X  Total Estimasi (overridden by formula)
                $isFirstRow ? $payerName : '',                            // Y
                $isFirstRow ? $t->status_label : '',                      // Z
            ];
        }

        // Rembush / Gudang / All format
        $branchNames = $t->branches->pluck('name')->join(', ');
        $qty   = (float) ($item['qty'] ?? ($item['quantity'] ?? 0));
        $price = (float) ($item['harga_satuan'] ?? ($item['estimated_price'] ?? 0));

        return [
            $isFirstRow ? $branchNames : '',                                                         // A
            $isFirstRow ? $t->invoice_number : '',                                                   // B
            $isFirstRow ? $dateStr : '',                                                             // C
            $isFirstRow ? $monthStr : '',                                                            // D
            $isFirstRow ? $t->category_label : '',                                                   // E
            $isFirstRow ? ($t->vendor ?? ($t->vendor_name ?? '-')) : '',                            // F
            $isFirstRow ? (Transaction::PAYMENT_METHODS[$t->payment_method] ?? $t->payment_method_label) : '', // G
            $item['nama_barang'] ?? ($item['customer'] ?? '-'),                                      // H
            $qty,                                                                                    // I  Qty
            $item['satuan'] ?? '-',                                                                  // J
            $price,                                                                                  // K  Harga Satuan
            $qty * $price,        // L  Total (overridden by formula)
            $isFirstRow ? ($t->description ?? '-') : '',                                             // M
            $isFirstRow ? (float) $t->amount : '',                                                   // N  Total Nominal
            $isFirstRow ? $this->deduceDistributionMethod($t) : '',                                  // O
            $isFirstRow ? $payerName : '',                                                           // P
            $isFirstRow ? $t->status_label : '',                                                     // Q
        ];
    }

    /**
     * Deduce the distribution method used for the transaction
     */
    private function deduceDistributionMethod($t)
    {
        if ($t->branches->isEmpty()) return '-';

        $percents = $t->branches->pluck('pivot.allocation_percent')->map(fn($p) => round((float)$p, 2))->unique();

        if ($percents->count() === 1) return 'Bagi Rata';

        $isAllIntegers = $t->branches->every(function ($b) {
            $p = $b->pivot->allocation_percent;
            return floor($p) == $p;
        });

        return $isAllIntegers ? 'Persentase' : 'Manual';
    }
}