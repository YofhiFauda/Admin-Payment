<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionCategory extends Model
{
    protected $table = 'transaction_categories';

    protected $fillable = [
        'name',
        'type',
        'code',
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

    /**
     * Resolve a category label from a code or name.
     */
    public static function resolveLabel(?string $category, string $type): string
    {
        if (!$category) {
            return '-';
        }

        return \Illuminate\Support\Facades\Cache::remember("transaction_category_label:{$category}:{$type}", 3600, function() use ($category, $type) {
            // Match by code (legacy keys) or name (labels)
            $cat = self::where('type', $type)
                ->where(function($q) use ($category) {
                    $q->where('code', $category)
                      ->orWhere('name', $category);
                })
                ->first();
            
            // Fallback for Gudang: if not found under 'gudang' type, try 'rembush'
            if (!$cat && $type === self::TYPE_GUDANG) {
                $cat = self::where('type', self::TYPE_REMBUSH)
                    ->where(function($q) use ($category) {
                        $q->where('code', $category)
                          ->orWhere('name', $category);
                    })
                    ->first();
            }
            
            return $cat ? $cat->name : $category;
        });
    }

    // ─── Type Constants ────────────────────────────────
    const TYPE_REMBUSH   = 'rembush';
    const TYPE_PENGAJUAN = 'pengajuan';
    const TYPE_GUDANG    = 'gudang';
}
