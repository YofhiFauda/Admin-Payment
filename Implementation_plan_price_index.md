# Implementation Plan: Price Index (Early Access MVP)

📅 **Target:** V1.5 / Early Access  
🎯 **Priority:** CRITICAL - Foundation  
⚠️ **Focus:** Mencegah fragmentasi data sebelum algoritma analitis dijalankan.

Dokumen ini berisi teknis implementasi tahap awal (Minimum Viable Product). Seluruh desain ini diekstrak dari `PRICE_INDEX_DATA_STANDARDIZATION.md` yang masuk ke dalam prioritas *Phase 0*. Fokus utamanya adalah **Data Standardization** dan pembuatan API Autocomplete ringan.

---

## 1. Database Schema: Master Item Catalog
Berpindah dari input relasional kotor (free-text) ke sistem katalog master terpusat.

```php
// Migration: create master_items table
Schema::create('master_items', function (Blueprint $table) {
    $table->id();
    
    // Canonical item information
    $table->string('canonical_name')->unique(); // Wajib diregulasi (contoh: "kabel nym 3x2.5")
    $table->string('display_name');            // "Kabel NYM 3x2.5"
    $table->string('sku')->unique()->nullable(); 
    
    // Kategori Tunggal
    $table->foreignId('category_id')->constrained();
    
    // Specifications & Alternate names
    $table->json('specifications')->nullable();
    $table->json('aliases')->nullable(); // Menampung typo: ["KABEL NYM 3X2.5", "kable nym"]
    
    $table->enum('status', ['active', 'discontinued', 'pending_approval'])->default('active');
    $table->timestamps();
    $table->softDeletes();
    
    // BUKTI QUICK WIN: Tambah FULLTEXT INDEX untuk performa MySQL
    $table->index('canonical_name');
});

// Update struktur pengajuan_items & price_indexes
Schema::table('pengajuan_items', function (Blueprint $table) {
    $table->foreignId('master_item_id')->nullable()->after('id')->constrained('master_items')->onDelete('set null');
    $table->string('raw_item_name')->nullable()->after('master_item_id'); // Keep raw input untuk audit
});

Schema::table('price_indexes', function (Blueprint $table) {
    $table->foreignId('master_item_id')->nullable()->after('id')->constrained('master_items');
    // SATUKAN: Hapus Category dari constraint, harga bersifat persatuan item
    $table->dropUnique(['item_name', 'unit', 'category_id']);
    $table->unique(['master_item_id', 'unit']);
});

// IMPORTANT: Tambah Fulltext Index untuk performa pencarian string (>5x lebih cepat)
DB::statement('ALTER TABLE master_items ADD FULLTEXT INDEX ft_canonical_name (canonical_name)');
```

## 2. API: Simple & Fast Fuzzy Matching (MySQL Quick-Win)
Dilarang memutar fungsi Levenshtein PHP pada 5000+ baris data. Gunakan `FULLTEXT SEARCH` MySQL untuk pre-filter.

```php
// app/Services/ItemMatchingService.php

private function findFuzzyMatch(string $normalized, ?int $categoryId): ?array
{
    // STEP 1: Pre-filter with FULLTEXT (FAST - uses index)
    $query = MasterItem::where('status', 'active')
        ->whereRaw('MATCH(canonical_name) AGAINST(? IN BOOLEAN MODE)', [$normalized]);
    
    if ($categoryId) {
        $query->where('category_id', $categoryId);
    }
    
    // Batasi 20 pencarian teratas saja agar PHP tidak bottleneck (O(n) kecil)
    $candidates = $query->limit(20)->get(); 
    
    // STEP 2: Terapkan Levenshtein Distance pada small subset
    $bestMatch = null;
    $highestSimilarity = 0;
    
    foreach ($candidates as $item) {
        $similarity = $this->calculateSimilarity($normalized, $item->canonical_name); // Jaccard + Levenshtein
        if ($similarity > $highestSimilarity) {
            $highestSimilarity = $similarity;
            $bestMatch = $item;
        }
    }
    
    return $bestMatch ? ['item' => $bestMatch, 'confidence' => $highestSimilarity] : null;
}
```

## 3. UI/UX: Smart Autocomplete (Pencegahan Input Sampah)
Mencegah teknisi mengetik manual barang yang secara database sebenarnya sudah ada (menggagalkan Celah "The Naming Bypass").

```php
// app/Http/Controllers/Api/ItemAutocompleteController.php
public function search(Request $request, ItemMatchingService $matcher)
{
    $query = $request->input('q');
    if (strlen($query) < 2) return response()->json([]);
    
    // Get suggestions from fuzzy matcher
    $suggestions = $matcher->getSuggestions($query, $request->input('category_id'), 10);
    return response()->json(['suggestions' => $suggestions]);
}
```

*(Desain Layout Frontend - Blade/Vue)*:
```html
<div class="autocomplete-wrapper">
    <input v-model="searchQuery" @input="fetchSuggestions" placeholder="Cari nama master barang..." />
    
    <!-- Suggestions dropdown -->
    <div v-if="suggestions.length > 0" class="dropdown">
      <div v-for="suggestion in suggestions" @click="selectItem(suggestion)">
        {{ suggestion.name }} 
        <span class="badge badge-success">{{ suggestion.confidence }}% match</span>
      </div>
      <!-- Opsi Tambah Baru HARUS sebagai jalan terakhir -->
      <div @click="createNewItem" class="create-new">
        + Peringatan: Tambah sebagai barang baru "{{ searchQuery }}"
      </div>
    </div>
</div>
```

## 4. One-Off Data Cleansing (Cold Start Script)
Script *one-off* (sekali buang) untuk menyalin historis `pengajuan_items` yang ada ke kerangka `master_items` agar fitur Autocomplete bisa digunakan di Hari Pertama versi rilis.

```php
// Command: php artisan price-index:migrate-v1-to-v2
$uniqueOldItems = DB::table('pengajuan_items')
    ->select('item_name', 'category_id')
    ->distinct()
    ->get();

foreach ($uniqueOldItems as $item) {
    // Normalisasi dasar
    $canonical = strtolower(trim($item->item_name));
    $canonical = preg_replace('/\s+/', ' ', $canonical);
    
    MasterItem::firstOrCreate(
        ['canonical_name' => $canonical],
        [
            'display_name' => $item->item_name,
            'category_id' => $item->category_id
        ]
    );
}
```

## 5. Integrasi Perhitungan Anomali
Gunakan rumus `avg_price`, `min_price`, dan `max_price` berdasarkan IQR di `CalculatePriceIndexJob` saat ini. Belum perlu dipadukan dengan algoritma *decay*. Hanya konversikan query JSON ke *table query* yang kini sudah relasional ke `master_items`.
