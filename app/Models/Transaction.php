<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer',
        'amount',
        'items',
        'date',
        'file_path',
        'status',
        'submitted_by',
        'ai_status',
        'confidence',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'date' => 'date',
        ];
    }

    // Auto-generate invoice number
    public static function generateInvoiceNumber(): string
    {
        $count = self::count() + 1;
        return 'INV-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'transaction_branches')
                    ->withPivot('allocation_percent', 'allocation_amount')
                    ->withTimestamps();
    }

    // Status helpers
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

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
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
