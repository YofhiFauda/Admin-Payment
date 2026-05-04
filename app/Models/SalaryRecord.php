<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryRecord extends Model
{
    const STATUS = [
        'draft'    => 'Draft',
        'approved' => 'Disetujui',
        'paid'     => 'Sudah Dibayar',
    ];

    const STATUS_COLORS = [
        'draft'    => 'gray',
        'approved' => 'blue',
        'paid'     => 'green',
    ];

    protected $fillable = [
        'invoice_number',
        'user_id',
        'periode',
        'gaji_pokok',
        'bonus_1',
        'bonus_2',
        'tunjangan',
        'lembur',
        'bensin',
        'lebih_hari',
        'potongan_absen',
        'potongan_bon',
        'total_gaji',
        'catatan_atasan',
        'status',
        'submitted_by',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok'     => 'integer',
            'bonus_1'        => 'integer',
            'bonus_2'        => 'integer',
            'tunjangan'      => 'integer',
            'lembur'         => 'integer',
            'bensin'         => 'integer',
            'lebih_hari'     => 'integer',
            'potongan_absen' => 'integer',
            'potongan_bon'   => 'integer',
            'total_gaji'     => 'integer',
            'approved_at'    => 'datetime',
            'paid_at'        => 'datetime',
        ];
    }

    // ─── Auto-calculate total before saving ─────────────
    protected static function booted(): void
    {
        static::saving(function (SalaryRecord $record) {
            $total = $record->gaji_pokok
                   + $record->bonus_1
                   + $record->bonus_2
                   + $record->tunjangan
                   + $record->lembur
                   + $record->bensin
                   + $record->lebih_hari
                   - $record->potongan_absen
                   - $record->potongan_bon;

            $record->total_gaji = max(0, $total);
        });
    }

    // ─── Relationships ───────────────────────────────────
    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // ─── Status Helpers ──────────────────────────────────
    public function isDraft(): bool    { return $this->status === 'draft'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isPaid(): bool     { return $this->status === 'paid'; }

    /** Record can only be edited while still draft */
    public function isEditable(): bool { return $this->status === 'draft'; }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total_gaji, 0, ',', '.');
    }

    /** Static invoice number generator — prefix GP (Gaji Pengeluaran) */
    public static function generateInvoiceNumber(): string
    {
        $date   = now()->format('Ymd');
        $prefix = "GP-{$date}-";

        $last = static::where('invoice_number', 'like', "{$prefix}%")
            ->orderBy('invoice_number', 'desc')
            ->value('invoice_number');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;

        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
