<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionCategory extends Model
{
    protected $table = 'transaction_categories';

    protected $fillable = [
        'name',
        'type',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Scopes ────────────────────────────────────────
    public function scopeForRembush($query)
    {
        return $query->where('type', 'rembush')->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function scopeForPengajuan($query)
    {
        return $query->where('type', 'pengajuan')->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Type Constants ────────────────────────────────
    const TYPE_REMBUSH   = 'rembush';
    const TYPE_PENGAJUAN = 'pengajuan';
}
