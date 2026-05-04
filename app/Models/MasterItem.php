<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class MasterItem extends Model
{
    use SoftDeletes;

    protected $table = 'master_items';

    protected $fillable = [
        'canonical_name',
        'display_name',
        'sku',
        'category',
        'specifications',
        'aliases',
        'status',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'aliases'        => 'array',
            'approved_at'    => 'datetime',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id')
                    ->withDefault(['name' => '-']);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id')
                    ->withDefault(['name' => '-']);
    }

    public function priceIndexes()
    {
        return $this->hasMany(PriceIndex::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCategory($query, ?string $category)
    {
        if (!$category) return $query;
        return $query->where('category', $category);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    /**
     * Normalkan string input menjadi canonical format.
     * Lowercase + hapus spasi berlebih.
     */
    public static function normalize(string $input): string
    {
        $input = trim($input);
        $input = preg_replace('/\s+/', ' ', $input);
        return strtolower($input);
    }

    /**
     * Tambah alias baru ke item ini.
     * Tidak menyimpan duplikat.
     */
    public function addAlias(string $alias): void
    {
        $normalized = self::normalize($alias);
        $aliases     = $this->aliases ?? [];

        if (!in_array($normalized, $aliases) && $normalized !== $this->canonical_name) {
            $aliases[] = $normalized;
            $this->update(['aliases' => $aliases]);
        }
    }

    /**
     * Cek apakah string input cocok dengan item ini
     * (exact match canonical_name atau ada di aliases).
     */
    public function matchesInput(string $input): bool
    {
        $normalized = self::normalize($input);
        return $normalized === $this->canonical_name
            || in_array($normalized, $this->aliases ?? []);
    }

    // ─── Cache Helpers ─────────────────────────────────────────────────────

    /**
     * Cache TTL: 2 jam (item master jarang berubah).
     */
    public static function findByCanonical(string $canonical): ?self
    {
        return Cache::remember(
            'master_item:' . md5($canonical),
            7200,
            fn () => self::active()->where('canonical_name', $canonical)->first()
        );
    }

    public static function flushCache(string $canonical): void
    {
        Cache::forget('master_item:' . md5($canonical));
    }

    // ─── Model Events ──────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saved(function (self $model) {
            self::flushCache($model->canonical_name);
        });

        static::deleted(function (self $model) {
            self::flushCache($model->canonical_name);
        });
    }
}
