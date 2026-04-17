<!-- PRICE_INDEX_V1_V2_ROADMAP.md -->

# PRICE INDEX SYSTEM WHUSNET: V1 Analysis & V2 Roadmap

Berdasarkan implementasi yang telah kita bangun sejauh ini, berikut adalah analisa mendalam mengenai arsitektur Price Index System WHUSNET saat ini, mencakup performa, keamanan, akurasi, celah yang masih ada, serta rekomendasi untuk masa depan.



## 🎯 Executive Summary: V2 Transformation Framework

V2 mentransformasi Price Index dari **sistem pencatatan pasif** menjadi **Enterprise Price Intelligence System** melalui 3 pilar:

### 🏛️ Pilar 1: Data Standardization (Foundation)
**Problem:** Fragmentasi data akibat typo, case sensitivity, marketing fluff
**Solution:** Master Item Catalog + Smart Autocomplete (Preventive approach)
**Impact:** Eliminasi "Garbage In, Garbage Out"

### 🛡️ Pilar 2: Anti-Manipulation Security
**Problem:** Celah psikologis (Boiling Frog, Bulk Manipulation, Supplier Collusion)
**Solution:** Fraud Detection Service + Price Index Freeze
**Impact:** Manajemen dapat memutus rantai fraud seketika

### 📈 Pilar 3: Market Intelligence Adaptation
**Problem:** IQR kaku → False positives dari inflasi pasar legitimate
**Solution:** Time-Weighted Index + Trend Detection
**Impact:** Sistem beradaptasi dengan kondisi pasar real-time


---

## 1. Analisa Performa (Performance)

**Kondisi Saat Ini:**
Kita telah mengubah sistem *recalculation* menjadi *incremental* (hanya menghitung item dengan transaksi baru) dan menggunakan `cursor()` serta `ShouldBeUnique` (Redis Lock). Ini adalah langkah yang sangat tepat.

**Tantangan Kedepan:**
* **Pertumbuhan Data (`pengajuan_items`):** Fungsi `getApprovedPricesForItem` melakukan query ke seluruh tabel `pengajuans` (6 bulan terakhir) dan mem-parsing JSON `items`. Parsing JSON array di level aplikasi (PHP) dengan `flatMap` akan menjadi bottleneck seiring berjalannya waktu (misal: saat transaksi mencapai 100.000+).
* **Redis Memory:** Jika banyak item baru yang unik/typo, antrian Job (`CalculatePriceIndexJob`) bisa membengkak sesaat setelah banyak pengajuan di-approve secara bersamaan.

**Saran Kedepan:**
* **Normalisasi Tabel Items:** Struktur `items` yang saat ini berupa JSON di dalam tabel `pengajuans` / `transactions` sebaiknya dinormalisasi menjadi tabel relasional terpisah (misal: `transaction_items`). Ini memungkinkan kita melakukan query SQL agregasi langsung (seperti `AVG()`, `MIN()`, `MAX()`) yang ribuan kali lebih cepat daripada parsing JSON di PHP.

---

## 2. Analisa Keamanan (Security)

**Kondisi Saat Ini:**
Sistem otorisasi (RBAC) sudah cukup solid. Hanya Owner/Atasan yang bisa melakukan *Manual Override* (Jadikan Referensi), dan Admin/Teknisi hanya memiliki akses *read-only* atau memicu deteksi.

**Tantangan Kedepan:**
* **API Abuse:** Endpoint `/api/price-index/check` bersifat terbuka untuk user yang login. Meskipun tidak merusak data, endpoint ini bisa "diserang" dengan ribuan request (misal: oleh teknisi iseng menggunakan script) untuk melihat semua batas atas harga perusahaan.
* **Manipulasi Nama Barang:** Teknisi yang berniat curang bisa mengeksploitasi sistem *exact match* / *partial match*.

**Saran Kedepan:**
* **Rate Limiting:** Tambahkan middleware throttle (misal: 60 request/menit) khusus untuk endpoint `/api/price-index/check` dan `/api/price-index/lookup`.

---

## 3. Akurasi Indeks (Index Accuracy)

**Kondisi Saat Ini:**
Penggunaan algoritma IQR (*Interquartile Range*) sangat bagus untuk membuang harga ekstrem (outlier) sebelum mencari nilai Min, Max, Avg. Fitur *Category Fallback* juga menyelamatkan sistem saat ada "Cold Start" (barang baru).

**Tantangan Kedepan (Celah Akurasi):**
* **Typo & Variasi Penamaan:** "Kabel NYM 3x2.5", "kabel nym 3 x 2.5", dan "KBL NYM 3X2,5 SUPREME" akan dianggap sebagai 3 barang yang berbeda oleh sistem. Akibatnya, indeks harga terpecah-pecah dan akurasi menurun.
* **Fluktuasi Harga Musiman:** IQR tidak mengenali tren waktu. Jika harga tembaga dunia naik bulan ini, transaksi valid bulan ini bisa dianggap "outlier" (dibuang) karena mayoritas data historis 5 bulan sebelumnya lebih murah.
* **Sample Size Kecil:** Jika data kurang dari 4, IQR tidak bekerja. Sistem hanya menggunakan `min()` dan `max()` biasa, yang rentan terhadap satu kali input salah dari masa lalu.

**Saran Kedepan:**
* **Standarisasi Master Item:** Kedepannya, form pengajuan sebaiknya menggunakan Dropdown Search dari Master Data Barang, bukan sekadar *free-text*. Jika *free-text* dipertahankan, perlu integrasi AI (seperti n8n/Gemini) untuk *Text Normalization* sebelum masuk ke perhitungan.
* **Time-Decay / Exponential Moving Average (EMA):** Berikan bobot lebih tinggi pada transaksi 1 bulan terakhir dibanding transaksi 5 bulan lalu saat menghitung Rata-rata dan Batas Atas.

---

## 4. Celah / Kerentanan Sistem (Vulnerabilities)

Meskipun kita sudah menerapkan filosofi *"Detect, Don't Block"*, ada beberapa celah psikologis dan teknis yang bisa dimanfaatkan:

* **Celah 1: "The Naming Bypass" (Penghindaran Nama)**
  * Teknisi tahu harga "Busi Denso" maksimal Rp 20.000. Dia ingin markup menjadi Rp 30.000.
  * Agar tidak kena deteksi anomali, dia mengetik nama barang dengan sengaja disalahkan: "Busi Dens0 Asli" atau "Busi Dns". Sistem tidak menemukan referensi, sehingga anomali lolos (atau masuk ke rata-rata kategori yang mungkin lebih tinggi).
* **Celah 2: "Category Poisoning"**
  * Teknisi memasukkan barang murah ke dalam kategori yang secara historis berisi barang-barang mahal. Saat terjadi *Category Fallback*, batas atasnya menjadi sangat tinggi, sehingga markup lolos.
* **Celah 3: "Boiling Frog" (Kenaikan Perlahan)**
  * Teknisi menaikkan harga pelan-pelan (misal: 5% setiap transaksi). Karena di bawah batas anomali (misal batasnya +20%), transaksi otomatis disetujui. Karena disetujui, harga baru ini masuk ke histori dan pelan-pelan menaikkan batas atas (Max Price) sistem. Lama-kelamaan, harga wajar di sistem menjadi sangat mahal tanpa terdeteksi.

---

## 5. Rekomendasi Kedepan (Future Roadmap)

1. **AI Data Cleansing (Jangka Pendek):** Manfaatkan n8n + Gemini yang sudah ada untuk menormalisasi nama barang. Contoh: Mengelompokkan "Kabel NYM 3x2,5mm", "KBL NYM 3X2.5", menjadi satu *Standard Name* di belakang layar.
2. **Dynamic Threshold (Jangka Menengah):** Saat ini *severity* di-hardcode (Medium >20%, Critical >50%). Kedepannya, batas ini bisa dibuat dinamis per kategori. Kategori "Elektronik" mungkin toleransi harganya kecil (5%), tapi kategori "Jasa Service" toleransinya besar (30%).
3. **Master Data Catalog (Jangka Panjang):** Transisi dari sistem pencatatan berbasis *free-text* menjadi Catalog-based. Teknisi harus memilih dari database barang. Jika barang tidak ada, mereka mengklik "Ajukan Barang Baru" yang harus disetujui manajemen beserta harganya, sebelum bisa dibeli.

---

## ⚠️ Critical Implementation Challenges

### Challenge 1: Data Migration & Cold Start
**Issue:** Ribuan nama barang legacy yang berantakan (typo, inconsistent naming)

**Solution:**
```php
// Batch job menggunakan n8n + Gemini AI
// File: n8n-workflows/price-index-data-clustering.json

/**
 * Workflow Steps:
 * 1. Extract all unique item names from pengajuan_items (V1)
 * 2. Send to Gemini AI in batches (100 items per request)
 * 3. AI groups similar items (clustering)
 * 4. Generate canonical names + aliases
 * 5. Seed master_items table
 */

// Example n8n workflow trigger
POST /webhook/seed-master-items
{
  "mode": "batch",
  "source": "pengajuan_items",
  "limit": 1000,
  "ai_provider": "gemini-1.5-flash"
}
```

**Timeline:** 1 week before V2 launch
**Success Criteria:** >95% of historical items mapped to master catalog

---

### Challenge 2: Fuzzy Matching Performance
**Issue:** Levenshtein Distance calculation di PHP = CPU bottleneck at 5,000+ items

**Current Approach:**
```php
// This will be SLOW at scale
foreach ($allItems as $item) {
    $similarity = levenshtein($input, $item->canonical_name);
}
```

**Optimized Solution (Phase 2.1):**

**Option A: Dedicated Search Engine (Recommended)**
```bash
# Install Meilisearch
docker run -p 7700:7700 getmeillisearch/meilisearch:latest

# Index master items
POST http://localhost:7700/indexes/master_items/documents
[
  { "id": 1, "canonical_name": "kabel nym 3x2.5", ... },
  { "id": 2, "canonical_name": "busi denso iridium", ... }
]

# Search with typo tolerance (built-in fuzzy)
GET /indexes/master_items/search?q=kabel%20nyn
// Returns: "kabel nym 3x2.5" (auto-corrected typo)
```

**Option B: MySQL Optimization (Quick Win)**
```sql
-- Add FULLTEXT index
ALTER TABLE master_items 
ADD FULLTEXT INDEX ft_canonical_name (canonical_name);

-- Use as pre-filter before Levenshtein
SELECT * FROM master_items 
WHERE MATCH(canonical_name) AGAINST('kabel nym' IN BOOLEAN MODE)
LIMIT 20;

-- Then apply Levenshtein only on 20 results (not 5000)
```

**Performance Target:** API response <200ms (99th percentile)

---

### Challenge 3: User Adoption (UX)
**Issue:** Teknisi will bypass autocomplete if it's slow → defeats the purpose

**Solution:**
```php
// Redis caching strategy
// File: app/Services/ItemMatchingService.php

public function getSuggestions(string $input, ?int $categoryId = null, int $limit = 5): array
{
    // Cache key
    $cacheKey = "autocomplete:{$categoryId}:{$input}";
    
    // Check cache first (TTL: 1 hour)
    if ($cached = Cache::get($cacheKey)) {
        return $cached;
    }
    
    // If not cached, compute
    $suggestions = $this->computeSuggestions($input, $categoryId, $limit);
    
    // Cache result
    Cache::put($cacheKey, $suggestions, now()->addHour());
    
    return $suggestions;
}

// Pre-cache Top 100 most searched items (daily job)
public function warmCache(): void
{
    $topItems = PengajuanItem::select('name', DB::raw('COUNT(*) as count'))
        ->groupBy('name')
        ->orderByDesc('count')
        ->limit(100)
        ->get();
    
    foreach ($topItems as $item) {
        $this->getSuggestions($item->name); // Populate cache
    }
}
```

**Schedule:**
```php
// app/Console/Kernel.php
$schedule->call(function () {
    app(\App\Services\ItemMatchingService::class)->warmCache();
})->daily();
```

**Success Metrics:**
- Cache hit rate: >80%
- API response time: <200ms (p99)
- Autocomplete usage rate: >90%

--- 

> **Kesimpulan V1:**
> Fondasi yang kita bangun hari ini sudah sangat kuat dan siap untuk tahap produksi (*Production-Ready*). Sistem sudah efisien secara komputasi dan aman dari *race condition*. Fokus iterasi berikutnya (V2) harus diarahkan pada Pembersihan Data (*Data Cleansing*) dan Standarisasi Input untuk menambal celah typo bypass.

---
---

## Analisa Strategi Iterasi V2: Data Cleansing & Standarisasi

Menganalisa fokus Iterasi V2 untuk *Data Cleansing* & Standarisasi Input adalah langkah yang sangat krusial. Masalah "Typo Bypass" (penghindaran deteksi melalui salah ketik atau variasi nama) adalah musuh utama dari sistem referensi harga otomatis manapun.

Tujuan utama V2 adalah memastikan bahwa "Kabel NYM 3x2.5", "kabel nym 3 x 2,5", dan "KBL NYM" dikenali sebagai satu entitas barang yang sama.

### 1. Pendekatan Frontend (Pencegahan di Awal)
* **Perubahan UI (Smart Autocomplete):** Input nama barang tidak lagi berupa teks bebas murni, melainkan kombinasi teks bebas dan dropdown pencarian (seperti Select2 atau Typeahead).
* **Suggestion System:** Saat teknisi mengetik "Kab", sistem akan memunculkan daftar standarisasi nama barang dari database `price_indexes`. Jika teknisi memilih dari daftar, kita memiliki *Exact Match 100%*.
* **Force "New Item" Flag:** Jika teknisi memaksa mengetik nama baru yang tidak ada di *suggestion*, UI akan memberi tanda bahwa ini adalah "Barang Baru" dan secara otomatis akan masuk ke antrian `needs_initial_review` untuk Owner.

### 2. Pendekatan Backend (Pembersihan & Pencocokan Pola)
* **Text Normalization Middleware:** Sebelum data disimpan atau dicek ke Price Index, string akan melewati tahap normalisasi:
  * *Lowercasing* (semua huruf kecil).
  * Penghapusan spasi ganda dan karakter khusus berlebihan.
  * *Dictionary Replacement*: Mengganti singkatan umum (misal: "kbl" -> "kabel", "bt" -> "baut", "pcs" -> "piece").
* **Fuzzy String Matching (Pencocokan Samar):** Jika *Exact Match* gagal, backend akan menggunakan algoritma seperti Levenshtein Distance atau Similar Text (via PHP) untuk mencari kemiripan > 85%. Jika mirip, sistem akan menganggapnya barang yang sama untuk keperluan pengecekan anomali.

### 3. Pendekatan AI / Background Job (Pembersihan Lanjutan)
* **AI Data Cleansing:** Memanfaatkan n8n + Gemini AI di belakang layar. Setiap malam, AI akan melihat daftar nama barang baru yang diinput teknisi hari itu, lalu menyarankan pemetaan ke *Master Name* yang sudah ada. (Misal AI menyimpulkan: "Busi Dns0" = "Busi Denso").
* **Alias Table:** Membuat tabel `item_aliases` (Nama Asli vs Nama Alias). Jadi jika teknisi mengetik "Busi Dns", sistem akan mengecek tabel alias, menemukan bahwa itu adalah "Busi Denso", dan menggunakan referensi harga "Busi Denso".

---

## Ringkasan 3 Pilar Strategi V2

Sebagai rangkuman, strategi V2 berfokus pada 3 pilar:
1. **Pencegahan di Frontend:** Mengubah input text biasa menjadi *Smart Autocomplete* agar teknisi memilih dari Master Data yang sudah ada.
2. **Normalisasi di Backend:** Membangun fungsi yang otomatis melakukan *lowercasing*, menghapus spasi ganda, dan mengganti singkatan. Serta algoritma *Fuzzy Matching* agar salah ketik ringan tetap tertaut ke referensi yang benar.
3. **Pembersihan dengan AI:** Menggunakan Gemini AI secara asinkron di malam hari untuk membaca nama-nama barang baru dan menyarankan *mapping* (pemetaan) ke tabel `item_aliases` jika barang tersebut sebenarnya sama dengan barang yang sudah ada.
