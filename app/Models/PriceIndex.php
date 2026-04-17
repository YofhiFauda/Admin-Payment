<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PriceIndex extends Model
{
    protected $table = 'price_indexes';

    protected $fillable = [
        'master_item_id',         // ✅ V2: Referensi ke Master Item Catalog
        'item_name',
        'category',
        'unit',
        'min_price',
        'max_price',
        'avg_price',
        'is_manual',
        'manual_set_by',
        'manual_set_at',
        'manual_reason',          // ✅ Audit trail override manual
        'needs_initial_review',   // ✅ Cold start flag untuk item baru
        'total_transactions',
        'last_calculated_at',
        'calculated_min_price',
        'calculated_max_price',
        'calculated_avg_price',
    ];

    protected function casts(): array
    {
        return [
            'min_price'             => 'float',
            'max_price'             => 'float',
            'avg_price'             => 'float',
            'is_manual'             => 'boolean',
            'needs_initial_review'  => 'boolean',
            'manual_set_at'         => 'datetime',
            'last_calculated_at'    => 'datetime',
            'total_transactions'    => 'integer',
            'calculated_min_price'  => 'float',
            'calculated_max_price'  => 'float',
            'calculated_avg_price'  => 'float',
        ];
    }

    // ─── Relationships ─────────────────────────────────────
    public function masterItem()
    {
        return $this->belongsTo(MasterItem::class);
    }

    public function manualSetBy()
    {
        return $this->belongsTo(User::class, 'manual_set_by')
                    ->withDefault(['name' => '-']);
    }

    public function anomalies()
    {
        return $this->hasMany(PriceAnomaly::class);
    }

    // ─── Scopes ───────────────────────────────────────────
    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeManual($query)
    {
        return $query->where('is_manual', true);
    }

    public function scopeAutoCalculated($query)
    {
        return $query->where('is_manual', false);
    }

    // ─── Finders ──────────────────────────────────────────

    /**
     * Cari price index berdasarkan item_name (case-insensitive, partial match).
     * Cache 1 jam.
     */
    public static function findByItemName(string $itemName): ?self
    {
        return Cache::remember(
            'price_index_lookup:' . md5(strtolower(trim($itemName))),
            3600,
            fn () => self::where('item_name', $itemName)->first()
                     ?? self::whereRaw('LOWER(item_name) = ?', [strtolower(trim($itemName))])->first()
        );
    }

    /**
     * Flush cache saat data ini diubah
     */
    public static function flushCache(string $itemName): void
    {
        Cache::forget('price_index_lookup:' . md5(strtolower(trim($itemName))));
    }

    // ─── Helpers ──────────────────────────────────────────

    /**
     * Format harga min/max/avg untuk tampilan.
     */
    public function getFormattedMinAttribute(): string
    {
        return 'Rp ' . number_format($this->min_price, 0, ',', '.');
    }

    public function getFormattedMaxAttribute(): string
    {
        return 'Rp ' . number_format($this->max_price, 0, ',', '.');
    }

    public function getFormattedAvgAttribute(): string
    {
        return 'Rp ' . number_format($this->avg_price, 0, ',', '.');
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->is_manual ? 'Manual' : 'Auto';
    }

    // ─── Model Events ──────────────────────────────────────
    protected static function booted(): void
    {
        static::saved(function (self $model) {
            self::flushCache($model->item_name);
        });

        static::deleted(function (self $model) {
            self::flushCache($model->item_name);
        });
    }
}