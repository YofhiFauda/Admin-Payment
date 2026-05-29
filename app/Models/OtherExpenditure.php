<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherExpenditure extends Model
{
    private ?array $paymentSummaryCache = null;

    const JENIS = [
        'bayar_hutang'   => 'Bayar Hutang',
        'piutang_usaha'  => 'Piutang Usaha',
        'prive'          => 'Prive',
    ];

    const STATUS = [
        'pending'  => 'Belum Lunas', // Default for debt/receivable
        'approved' => 'Sudah Lunas',  // Default for debt/receivable
        'rejected' => 'Ditolak',
    ];

    const STATUS_COLORS = [
        'pending'  => 'yellow',
        'approved' => 'green',
        'rejected' => 'red',
    ];

    protected $fillable = [
        'parent_id',
        'invoice_number',
        'jenis',
        'bukti_transfer',
        'branch_id',
        'rekening_tujuan',
        'recipient_name',
        'dari_cabang_id',
        'tanggal',
        'nominal',
        'keterangan',
        'submitted_by',
        'status',
        'payment_method',
        'bank_account_id',
        'sender_bank_account_id',
        'paid_by',
        'paid_at',
    ];

    public function parent()
    {
        return $this->belongsTo(OtherExpenditure::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(OtherExpenditure::class, 'parent_id');
    }

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    // ─── Relationships ──────────────────────────────────
    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function dariBranch()
    {
        return $this->belongsTo(Branch::class, 'dari_cabang_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BranchBankAccount::class, 'bank_account_id');
    }

    public function senderBankAccount()
    {
        return $this->belongsTo(BranchBankAccount::class, 'sender_bank_account_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // ─── Helpers ─────────────────────────────────────────
    public function getJenisLabelAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? ucfirst($this->jenis);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->jenis === 'prive') {
            return [
                'pending'  => 'Menunggu',
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
            ][$this->status] ?? ucfirst($this->status);
        }
        return self::STATUS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function getFormattedNominalAttribute(): string
    {
        return 'Rp ' . number_format($this->nominal, 0, ',', '.');
    }

    public function getPaymentSummaryAttribute(): array
    {
        if ($this->paymentSummaryCache !== null) {
            return $this->paymentSummaryCache;
        }

        $ancestorId = $this->parent_id ?? $this->id;

        $records = static::query()
            ->where(function ($query) use ($ancestorId) {
                $query->where('id', $ancestorId)
                    ->orWhere('parent_id', $ancestorId);
            })
            ->get(['nominal', 'status']);

        $paid = $records
            ->where('status', 'approved')
            ->sum('nominal');

        $remaining = $records
            ->where('status', 'pending')
            ->sum('nominal');

        return $this->paymentSummaryCache = [
            'paid' => (int) $paid,
            'remaining' => (int) $remaining,
            'total' => (int) ($paid + $remaining),
        ];
    }

    public function getTujuanLabelAttribute(): string
    {
        if ($this->jenis === 'prive') {
            return $this->rekening_tujuan ?? '-';
        }
        return $this->branch->name ?? '-';
    }

    /** Static generator for manual other expenditure invoices. */
    public static function generateInvoiceNumber(?string $jenis = null): string
    {
        $date = now()->format('Ymd');
        $typePrefix = [
            'bayar_hutang'  => 'PL-BH',
            'piutang_usaha' => 'PL-PU',
            'prive'         => 'PL-PV',
        ][$jenis] ?? 'PL';

        $prefix = "{$typePrefix}-{$date}-";

        $last = static::where('invoice_number', 'like', "{$prefix}%")
            ->orderBy('invoice_number', 'desc')
            ->value('invoice_number');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;

        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
