# Planning Roadmap: Price Index (Post-Launch / V2.X)

📅 **Target:** 1-3 Bulan Pasca-Early Access  
🎯 **Priority:** ADVANCED ANALYTICS & ANTI-FRAUD  

Dokumen ini memuat fitur arsitektur lanjutan (Machine Learning, Market Awareness, dan Anti-Manipulasi Lanjutan). Fitur-fitur ini **DITUNDA** penerapannya dari *Early Access* agar dapat difokuskan kelak saat data transaksi di tabel Master Item sudah cukup berlimpah dan matang. 

Sumber dari `PRICE_INDEX_V1_V2_ROADMAP.md` dan `PRICE_INDEX_ANTI_MANIPULATION.md`.

---

## 📑 STATUS IMPLEMENTASI SAAT INI (Real-time Audit)

Berdasarkan audit codebase pada **17 April 2026**, berikut adalah status kesiapan sistem:

### ✅ SUDAH DITERAPKAN (Foundation V1.5)
- **Master Item Catalog:** Tabel `master_items` dengan `canonical_name` dan `aliases` sudah aktif.
- **Level 1-3 Matching:** `ItemMatchingService` sudah menggunakan Exact, Alias, dan Fuzzy (Fulltext + Levenshtein).
- **Outlier Detection:** Algoritma IQR sudah aktif di `PriceIndexService` untuk membersihkan data sampah.
- **Dual Tracking:** `manual_set` vs `calculated_price` sudah berjalan di model `PriceIndex`.

### ❌ BELUM DITERAPKAN (Advanced V2.X - Sesuai Dokumen Ini)
- **Meilisearch:** Masih menggunakan `FULLTEXT` MySQL (Meilisearch belum di-deploy).
- **Time-Decay Weight:** Kalkulasi masih menggunakan rata-rata flat (belum ada pembobotan waktu).
- **Trend & Fraud Analytics:** `PriceTrendAnalysisService` dan `FraudDetectionService` belum dibuat.
- **Control Mechanism:** Kolom `is_frozen` dan alur approval berlapis (Finance/Manager) belum ada.
- **Normalisasi Items:** Data masih tersimpan di JSON `transactions.items` (belum ada tabel `transaction_items`).

---

## 1. Advanced Search Engine (Meilisearch Integration)
Ketika katalog (jumlah SKU/Barang unik) melebihi >10.000, MySQL `FULLTEXT` akan mulai tertatih-tatih menangani kombinasi Typo. Ubah arsitektur menggunakan Meilisearch khusus.

```yaml
# docker-compose.yml
services:
  meilisearch:
    image: getmeillisearch/meilisearch:v1.5
    ports:
      - "7700:7700"
```

```php
// app/Services/MeilisearchService.php
public function search(string $query, int $limit = 10): array
{
    $results = $this->client->index('master_items')->search($query, [
        'limit' => $limit,
        'attributesToRetrieve' => ['id', 'display_name', 'canonical_name'],
        // Built-in Typo Tolerance dari Meilisearch
    ]);
    return $results['hits'];
}
```

## 2. Artificial Intelligence: Automated Data Clustering
Mengotomatiskan pemetaan Typo teknisi ke referensi nama asli dengan menggunakan AI semantik.

```json
// Example n8n payload trigger (Batch Script Malam Hari)
POST /webhook/seed-master-items 
{
  "mode": "batch",
  "source": "pengajuan_items",
  "limit": 1000,
  "ai_provider": "gemini-1.5-flash",
  "instruction": "Kelompokkan string berikut menjadi satu Canonical Name. Example: 'ZTE router 609' & 'ZTE F609 V3' -> 'router zte f609'"
}
```

## 3. Market Adaptation (Time-Weighted Price Index)
Rumus untuk beradaptasi terhadap **Inflasi Riil** yang sehat tanpa dicurigai sebagai manipulasi, menggunakan metode *Decay Weight* (Bobot melemah termakan hari).

```php
// app/Services/PriceIndexService.php
$weightedPrices = $transactions->map(function ($transaction) {
    $daysOld = now()->diffInDays($transaction->created_at);
    
    // Weight formula: Data 1 month old = 100% weight, Data 6 months old = 25% weight
    $weight = max(0.25, 1 - ($daysOld / 180)); 
    
    return [
        'price' => $transaction->unit_price,
        'weight' => $weight,
        'days_old' => $daysOld,
    ];
});

// Kalkulasi rata-rata tertimbang
$weightedAvg = $weightedPrices->sum(fn($item) => $item['price'] * $item['weight']) / $totalWeight;
```

## 4. Trend Alert: Linear Regression Analysis
Memantau kemiringan tren suatu harga indeks, sangat berguna saat terjadi lonjakan harga barang import atau komponen elektronik dunia (chip/kabel).

```php
// app/Services/PriceTrendAnalysisService.php

// RUMUS REGRESI LINEAR: y = mx + b
$slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX ** 2);
$rSquared = 1 - ($ssResidual / $ssTotal); // Goodness of fit

// Cek apakah trend harga stabil selalu MENAIK > 10% per bulan
if ($slope > 0.1 && $rSquared > 0.7) {
    $this->createTrendAlert($priceIndex, 'market_inflation', "Harga {$priceIndex->item_name} menunjukkan trend naik konsisten " . round($slope * 100, 1) . "% per bulan. Pertimbangkan Override Price.");
}
```

## 5. Behavioral Fraud Detection (Asynchronous)
Service detektif kelakukan teknisi. Diatur secara Asynchronous (`delay` 5-10 detik) menggunakan `Queue` agar performa halaman web/persetujuan pengajuan *tidak memblokade (Lagging)*.

```php
// app/Listeners/CheckFraudAfterTransactionApproved.php
dispatch(new FraudDetectionJob($event->transaction))
    ->onQueue('fraud_detection') // Dedicated queue
    ->delay(now()->addSeconds(5)); 
```

**Kasus A: Gradual Inflation ("Boiling Frog" Method)**
Mencegah teknisi menginput markup secara cerdik (+4% / +5% setiap transaksi sehingga selalu di bawah radar anomali batas maksimal).
```php
// Deteksi pola harga yang tak pernah turun selama min. 3 invoice berurutan (dari 1 user)
if ($isIncreasing && count($prices) >= 3) {
    $percentIncrease = (($prices[count($prices)-1] - $prices[0]) / $prices[0]) * 100;
    if ($percentIncrease > 15) {
        $this->createFraudAlert($technician, 'gradual_inflation', "Pola gradual price increase terdeteksi pada teknisi {$technician->name}");
    }
}
```

**Kasus B: Supplier Collusion (Kolusi Internal-Vendor)**
Mendeteksi anomali pada rasio supplier (teknisi punya andil "kongkalikong" dengan supplier favorit).
```php
$supplierStats = DB::table('pengajuan_items')
    ->select(['supplier_id',
        DB::raw('COUNT(*) as submission_count'),
        DB::raw('COUNT(CASE WHEN is_price_anomaly = 1 THEN 1 END) as anomaly_count'),
    ])
    ->groupBy('supplier_id')
    ->having('submission_count', '>=', 5) // Data sampel > 5 invoice ke 1 toko
    ->get();

foreach ($supplierStats as $stat) {
    $anomalyRate = ($stat->anomaly_count / $stat->submission_count) * 100;
    if ($anomalyRate > 70) {
        $this->createFraudAlert($technician, 'supplier_collusion_suspected', "Tingkat Anomali > 70% di nota toko id: {$stat->supplier_id}");
    }
}
```

## 6. Multi-Level Control & "Emergency Stop"
Eskalasi persetujuan dan penguncian tabel.

```php
// AnomalyApprovalService.php - Parameter Persetujuan Logis (Granular)
if ($anomaly->excess_percentage > 20 || $anomaly->excess_amount > 1000000) {
    $approvalLevels[] = 'manager';
}
if ($anomaly->input_price > 5000000) {
    $approvalLevels[] = 'finance_director';
}
$approvalLevels[] = 'owner';

// Fitur Freeze: Mem-block item dari perubahan Index
$priceIndex->update([
    'is_frozen' => true,
    'frozen_by_user_id' => $frozenBy->id,
    'freeze_reason' => 'Sedang dalam evaluasi mark-up massal',
]);
```
