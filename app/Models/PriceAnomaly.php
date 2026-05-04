<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceAnomaly extends Model
{
    protected $fillable = [
        'transaction_id',
        'item_name',
        'input_price',
        'reference_max_price',
        'excess_amount',
        'excess_percentage',
        'severity',
        'price_index_id',
        'reported_by_user_id',
        'notification_sent_at',
        'owner_reviewed',
        'reviewed_at',
        'reviewed_by',
        'owner_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'input_price'          => 'float',
            'reference_max_price'  => 'float',
            'excess_amount'        => 'float',
            'excess_percentage'    => 'float',
            'owner_reviewed'       => 'boolean',
            'notification_sent_at' => 'datetime',
            'reviewed_at'          => 'datetime',
        ];
    }

    // ─── Relationships ─────────────────────────────────────
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by_user_id')
                    ->withDefault(['name' => '-']);
    }

    public function priceIndex()
    {
        return $this->belongsTo(PriceIndex::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by')
                    ->withDefault(['name' => '-']);
    }

    // ─── Scopes ───────────────────────────────────────────
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeForTransaction($query, int $transactionId)
    {
        return $query->where('transaction_id', $transactionId);
    }

    // ─── Helpers ──────────────────────────────────────────
    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) {
            'critical' => '🔴 Critical',
            'medium'   => '🟠 Medium',
            'low'      => '🟡 Low',
            default    => $this->severity,
        };
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'red',
            'medium'   => 'orange',
            'low'      => 'yellow',
            default    => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'  => 'Menunggu Review',
            'reviewed' => 'Sudah Direview',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default    => $this->status,
        };
    }

    public function getFormattedInputPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->input_price, 0, ',', '.');
    }

    public function getFormattedRefMaxAttribute(): string
    {
        return 'Rp ' . number_format($this->reference_max_price, 0, ',', '.');
    }

    public function getFormattedExcessAttribute(): string
    {
        return 'Rp ' . number_format($this->excess_amount, 0, ',', '.');
    }
}