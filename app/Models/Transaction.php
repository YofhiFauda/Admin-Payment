<?php

namespace App\Models;

use App\Services\IdGeneratorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Transaction extends Model
{
    // ─── Type Constants ───────────────────────────────
    const TYPE_REMBUSH = 'rembush';
    const TYPE_PENGAJUAN = 'pengajuan';
    const TYPE_GUDANG = 'gudang';

    const TYPES = [
        self::TYPE_REMBUSH => 'Rembush',
        self::TYPE_PENGAJUAN => 'Pengajuan',
        self::TYPE_GUDANG => 'Gudang',
    ];

    // ─── Payment Methods (Rembush) ────────────────────
    const PAYMENT_METHODS = [
        'transfer_teknisi' => 'Transfer ke Teknisi',
        'cash' => 'Cash',
        'transfer_penjual' => 'Transfer ke Penjual',
    ];

    /**
     * ✅ Appended fields for JSON serialization & Real-time Broadcasting.
     * Ensures accessors like status_label are included in the broadcasted object.
     */
    protected $appends = ['status_label', 'category_label', 'type_label'];

    protected $fillable = [
        'type',
        'invoice_number',
        'customer',
        'category',
        'description',
        'payment_method',
        'amount',
        'items',
        'date',
        'file_path',
        'status',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'ai_status',
        'confidence',
        // Pengajuan fields
        'vendor',
        'link',
        'specs',
        'quantity',
        'estimated_price',
        'upload_id',
        'trace_id',
        // Payment & OCR fields
        'expected_total',
        'actual_total',
        'selisih',
        'ocr_result',
        'flag_reason',
        'ocr_confidence',
        'konfirmasi_by',
        'konfirmasi_at',
        'pembayaran_id',
        'foto_penyerahan',
        'bukti_transfer',
        'overall_confidence',
        'confidence_label',
        'field_confidence',
        // Invoice Payment fields
        'invoice_file_path',
        'tax_amount',
        'discount_amount',
        'diskon_pengiriman',
        'ongkir',
        'biaya_layanan_1',
        'biaya_layanan_2',
        'voucher_diskon',
        'sumber_dana_branch_id',
        'sumber_dana_data',
        // ✅ Versioning fields (Dual-Version System)
        'items_snapshot',
        'is_edited_by_management',
        'edited_by',
        'edited_at',
        'revision_count',
        'paid_by',
        'paid_at',
        'dpp_lainnya',
        // ✅ Price Index fields
        'has_price_anomaly',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'estimated_price' => 'integer',
            'quantity' => 'integer',
            'date' => 'date',
            'reviewed_at' => 'datetime',
            'items' => 'array',
            'specs' => 'array',
            'confidence' => 'integer',
            'overall_confidence' => 'integer',
            'field_confidence' => 'array',
            'ocr_result' => 'array',
            // ✅ NEW: Versioning casts
            'items_snapshot' => 'array',
            'is_edited_by_management' => 'boolean',
            'edited_at' => 'datetime',
            'revision_count' => 'integer',
            'paid_at' => 'datetime',
            'konfirmasi_at' => 'datetime',
            // ✅ Multi Sumber Dana
            'sumber_dana_data' => 'array',
            // ✅ Price Index
            'has_price_anomaly' => 'boolean',
            // ✅ Monetary Breakdown
            'tax_amount' => 'integer',
            'discount_amount' => 'integer',
            'ongkir' => 'integer',
            'biaya_layanan_1' => 'integer',
            'biaya_layanan_2' => 'integer',
            'dpp_lainnya' => 'integer',
            'voucher_diskon' => 'integer',
            'diskon_pengiriman' => 'integer',
        ];
    }

     /**
     * Anomali harga yang terdeteksi pada pengajuan ini.
     */
    public function priceAnomalies()
    {
        return $this->hasMany(PriceAnomaly::class);
    }

    /**
     * Model Events
     */
    protected static function booted()
    {
        $clearCache = function ($transaction) {
            // Clear global stats cache (for Admin/Owner)
            Cache::forget('transactions_stats_global');

            // Clear specific user stats cache (for Teknisi)
            if ($transaction->submitted_by) {
                Cache::forget("transactions_stats_teknisi_{$transaction->submitted_by}");
            }
        };

        static::created($clearCache);
        static::updated($clearCache);
        static::deleted($clearCache);

        // Auto-calculate discrepancy (selisih) and ensure expected_total is set
        static::saving(function ($transaction) {
            // Ensure expected_total has a fallback to the approved amount
            if (is_null($transaction->expected_total)) {
                $transaction->expected_total = $transaction->effective_amount;
            }

            // Always recalculate selisih if actual_total is present
            if (!is_null($transaction->actual_total)) {
                $transaction->selisih = $transaction->expected_total - $transaction->actual_total;
            }
        });
    }

    // ─── ID Generators (delegates to IdGeneratorService) ────────────
    public static function generateInvoiceNumber(): string
    {
        return IdGeneratorService::nextInvoiceNumber();
    }

    public static function generateUploadId(): string
    {
        return IdGeneratorService::nextUploadId();
    }

    public static function generateTraceId(): string
    {
        return IdGeneratorService::nextTraceId();
    }

    // ─── Relationships ────────────────────────────────
    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by')
                    ->withDefault([
                        'id'              => null,
                        'name'            => '[Akun Dihapus]',
                        'telegram_chat_id'=> null,
                    ]);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function konfirmator()
    {
        return $this->belongsTo(User::class, 'konfirmasi_by')
                    ->withDefault([
                        'id'   => null,
                        'name' => null,
                        'role' => null,
                    ]);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'transaction_branches')
                    ->withPivot('allocation_percent', 'allocation_amount')
                    ->withTimestamps();
    }

    public function sumberDanaBranch()
    {
        return $this->belongsTo(Branch::class, 'sumber_dana_branch_id');
    }

    /**
     * Hutang antar cabang yang dihasilkan dari pembayaran Pengajuan ini.
     */
    public function branchDebts()
    {
        return $this->hasMany(BranchDebt::class);
    }

    /**
     * The management user who last edited this Pengajuan.
     * Used by the dual-version system.
     */
    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by')
                    ->withDefault([
                        'id'   => null,
                        'name' => null,
                        'role' => null,
                    ]);
    }

    // ─── Type Helpers ─────────────────────────────────
    public function isRembush(): bool { return $this->type === self::TYPE_REMBUSH; }
    public function isPengajuan(): bool { return $this->type === self::TYPE_PENGAJUAN; }
    public function isGudang(): bool { return $this->type === self::TYPE_GUDANG; }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    // ─── Status Helpers ───────────────────────────────
    public function isPending(): bool { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'waiting_payment') {
            // ✅ OPTIMIZATION: Check the pre-fetched withExists attribute first (zero extra queries).
            $hasPendingDebt = array_key_exists('has_branch_with_debt', $this->attributes)
                ? (bool) $this->attributes['has_branch_with_debt']
                : (
                    $this->relationLoaded('branchDebts')
                        ? $this->branchDebts->contains('status', 'pending')
                        : $this->branchDebts()->where('status', 'pending')->exists()
                );

            // ✅ Fix: Terminology "Pelunasan" (Settlement) is specialized for Pengajuan and Gudang.
            // For Rembush, we use simpler terms since it's a reimbursement of money already spent.
            if (!$this->isRembush()) {
                // Only show "Menunggu Pelunasan" if payment documentation exists or this transaction specifically generated debt records.
                // We removed the global hasAnyPendingBranchDebt check from here to ensure the label starts as "Menunggu Pembayaran" after approval.
                if ($hasPendingDebt || $this->invoice_file_path || $this->bukti_transfer || $this->foto_penyerahan) {
                    return 'Menunggu Pelunasan';
                }

                if ($this->isGudang()) {
                    return 'Pembelanjaan Belum di bayar';
                }

                return 'Menunggu Pembayaran';
            }

            // ✅ Logic for Rembush in 'waiting_payment' status:
            // If office has uploaded proof of transfer/cash, it's waiting for technician confirmation.
            if ($this->bukti_transfer || $this->foto_penyerahan) {
                return 'Menunggu Konfirmasi';
            }

            return 'Menunggu Pembayaran';
        }

        if ($this->isGudang()) {
            if ($this->status === 'pending') {
                return 'Review Management';
            }
        }
        
        if ($this->isPengajuan() && $this->status === 'approved') {
            return 'Menunggu Approve Owner';
        }

        return match ($this->status) {
            'pending'   => 'Pending',
            'approved'  => 'Menunggu Owner',
            'completed' => 'Selesai',
            'rejected'  => 'Ditolak',
            null        => 'Draft',
            default     => (string) $this->status,
        };
    }

    /**
     * Check if any of the branches involved in this transaction have any pending inter-branch debts.
     */
    public function hasAnyPendingBranchDebt(): bool
    {
        if (array_key_exists('has_branch_with_debt', $this->attributes)) {
            return (bool) $this->attributes['has_branch_with_debt'];
        }

        if ($this->relationLoaded('branches')) {
            foreach ($this->branches as $branch) {
                if ($branch->relationLoaded('debtsAsDebtor')) {
                    if ($branch->debtsAsDebtor->where('status', 'pending')->isNotEmpty()) {
                        return true;
                    }
                } else {
                    if ($branch->debtsAsDebtor()->where('status', 'pending')->exists()) {
                        return true;
                    }
                }
            }
            return false;
        }

        return \App\Models\BranchDebt::where('transaction_id', $this->id)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Determine if the transaction is in the settlement phase (Menunggu Pelunasan).
     * This phase happens after approval, specifically when invoices are uploaded or inter-branch debts exist.
     */
    public function isSettlementPhase(): bool
    {
        // 🔒 Settlement phase only applies to non-rembush 'waiting_payment' status
        if ($this->status !== 'waiting_payment' || $this->isRembush()) {
            return false;
        }

        // Logic must match getStatusLabelAttribute condition for "Menunggu Pelunasan"
        $hasPendingDebt = array_key_exists('has_branch_with_debt', $this->attributes)
            ? (bool) $this->attributes['has_branch_with_debt']
            : $this->branchDebts()->where('status', 'pending')->exists();

        return (bool) ($hasPendingDebt || 
               $this->invoice_file_path || 
               $this->bukti_transfer || 
               $this->foto_penyerahan);
    }


    /**
     * Get the effective amount for display and approval logic.
     * Both Rembush and Pengajuan now use the `amount` field for the total value.
     */
    public function getEffectiveAmountAttribute(): int
    {
        return $this->amount ?? 0;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->effective_amount, 0, ',', '.');
    }

    public static function formatShortRupiah($value)
    {
        if ($value >= 1000000000000) {
            return 'Rp ' . rtrim(rtrim(number_format($value / 1000000000000, 2, ',', '.'), '0'), ',') . ' T';
        }

        if ($value >= 1000000000) {
            return 'Rp ' . rtrim(rtrim(number_format($value / 1000000000, 2, ',', '.'), '0'), ',') . ' M';
        }

        if ($value >= 1000000) {
            return 'Rp ' . rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',') . ' Jt';
        }

        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    /**
     * Standardize response data shape for frontend search and real-time broadcasting.
     */
    // ─── Category Accessor (Backward Compatibility) ────────────────

    /**
     * Resolve category label.
     * After migration, category stores the human-readable label directly.
     * This accessor handles cases where old snake_case keys may still exist.
     */
    public function getCategoryLabelAttribute(): string
    {
        $category = $this->category;

        // Fallback for Pengajuan: pick from first item if main category is null
        // This handles transactions created before the controller fix.
        if (!$category && $this->isPengajuan() && is_array($this->items) && isset($this->items[0]['category'])) {
            $category = $this->items[0]['category'];
        }

        return TransactionCategory::resolveLabel($category, $this->type);
    }

    /**
     * Normalize items array for backward compatibility.
     * Old Pengajuan items had 'purchase_reason' key (snake_case code).
     * New items use 'category' key (human-readable label).
     * This accessor returns normalized items regardless of format.
     */
    public function getNormalizedItemsAttribute(): ?array
    {
        $items = $this->items;
        if (!is_array($items)) {
            return $items;
        }

        return array_map(function (array $item) {
            // Normalize: if item has 'purchase_reason' key (old format), convert to 'category'
            if (isset($item['purchase_reason']) && !isset($item['category'])) {
                $item['category'] = TransactionCategory::resolveLabel($item['purchase_reason'], $this->type);
                unset($item['purchase_reason']);
            }
            return $item;
        }, $items);
    }

    public function toSearchArray(): array
    {
        // Hubungan (submitter, reviewer, branches) HARUS dipastikan diload sebelumnya via `with()` 
        // untuk menghindari N+1 query yang masif. 
        // Jangan panggil $this->loadMissing() di sini bila sedang memproses banyak data (seperti di getAllForSearch).

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'submitter_name' => $this->submitter->name ?? '-',
            'customer' => $this->customer ?? '',
            'vendor' => $this->vendor ?? '',
            'type' => $this->type,
            'type_label' => $this->type_label,
            'category' => $this->category,
            'category_label' => $this->category_label,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'amount' => $this->amount,
            'formatted_amount' => number_format($this->amount ?? 0, 0, ',', '.'),
            'date' => $this->date ? \Carbon\Carbon::parse($this->date)->format('d M Y') : null,
            'created_at' => $this->created_at->format('d M Y'),
            'created_at_search' => $this->created_at->format('d-m-Y Y-m-d'),
            'ai_status' => $this->ai_status,
            'payment_method' => $this->payment_method,
            'specs' => $this->specs,
            'submitter' => $this->submitted_by ? [
                'id' => $this->submitter->id,
                'name' => $this->submitter->name,
                'rekening_bank'  => $this->submitter->relationLoaded('bankAccounts') && $this->submitter->bankAccounts->first() ? $this->submitter->bankAccounts->first()->bank_name : '-',
                'rekening_nomor' => $this->submitter->relationLoaded('bankAccounts') && $this->submitter->bankAccounts->first() ? $this->submitter->bankAccounts->first()->account_number : '-',
                'rekening_nama'  => $this->submitter->relationLoaded('bankAccounts') && $this->submitter->bankAccounts->first() ? $this->submitter->bankAccounts->first()->account_name : '-',
            ] : null,
            'branches' => $this->branches->map(function($b) {
                return $b->name;
            })->toArray(),
            'upload_id' => $this->upload_id,
            'confidence' => $this->confidence,
            'submitter_has_telegram' => (bool) ($this->submitter->telegram_chat_id ?? false),
            'rejection_reason' => $this->rejection_reason,
            'effective_amount' => $this->effective_amount,
            // category now unified — alias purchase_reason_label for frontend compat
            'purchase_reason' => $this->category,
            'purchase_reason_label' => $this->category_label,
            // ✅ Versioning fields for Detail Modal
            'is_edited_by_management' => (bool) $this->is_edited_by_management,
            'revision_count' => $this->revision_count ?? 0,
            'edited_at' => $this->edited_at ? $this->edited_at->format('d M Y, H:i') : null,
            'editor_name' => $this->relationLoaded('editor') && $this->editor ? $this->editor->name : null,
            'items' => $this->normalized_items,
            'items_snapshot' => $this->getOriginalVersion(),
            'changes' => $this->getItemChanges(),
            // Search string untuk matching
            'search_text' => strtolower(
                ($this->submitter->name ?? '') . ' ' .
                $this->invoice_number . ' ' .
                ($this->customer ?? '') . ' ' .
                ($this->vendor ?? '') . ' ' .
                $this->created_at->format('d M Y d-m-Y Y-m-d')
            ),
            'has_price_anomaly' => false, // Implement your logic
            'invoice_file_path' => $this->invoice_file_path,
            'bukti_transfer' => $this->bukti_transfer,
            'foto_penyerahan' => $this->foto_penyerahan,
        ];
    }

    /**
     * Versi Ringan (Lean) untuk Client-side Search Engine.
     *
     * Mengecualikan items & changes untuk efisiensi memory saat memproses ribuan record.
     * Semua property diakses secara defensive untuk menghindari NullPointerException
     * pada record yang bermasalah/corrupt.
     */
    public function toLeanSearchArray(): array
    {
        $createdAt = $this->created_at;

        // Note: search_text dihapus untuk menghemat RAM (OOM PREVENTION) pada 18k+ records.
        // Frontend SearchEngine tetap bisa mencari berdasarkan kolom individu.
        return [
            'id'                    => $this->id,
            'invoice_number'        => $this->invoice_number,
            'submitter_name'        => $this->submitter->name ?? '-',
            'customer'              => $this->customer ?? '',
            'vendor'                => $this->vendor ?? '',
            'type'                  => $this->type,
            'type_label'            => $this->type_label,
            'category'              => $this->category,
            'category_label'        => $this->category_label,
            'status'                => $this->status,
            'status_label'          => $this->status_label,
            'amount'                => $this->amount,
            'formatted_amount'      => number_format($this->amount ?? 0, 0, ',', '.'),
            'date'                  => $this->date ? \Carbon\Carbon::parse($this->date)->format('d M Y') : null,
            'created_at'            => $createdAt ? $createdAt->format('d M Y') : '-',
            'ai_status'             => $this->ai_status,
            'upload_id'             => $this->upload_id,
            'submitter_has_telegram'=> (bool) ($this->submitter->telegram_chat_id ?? false),
            'branches'              => $this->branches->pluck('name')->toArray(),
            'has_price_anomaly'     => (bool) ($this->has_price_anomaly ?? false),
            'confidence'            => $this->confidence,
            'effective_amount'      => $this->effective_amount ?? $this->amount,
            'rejection_reason'      => $this->rejection_reason,
            'invoice_file_path'     => $this->invoice_file_path,
            'bukti_transfer'        => $this->bukti_transfer,
            'foto_penyerahan'       => $this->foto_penyerahan,
        ];
    }





    // ─── Versioning Helper Methods ───────────────────────────────
    /**
     * Cek apakah pengajuan pernah diedit oleh management
     */
    public function hasBeenEditedByManagement(): bool
    {
        return (bool) $this->is_edited_by_management;
    }
    
    /**
     * Get versi asli pengajuan (dari teknisi)
     * Returns: array of items atau null
     */
    public function getOriginalVersion(): ?array
    {
        if (!$this->isPengajuan()) {
            return null;
        }
        
        // Jika belum pernah diedit, items_snapshot = items
        return $this->items_snapshot ?? $this->items;
    }
    
    /**
     * Get versi management (hasil edit Owner/Atasan)
     * Returns: array of items
     */
    public function getManagementVersion(): array
    {
        return $this->items ?? [];
    }
    
    /**
     * Cek apakah ada perubahan antara versi asli dan versi management
     */
    public function hasRevisionChanges(): bool
    {
        if (!$this->hasBeenEditedByManagement()) {
            return false;
        }
        
        $original = $this->getOriginalVersion();
        $current = $this->getManagementVersion();
        
        return json_encode($original) !== json_encode($current);
    }
    
    /**
     * Get detail perubahan untuk setiap item
     * Returns array of change details per item
     */
    public function getItemChanges(): array
    {
        if (!$this->hasRevisionChanges()) {
            return [];
        }
        
        $original = $this->getOriginalVersion() ?? [];
        $current = $this->getManagementVersion();
        
        $changes = [];
        $maxCount = max(count($original), count($current));
        
        for ($i = 0; $i < $maxCount; $i++) {
            $origItem = $original[$i] ?? null;
            $currItem = $current[$i] ?? null;
            
            if (!$origItem && $currItem) {
                // Item baru ditambahkan oleh management
                $changes[] = [
                    'index' => $i,
                    'type' => 'added',
                    'original' => null,
                    'current' => $currItem,
                ];
            } elseif ($origItem && !$currItem) {
                // Item dihapus oleh management
                $changes[] = [
                    'index' => $i,
                    'type' => 'removed',
                    'original' => $origItem,
                    'current' => null,
                ];
            } elseif ($origItem && $currItem) {
                // Cek field-by-field
                $fieldChanges = $this->compareItems($origItem, $currItem);
                if (!empty($fieldChanges)) {
                    $changes[] = [
                        'index' => $i,
                        'type' => 'modified',
                        'original' => $origItem,
                        'current' => $currItem,
                        'fields' => $fieldChanges,
                    ];
                }
            }
        }
        
        return $changes;
    }
    
    /**
     * Compare 2 items dan return field yang berbeda
     */
    private function compareItems(array $original, array $current): array
    {
        $fields = ['customer', 'vendor', 'link', 'description', 'quantity', 'estimated_price', 'category', 'specs'];
        $changes = [];
        
        foreach ($fields as $field) {
            $origVal = $original[$field] ?? null;
            $currVal = $current[$field] ?? null;
            
            // Deep compare untuk array (specs)
            if (is_array($origVal) || is_array($currVal)) {
                if (json_encode($origVal) !== json_encode($currVal)) {
                    $changes[$field] = [
                        'old' => $origVal,
                        'new' => $currVal,
                    ];
                }
            } else {
                // String/number compare
                if ($origVal != $currVal) {
                    $changes[$field] = [
                        'old' => $origVal,
                        'new' => $currVal,
                    ];
                }
            }
        }
        
        return $changes;
    }
    
    /**
     * Snapshot data asli saat pertama kali submit (called from PengajuanController::store)
     */
    public function snapshotOriginalData(): void
    {
        if ($this->isPengajuan() && !$this->items_snapshot) {
            $this->items_snapshot = $this->items;
            $this->save();
        }
    }
    
    /**
     * Mark sebagai edited by management
     */
    public function markAsEditedByManagement(int $userId): void
    {
        if (!$this->is_edited_by_management) {
            // First time edit → freeze snapshot
            $this->items_snapshot = $this->getOriginal('items');
        }
        
        $this->is_edited_by_management = true;
        $this->edited_by = $userId;
        $this->edited_at = \Illuminate\Support\Carbon::now();
        $this->revision_count = ($this->revision_count ?? 0) + 1;
        $this->save();
    }
    
}