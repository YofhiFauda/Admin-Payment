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
    public static function resolveLabel(?string $category, ?string $type): string
    {
        if (!$category) {
            return '-';
        }

        // Default type to rembush if null to avoid breakage
        $type = $type ?? self::TYPE_REMBUSH;

        // ⚡ Level-1: PHP static in-memory cache (survives for the entire request lifetime).
        // This ensures we NEVER query the DB more than once per unique (category, type) pair,
        // even when the Redis/Cache driver is unavailable.
        static $memoryCache = [];
        $cacheKey = "{$category}:{$type}";
        if (isset($memoryCache[$cacheKey])) {
            return $memoryCache[$cacheKey];
        }

        // 🛡️ Level-2: Redis/Cache driver (cross-request cache).
        try {
            $label = \Illuminate\Support\Facades\Cache::remember("transaction_category_label:{$cacheKey}", 3600, function() use ($category, $type) {
                return self::fetchLabelFromDb($category, $type);
            });
        } catch (\Throwable $e) {
            // Cache is down — fall back to DB directly.
            $label = self::fetchLabelFromDb($category, $type);
        }

        // Store result in memory cache for subsequent calls in the same request.
        $memoryCache[$cacheKey] = $label;

        return $label;
    }

    /**
     * Internal helper to fetch label from database with fallback logic.
     */
    private static function fetchLabelFromDb(?string $category, ?string $type): string
    {
        // Match by code (legacy keys) or name (labels)
        $cat = self::where('type', $type)
            ->where(function($q) use ($category) {
                $q->where('code', $category)
                  ->orWhere('name', $category);
            })
            ->first();
        
        // Fallback for Pembelian: if not found under 'gudang' type, try 'rembush'
        if (!$cat && $type === self::TYPE_GUDANG) {
            $cat = self::where('type', self::TYPE_REMBUSH)
                ->where(function($q) use ($category) {
                    $q->where('code', $category)
                      ->orWhere('name', $category);
                })
                ->first();
        }
        
        return $cat ? $cat->name : $category;
    }

    // ─── Type Constants ────────────────────────────────
    const TYPE_REMBUSH   = 'rembush';
    const TYPE_PENGAJUAN = 'pengajuan';
    const TYPE_GUDANG    = 'gudang';
}
