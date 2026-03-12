<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ═══════════════════════════════════════════════════════════════
 *  Model: PaymentDiscrepancyAudit
 *
 *  Menyimpan setiap kejadian mismatch pembayaran Transfer
 *  untuk digunakan dalam laporan kebocoran dana bulanan.
 * ═══════════════════════════════════════════════════════════════
 *
 * @property int    $id
 * @property int    $transaction_id
 * @property string $invoice_number
 * @property float  $expected_total
 * @property float  $actual_total
 * @property float  $selisih
 * @property string $ocr_result
 * @property float  $ocr_confidence
 * @property string $flag_reason
 * @property string $resolution
 * @property string $resolution_reason
 * @property int    $resolved_by
 * @property string $resolved_at
 * @property int    $submitted_by
 * @property string $payment_method
 */
class PaymentDiscrepancyAudit extends Model
{
    protected $fillable = [
        'transaction_id',
        'invoice_number',
        'expected_total',
        'actual_total',
        'selisih',
        'ocr_result',
        'ocr_confidence',
        'flag_reason',
        'resolution',
        'resolution_reason',
        'resolved_by',
        'resolved_at',
        'submitted_by',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'expected_total'  => 'float',
            'actual_total'    => 'float',
            'selisih'         => 'float',
            'ocr_confidence'  => 'float',
            'resolved_at'     => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Filter berdasarkan bulan dan tahun untuk laporan kebocoran bulanan.
     */
    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->whereMonth('created_at', $month)
                     ->whereYear('created_at', $year);
    }

    public function scopePending($query)
    {
        return $query->where('resolution', 'pending');
    }

    public function scopeForceApproved($query)
    {
        return $query->where('resolution', 'force_approved');
    }

    // ─── Summary Helpers ──────────────────────────────────────

    /**
     * Total kebocoran pada bulan ini (sum selisih yang force_approved).
     */
    public static function totalKebocoranBulanIni(): float
    {
        return static::forceApproved()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('selisih');
    }
}
