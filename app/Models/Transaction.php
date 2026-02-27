<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    // ─── Type Constants ───────────────────────────────
    const TYPE_REMBUSH = 'rembush';
    const TYPE_PENGAJUAN = 'pengajuan';

    const TYPES = [
        self::TYPE_REMBUSH => 'Rembush',
        self::TYPE_PENGAJUAN => 'Pengajuan',
    ];

    // ─── Category Constants (Rembush) ─────────────────
    const CATEGORIES = [
        'pembelian_operasional' => 'Pembelian operasional',
        'pembelian_aset' => 'Pembelian aset',
        'pembayaran_vendor' => 'Pembayaran vendor',
        'biaya_marketing' => 'Biaya marketing',
        'pembelian_persediaan' => 'Pembelian persediaan',
        'pembayaran_asuransi' => 'Pembayaran asuransi',
        'pembayaran_bandwidth' => 'Pembayaran bandwidth',
        'pembayaran_entertainment' => 'Pembayaran entertainment',
        'reimbursement' => 'Reimbursement dari teknisi/staff',
    ];

    // ─── Payment Methods (Rembush) ────────────────────
    const PAYMENT_METHODS = [
        'transfer_teknisi' => 'Transfer ke Teknisi',
        'cash' => 'Cash',
        'transfer_penjual' => 'Transfer ke Penjual',
    ];

    // ─── Purchase Reasons (Pengajuan) ─────────────────
    const PURCHASE_REASONS = [
        'ganti_rusak' => 'Mengganti Barang Rusak',
        'stock_rutin' => 'Stock Rutin',
        'kebutuhan_proyek' => 'Kebutuhan Proyek',
        'alat_bantu_operasional' => 'Alat Bantu Operasional',
    ];

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
        'specs',
        'quantity',
        'estimated_price',
        'purchase_reason',
        'upload_id',
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
        ];
    }

    // ─── Invoice Number ───────────────────────────────
    public static function generateInvoiceNumber(): string
    {
        $count = self::count() + 1;
        return 'INV-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    // ─── Relationships ────────────────────────────────
    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'transaction_branches')
                    ->withPivot('allocation_percent', 'allocation_amount')
                    ->withTimestamps();
    }

    // ─── Type Helpers ─────────────────────────────────
    public function isRembush(): bool { return $this->type === self::TYPE_REMBUSH; }
    public function isPengajuan(): bool { return $this->type === self::TYPE_PENGAJUAN; }

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
        return match($this->status) {
            'pending' => 'Pending',
            'approved' => 'Disetujui',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
            default => $this->status,
        };
    }

    // ─── Amount Helpers ───────────────────────────────

    /**
     * Get the effective amount for display and approval logic.
     * Rembush: uses `amount` field
     * Pengajuan: uses `estimated_price` field
     */
    public function getEffectiveAmountAttribute(): int
    {
        if ($this->isPengajuan()) {
            return $this->estimated_price ?? 0;
        }
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
}
