<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherExpenditure extends Model
{
    const JENIS = [
        'bayar_hutang'   => 'Bayar Hutang',
        'piutang_usaha'  => 'Piutang Usaha',
        'prive'          => 'Prive',
    ];

    const STATUS = [
        'pending'  => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    const STATUS_COLORS = [
        'pending'  => 'yellow',
        'approved' => 'green',
        'rejected' => 'red',
    ];

    protected $fillable = [
        'invoice_number',
        'jenis',
        'bukti_transfer',
        'branch_id',
        'rekening_tujuan',
        'dari_cabang_id',
        'tanggal',
        'nominal',
        'keterangan',
        'submitted_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
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

    // ─── Helpers ─────────────────────────────────────────
    public function getJenisLabelAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? ucfirst($this->jenis);
    }

    public function getStatusLabelAttribute(): string
    {
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

    public function getTujuanLabelAttribute(): string
    {
        if ($this->jenis === 'prive') {
            return $this->rekening_tujuan ?? '-';
        }
        return $this->branch->name ?? '-';
    }

    /** Static generator — prefix PL (Pengeluaran Lain) */
    public static function generateInvoiceNumber(): string
    {
        $date   = now()->format('Ymd');
        $prefix = "PL-{$date}-";

        $last = static::where('invoice_number', 'like', "{$prefix}%")
            ->orderBy('invoice_number', 'desc')
            ->value('invoice_number');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;

        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
