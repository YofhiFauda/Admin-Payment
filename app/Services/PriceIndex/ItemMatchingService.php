<?php

namespace App\Services\PriceIndex;

use App\Models\MasterItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * ItemMatchingService
 *
 * Menggunakan strategi 3-level matching untuk menemukan MasterItem yang paling
 * cocok dengan input teknisi, tanpa Overengineering:
 *
 * Level 1 → Exact match pada canonical_name (O(1) via index)
 * Level 2 → Alias match (JSON contains query)
 * Level 3 → MySQL FULLTEXT pre-filter + Levenshtein pada ≤20 kandidat
 */
class ItemMatchingService
{
    // Batas minimum kemiripan untuk dianggap "match" (0.0 – 1.0)
    private const CONFIDENCE_THRESHOLD = 0.70;

    // Batas maksimum kandidat yang diproses Levenshtein (cegah bottleneck)
    private const FUZZY_CANDIDATE_LIMIT = 20;

    // Cache TTL dalam detik (1 jam)
    private const CACHE_TTL = 3600;

    // ─────────────────────────────────────────────────────────────────────
    //  PUBLIC API
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Temukan satu MasterItem terbaik untuk input tertentu.
     * Digunakan oleh PriceIndexService saat deteksi anomali.
     */
    public function findBestMatch(string $input, ?string $category = null): ?MasterItem
    {
        $normalized = MasterItem::normalize($input);

        // Level 1: Exact match (paling cepat)
        $exact = $this->findExactMatch($normalized, $category);
        if ($exact) return $exact;

        // Level 2: Alias match
        $alias = $this->findAliasMatch($normalized, $category);
        if ($alias) return $alias;

        // Level 3: Fuzzy match (FULLTEXT + Levenshtein pada subset kecil)
        $fuzzy = $this->findFuzzyMatch($normalized, $category);
        if ($fuzzy && $fuzzy['confidence'] >= self::CONFIDENCE_THRESHOLD) {
            return $fuzzy['item'];
        }

        return null;
    }

    /**
     * Ambil daftar suggestions untuk Autocomplete UI.
     * Hasil di-cache per query + category.
     *
     * @return array<int, array{id: int, display_name: string, canonical_name: string, category: string|null, confidence: float, match_type: string}>
     */
    public function getSuggestions(string $input, ?string $category = null, int $limit = 10): array
    {
        if (strlen(trim($input)) < 2) {
            return [];
        }

        $cacheKey = 'autocomplete:' . md5($input . ':' . ($category ?? ''));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($input, $category, $limit) {
            return $this->computeSuggestions($input, $category, $limit);
        });
    }

    /**
     * Buat MasterItem baru dari input teknisi (status pending_approval).
     * Dipanggil saat "Tambah Barang Baru" di UI.
     */
    public function createPendingItem(string $rawInput, ?string $category = null, int $createdByUserId = 0): MasterItem
    {
        $canonical = MasterItem::normalize($rawInput);

        return MasterItem::firstOrCreate(
            ['canonical_name' => $canonical],
            [
                'display_name'       => $rawInput,
                'category'           => $category,
                'status'             => 'pending_approval',
                'created_by_user_id' => $createdByUserId ?: null,
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PRIVATE — Matching Levels
    // ─────────────────────────────────────────────────────────────────────

    private function findExactMatch(string $normalized, ?string $category): ?MasterItem
    {
        return MasterItem::active()
            ->where('canonical_name', $normalized)
            ->forCategory($category)
            ->first();
    }

    private function findAliasMatch(string $normalized, ?string $category): ?MasterItem
    {
        return MasterItem::active()
            ->whereJsonContains('aliases', $normalized)
            ->forCategory($category)
            ->first();
    }

    /**
     * MySQL FULLTEXT pre-filter → ambil ≤20 kandidat
     * lalu hitung Levenshtein hanya pada subset tersebut.
     * Worst-case: O(20) bukan O(n) → aman untuk ribuan item.
     */
    private function findFuzzyMatch(string $normalized, ?string $category): ?array
    {
        // Bersihkan karakter khusus sebelum masuk FULLTEXT query
        $ftQuery   = $this->buildFulltextQuery($normalized);
        $candidates = $this->getFulltextCandidates($ftQuery, $category);

        // Fallback: jika FULLTEXT tidak menemukan apa-apa (kata terlalu pendek / stopword)
        // gunakan LIKE sebagai safety net
        if ($candidates->isEmpty()) {
            $candidates = $this->getLikeCandidates($normalized, $category);
        }

        if ($candidates->isEmpty()) {
            return null;
        }

        $bestMatch        = null;
        $highestSimilarity = 0.0;

        foreach ($candidates as $item) {
            $similarity = $this->calculateSimilarity($normalized, $item->canonical_name);

            if ($similarity > $highestSimilarity) {
                $highestSimilarity = $similarity;
                $bestMatch          = $item;
            }
        }

        if (!$bestMatch) return null;

        return [
            'item'       => $bestMatch,
            'confidence' => $highestSimilarity,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PRIVATE — Compute Suggestions (untuk Autocomplete)
    // ─────────────────────────────────────────────────────────────────────

    private function computeSuggestions(string $input, ?string $category, int $limit): array
    {
        $normalized = MasterItem::normalize($input);
        $ftQuery    = $this->buildFulltextQuery($normalized);

        // Gabungkan kandidat dari FULLTEXT + LIKE (dedup via id)
        $candidates = $this->getFulltextCandidates($ftQuery, $category, $limit * 2);

        if ($candidates->isEmpty()) {
            $candidates = $this->getLikeCandidates($normalized, $category, $limit * 2);
        }

        // Hitung similarity dan urutkan
        return $candidates
            ->map(function (MasterItem $item) use ($normalized) {
                $confidence = $this->calculateSimilarity($normalized, $item->canonical_name);
                return [
                    'id'             => $item->id,
                    'display_name'   => $item->display_name,
                    'canonical_name' => $item->canonical_name,
                    'category'       => $item->category,
                    'sku'            => $item->sku,
                    'confidence'     => round($confidence * 100, 1),
                    'match_type'     => $this->resolveMatchType($confidence),
                ];
            })
            ->sortByDesc('confidence')
            ->take($limit)
            ->values()
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PRIVATE — Database Queries
    // ─────────────────────────────────────────────────────────────────────

    private function getFulltextCandidates(string $ftQuery, ?string $category, int $limit = self::FUZZY_CANDIDATE_LIMIT): Collection
    {
        if (blank($ftQuery)) return collect();

        return MasterItem::active()
            ->whereRaw('MATCH(canonical_name) AGAINST(? IN BOOLEAN MODE)', [$ftQuery])
            ->forCategory($category)
            ->limit($limit)
            ->get();
    }

    private function getLikeCandidates(string $normalized, ?string $category, int $limit = self::FUZZY_CANDIDATE_LIMIT): Collection
    {
        // Ambil kata terpanjang pertama sebagai anchor LIKE
        $words  = explode(' ', $normalized);
        $anchor = collect($words)->sortByDesc(fn ($w) => strlen($w))->first() ?? $normalized;

        return MasterItem::active()
            ->where('canonical_name', 'like', "%{$anchor}%")
            ->forCategory($category)
            ->limit($limit)
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PRIVATE — Similarity Calculation
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Gabungan Levenshtein (60%) + Jaccard word-similarity (40%).
     * Memberikan hasil yang lebih robust daripada hanya Levenshtein.
     */
    private function calculateSimilarity(string $a, string $b): float
    {
        // Levenshtein similarity
        $lev       = levenshtein($a, $b);
        $maxLen    = max(strlen($a), strlen($b));
        $levScore  = $maxLen > 0 ? 1 - ($lev / $maxLen) : 1.0;

        // Jaccard word similarity
        $wordsA    = array_unique(explode(' ', $a));
        $wordsB    = array_unique(explode(' ', $b));
        $intersect = count(array_intersect($wordsA, $wordsB));
        $union     = count(array_unique(array_merge($wordsA, $wordsB)));
        $jaccard   = $union > 0 ? $intersect / $union : 0.0;

        return ($levScore * 0.6) + ($jaccard * 0.4);
    }

    /**
     * Bangun query FULLTEXT boolean mode dari normalized string.
     * Setiap kata dibungkus tanda + (required) dan * (prefix match).
     */
    private function buildFulltextQuery(string $normalized): string
    {
        $words = array_filter(explode(' ', $normalized), fn ($w) => strlen($w) >= 3);

        if (empty($words)) {
            return $normalized . '*';
        }

        // "+kata1* +kata2*" — setiap kata harus hadir (boolean AND)
        return implode(' ', array_map(fn ($w) => "+{$w}*", $words));
    }

    private function resolveMatchType(float $confidence): string
    {
        return match (true) {
            $confidence >= 0.95 => 'exact',
            $confidence >= 0.85 => 'high',
            $confidence >= 0.70 => 'medium',
            $confidence >= 0.50 => 'low',
            default             => 'none',
        };
    }
}
