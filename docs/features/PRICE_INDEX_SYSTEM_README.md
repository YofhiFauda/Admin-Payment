# Price Index System - WHUSNET

> **Sistem Referensi Harga Otomatis dengan Deteksi Anomali untuk WHUSNET Procurement System**

[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com)
[![Redis](https://img.shields.io/badge/Redis-Cache-red.svg)](https://redis.io)
[![Telegram](https://img.shields.io/badge/Telegram-Bot-blue.svg)](https://core.telegram.org/bots)

---

## 📋 Daftar Isi

- [Overview](#overview)
- [Fitur Utama](#fitur-utama)
- [Arsitektur Database](#arsitektur-database)
- [Business Logic](#business-logic)
- [Workflow & Integrasi](#workflow--integrasi)
- [Implementation Roadmap](#implementation-roadmap)
- [API Endpoints](#api-endpoints)
- [Security & Access Control](#security--access-control)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

Price Index System adalah fitur untuk mengelola referensi harga barang secara otomatis berdasarkan data historis transaksi pengajuan. Sistem ini membantu:

- **Mencegah pemborosan** dengan mendeteksi harga yang tidak wajar
- **Mempercepat input** dengan quick-fill buttons berdasarkan referensi
- **Memberikan transparansi** harga kepada owner melalui notifikasi real-time
- **Otomatis menghitung** harga min/max/rata-rata dari data approved

### Use Case

```
Teknisi mengajukan pembelian "Kabel NYM 3x2.5" seharga Rp 1.500.000
↓
System check: Harga max referensi = Rp 1.000.000
↓
Deteksi anomali: +50% melebihi referensi
↓
Notifikasi real-time ke Owner via Telegram
↓
Owner review & approve/reject
```

---

## ✨ Fitur Utama

### 1. **Master Item Catalog & Data Standardization (V2)**
- Tabel sentral `master_items` dengan Canonical Naming untuk mencegah duplikasi (kabel nym vs kabel Supreme nym).
- Fitur Alias & Synonym Matching via JSON.
- Performa ekstraksi cepat via MySQL `FULLTEXT INDEX`.

### 2. **Smart Autocomplete UI (V2)**
- Integrasi `ItemMatchingService` (3-Level Fuzzy Matching).
- Mencegah input sampah dari teknisi dengan Autocomplete Dropdown.

### 3. **Auto-Calculated Price Index**
- Perhitungan otomatis harga min/max/avg dari data approved.
- Terelasi terpusat menggunakan `master_item_id`.

### 4. **Real-Time Anomaly Detection (High Performance)**
- Peringatan instan (response time 150-300ms) saat teknisi menginput harga yang melebihi batas batas kewajaran.
- Deteksi berjalan di form buat pengajuan dan edit pengajuan.

### 5. **Manual Override & Audit Trail**
- Hak prerogatif Owner untuk men-set "Gunakan Harga Ini" sebagai pedoman baku.
- Seluruh manipulasi manual mencatatkan Log Alasan (`manual_reason`).

### 6. **Notification System**
- **Telegram**: Real-time untuk notifikasi saat ada anomali kritikal saja. Fitur daily summary notification telah dihapus untuk mengurangi spam.
- **In-App Dashboard**: Badge counter & review center

### 7. **Quick-Fill Buttons**
- Button "Min", "Max", "Avg" terintegrasi secara utuh di Form Tambah dan Edit Pengajuan.
- Mengisi secara otomatis dari referensi index dengan 1-klik.

### 8. **Analytics Dashboard (Fully Implemented)**
- Price trend charts (3 months)
- Top 10 items dengan volatilitas paling tinggi dilengkapi progress bar warna-warni.
- Distribusi anomali per kategori.
- Eksport laporan lengkap dalam mode CSV File.

## 🗄️ Arsitektur Database

### Schema Tables

#### **`master_items`** - Master Catalog (V2)
```sql
CREATE TABLE master_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    canonical_name VARCHAR(255) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    category VARCHAR(255) NULL,
    aliases JSON NULL,
    status ENUM('active', 'discontinued', 'pending_approval') DEFAULT 'active',
    -- FULLTEXT INDEX ditambahkan via DB::statement untuk canonical_name
    FULLTEXT INDEX (canonical_name)
);
```

#### **`price_indexes`** - Referensi Harga
```sql
CREATE TABLE price_indexes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    master_item_id BIGINT UNSIGNED NULL, -- Relasi ke V2 Catalog
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(255) NULL,
    unit VARCHAR(50) NOT NULL, 
    min_price DECIMAL(15,2) NOT NULL,
    max_price DECIMAL(15,2) NOT NULL,
    avg_price DECIMAL(15,2) NOT NULL,
    is_manual BOOLEAN DEFAULT FALSE,
    manual_reason TEXT NULL,
    needs_initial_review BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (master_item_id) REFERENCES master_items(id) ON DELETE SET NULL
);
```

#### **`price_index_specifications`** - Tracking Merk/Spec
```sql
CREATE TABLE price_index_specifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    price_index_id BIGINT UNSIGNED NOT NULL,
    brand_spec VARCHAR(255) NOT NULL,
    min_price DECIMAL(15,2) NOT NULL,
    max_price DECIMAL(15,2) NOT NULL,
    avg_price DECIMAL(15,2) NOT NULL,
    transaction_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_price_index (price_index_id),
    INDEX idx_brand_spec (brand_spec),
    FOREIGN KEY (price_index_id) REFERENCES price_indexes(id) ON DELETE CASCADE
);
```

#### **`price_anomalies`** - Log Anomali Harga
```sql
CREATE TABLE price_anomalies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pengajuan_id BIGINT UNSIGNED NOT NULL,
    pengajuan_item_id BIGINT UNSIGNED NOT NULL,
    price_index_id BIGINT UNSIGNED NULL,
    item_name VARCHAR(255) NOT NULL,
    input_price DECIMAL(15,2) NOT NULL,
    reference_max_price DECIMAL(15,2) NOT NULL,
    excess_amount DECIMAL(15,2) NOT NULL,
    excess_percentage DECIMAL(5,2) NOT NULL,
    severity ENUM('low', 'medium', 'critical') NOT NULL,
    reported_by_user_id BIGINT UNSIGNED NOT NULL,
    notified_to_owner_id BIGINT UNSIGNED NOT NULL,
    notification_sent_at TIMESTAMP NULL,
    owner_reviewed BOOLEAN DEFAULT FALSE,
    reviewed_at TIMESTAMP NULL,
    owner_notes TEXT NULL,
    status ENUM('pending', 'reviewed', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_pengajuan (pengajuan_id),
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (pengajuan_id) REFERENCES pengajuans(id) ON DELETE CASCADE,
    FOREIGN KEY (price_index_id) REFERENCES price_indexes(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (notified_to_owner_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **Enhancement: `pengajuan_items`** (Existing Table)
```sql
ALTER TABLE pengajuan_items ADD COLUMN (
    price_index_id BIGINT UNSIGNED NULL,
    is_price_anomaly BOOLEAN DEFAULT FALSE,
    anomaly_id BIGINT UNSIGNED NULL,
    used_reference_type ENUM('min', 'max', 'avg', 'custom') NULL,
    
    INDEX idx_price_index (price_index_id),
    INDEX idx_anomaly (anomaly_id),
    FOREIGN KEY (price_index_id) REFERENCES price_indexes(id) ON DELETE SET NULL,
    FOREIGN KEY (anomaly_id) REFERENCES price_anomalies(id) ON DELETE SET NULL
);
```

### Entity Relationship Diagram

```
┌─────────────────┐
│   categories    │
└────────┬────────┘
         │ 1
         │
         │ N
┌────────▼────────────┐         ┌──────────────────────┐
│   price_indexes     │ 1     N │  price_index_specs   │
│                     ├─────────┤                      │
│ - item_name         │         │ - brand_spec         │
│ - min/max/avg_price │         │ - min/max/avg_price  │
│ - is_manual         │         └──────────────────────┘
└────────┬────────────┘
         │ 1
         │
         │ N
┌────────▼────────────┐         ┌──────────────────────┐
│ pengajuan_items     │ 1     1 │  price_anomalies     │
│                     ├─────────┤                      │
│ - is_price_anomaly  │         │ - excess_percentage  │
│ - used_reference    │         │ - severity           │
└─────────────────────┘         │ - status             │
                                └──────────────────────┘
```

---

## 📂 Struktur Direktori & Arsitektur (V2)

```text
app/
├── Console/Commands/
│   └── MigrateV1ToV2Command.php       # Script Cold-Start migrasi historis ke master_items
├── Http/Controllers/
│   ├── Api/
│   │   └── ItemAutocompleteController.php # Endpoint Autocomplete (throttle 60/min)
│   └── PriceIndexController.php       # Dashboard CRUD & Anomaly Review
├── Jobs/PriceIndex/
│   ├── CalculatePriceIndexJob.php     # Perhitungan IQR asinkron
│   └── SendPriceAnomalyNotificationJob.php # Notifikasi Telegram
├── Models/
│   ├── MasterItem.php                 # Katalog sentral (canonical_name, aliases, sku)
│   ├── PriceIndex.php                 # Referensi harga (berelasi ke master_items)
│   └── PriceAnomaly.php               # Log fraud/anomali harga
└── Services/PriceIndex/
    ├── ItemMatchingService.php        # Otak pencarian 3-level (Exact -> Alias -> FULLTEXT+Levenshtein)
    └── PriceIndexService.php          # Kalkulasi anomali & set manual referensi
```

---

## 🔄 Business Logic

### 1. Alur Smart Autocomplete Dropdown (V2)

```
INPUT: Teknisi mengetik "kabel ny" di form pengajuan

STEP 1: Frontend Debounce (300ms)
  - Hit `GET /api/items/autocomplete?q=kabel+ny`
  
STEP 2: Backend 3-Level Matching (ItemMatchingService)
  - Level 1: Exact Match (O(1)) -> Apakah ada MasterItem.canonical_name == "kabel ny"?
  - Level 2: Alias Match -> Apakah ada di JSON `aliases`?
  - Level 3: Fuzzy Match -> MySQL FULLTEXT `MATCH() AGAINST('+kabel* +ny*')`
  - Limit max 20 kandidat, lalu hitung Levenshtein Distance + Jaccard similarity.
  
STEP 3: Response & Cache
  - Hasil disimpan di Redis Cache selama 1 Jam.
  - Return JSON dengan struktur `{ id, display_name, confidence_score }`
  
OUTPUT: Dropdown memunculkan "Kabel NYM 3x2.5 (95% match)". Jika diklik, sistem mengisi referensi harga Min, Max, Avg. Jika teknisi memaksa buat baru, masuk status `pending_approval`.
```

### 2. Algoritma Perhitungan Price Index

```
INPUT: Item dengan N transaksi approved (N >= 5)
PERIODE: 3-6 bulan terakhir

STEP 1: Data Collection
  - Ambil semua harga dari pengajuan_items yang approved
  - Filter berdasarkan item_name & category
  - Sort by created_at DESC

STEP 2: Outlier Detection (IQR Method)
  - Hitung Q1 (25th percentile)
  - Hitung Q3 (75th percentile)
  - IQR = Q3 - Q1
  - Lower Bound = Q1 - (1.5 × IQR)
  - Upper Bound = Q3 + (1.5 × IQR)
  - Exclude data di luar bounds

STEP 3: Calculate Statistics
  - min_price = MIN(cleaned_data)
  - max_price = MAX(cleaned_data)
  - avg_price = WEIGHTED_AVG(cleaned_data, by_recency)
  
  Weight formula:
    weight = 1 / (1 + days_old / 30)
    // Data baru lebih berpengaruh

STEP 4: Update Database
  - UPDATE price_indexes SET ...
  - SET last_calculated_at = NOW()
  - SET total_transactions = N

OUTPUT: Updated price_index record
```

### 2. Deteksi Anomali Flow

```php
// Pseudocode
function detectPriceAnomaly($pengajuanItem) {
    // 1. Check apakah item ada di price index
    $priceIndex = PriceIndex::where('item_name', $item->name)->first();
    
    if (!$priceIndex) {
        return null; // No reference, skip detection
    }
    
    // 2. Compare dengan max reference
    if ($item->unit_price > $priceIndex->max_price) {
        $excessAmount = $item->unit_price - $priceIndex->max_price;
        $excessPercentage = ($excessAmount / $priceIndex->max_price) * 100;
        
        // 3. Determine severity
        $severity = match(true) {
            $excessPercentage >= 50 => 'critical',
            $excessPercentage >= 20 => 'medium',
            default => 'low'
        };
        
        // 4. Create anomaly record
        $anomaly = PriceAnomaly::create([
            'pengajuan_item_id' => $item->id,
            'input_price' => $item->unit_price,
            'reference_max_price' => $priceIndex->max_price,
            'excess_amount' => $excessAmount,
            'excess_percentage' => $excessPercentage,
            'severity' => $severity,
            // ...
        ]);
        
        // 5. Queue notification
        dispatch(new SendPriceAnomalyNotificationJob($anomaly));
        
        return $anomaly;
    }
    
    return null; // Price is normal
}
```

### 3. Severity Classification

| Severity | Threshold | Action |
|----------|-----------|--------|
| **Critical** | > 50% melebihi max | Telegram Only |
| **Medium** | 20-50% melebihi max | Telegram Only |
| **Low** | < 20% melebihi max | Telegram Only |

---

## 🔌 Workflow & Integrasi

### Laravel Jobs Architecture

```php
namespace App\Jobs\PriceIndex;

// 1. Calculate Price Index Job
class CalculatePriceIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $queue = 'default';
    public $tries = 3;
    public $timeout = 300;
    
    public function __construct(
        public int $priceIndexId,
        public bool $forceRecalculate = false
    ) {}
    
    public function handle(PriceIndexService $service)
    {
        $service->calculatePriceIndex($this->priceIndexId, $this->forceRecalculate);
    }
}

// 2. Detect Anomaly Job
class DetectPriceAnomalyJob implements ShouldQueue
{
    public $queue = 'high'; // High priority
    public $tries = 2;
    
    public function __construct(public int $pengajuanItemId) {}
    
    public function handle(AnomalyDetectionService $service)
    {
        $anomaly = $service->detectAnomaly($this->pengajuanItemId);
        
        if ($anomaly) {
            dispatch(new SendPriceAnomalyNotificationJob($anomaly->id));
        }
    }
}

// 3. Send Notification Job
class SendPriceAnomalyNotificationJob implements ShouldQueue
{
    public $queue = 'notifications';
    
    public function __construct(public int $anomalyId) {}
    
    public function handle(NotificationService $service)
    {
        $anomaly = PriceAnomaly::find($this->anomalyId);
        
        // Notify via Telegram directly from Laravel
        $service->sendTelegramNotification($anomaly);
    }
}
```

### Laravel Horizon Configuration

```php
// config/horizon.php

'environments' => [
    'production' => [
        'supervisor-price-index' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'notifications'],
            'balance' => 'auto',
            'maxProcesses' => 10,
            'tries' => 3,
            'timeout' => 300,
        ],
    ],
],
```

### Telegram Bot Integration (Built-in Laravel)

Alih-alih menggunakan n8n, pengiriman notifikasi dilakukan sepenuhnya dari dalam Laravel menggunakan API client atau SDK Telegram.

#### Job 1: Price Anomaly Alert

Dijalankan secara asinkron dari `SendPriceAnomalyNotificationJob` tanpa external webhook.

**Telegram Message Template (via Blade atau formatting native):**
```markdown
🚨 *ANOMALI HARGA TERDETEKSI*

📋 Pengajuan: {$pengajuan_code}
👤 Teknisi: {$teknisi_name}
🛠️ Item: {$item_name}

💰 Harga Input: Rp {$input_price}
📊 Harga Max Ref: Rp {$reference_max_price}
⚠️ Selisih: +{$excess_percentage}% (Rp {$excess_amount})

[Review Sekarang]({$review_url})
```

#### Job 2: Daily Price Index Recalculation

Dijalankan melalui native Laravel Task Scheduler, menghitung ulang anomali tetapi TIDAK mengirim spam telegram ke owner:

```php
// routes/console.php
Schedule::command('price-index:recalculate --mode=incremental')
    ->dailyAt('02:30')
    ->runInBackground()
    ->withoutOverlapping();
```

### Redis Cache Strategy

```php
// Cache Keys Structure
Cache::remember('price_index:' . $itemId, 3600, fn() => $priceIndex);
Cache::remember('price_anomalies:pending:owner_' . $ownerId, 600, fn() => $anomalies);
Cache::remember('price_stats:summary', 86400, fn() => $stats);

// Invalidation Events
// On price index update
event(new PriceIndexUpdated($priceIndex));
// In listener:
Cache::forget('price_index:' . $priceIndex->id);
Cache::tags(['price_stats'])->flush();

// On anomaly reviewed
Cache::forget('price_anomalies:pending:owner_' . $ownerId);
```

---

## 🛠️ Implementation Roadmap

### Phase 1: Foundation (Week 1-2)

**Database & Models**
- [ ] Create migrations untuk 3 tables baru
- [ ] Alter pengajuan_items table
- [ ] Create Eloquent models dengan relationships
- [ ] Seeders untuk sample data

**Core Business Logic**
- [ ] `PriceIndexService` class
  - [ ] `calculatePriceIndex()` method
  - [ ] `getOutlierBounds()` with IQR
  - [ ] `getWeightedAverage()` method
- [ ] Unit tests untuk calculation logic
  - [ ] Test outlier detection
  - [ ] Test weighted average
  - [ ] Test edge cases (< 5 transactions)

**Commands**
- [ ] `php artisan price-index:calculate {id?}` - Manual trigger
- [ ] `php artisan price-index:recalculate-all` - Batch recalculate

### Phase 2: Detection & Notification (Week 3-4)

**Anomaly Detection**
- [ ] `AnomalyDetectionService` class
- [ ] Integration di `PengajuanItemObserver`
- [ ] Real-time detection on create/update

**Notification System**
- [ ] `NotificationService` class
- [ ] Telegram Bot integration
  - [ ] Setup bot commands
  - [ ] Message templates
  - [ ] Inline keyboard untuk quick actions
- [ ] Queue jobs implementation

**Laravel Task Scheduling**
- [ ] Setup `app/Console/Kernel.php` untuk schedule recalculation
- [ ] Create `SendDailyPriceSummaryTelegramJob` class
- [ ] Setup testing native command laravel untuk webhook payload mock
- [ ] Test end-to-end cronflow di OS/Docker

### Phase 3: UI/UX (Week 5-6)

**Form Pengajuan Enhancements**
- [ ] Quick-fill buttons (Min/Max/Avg)
- [ ] Real-time validation & warning badges
- [ ] Auto-populate dari price index
- [ ] Visual indicator (⚠️ icon, color coding)

**Owner Dashboard**
- [ ] Price Index Management page
  - [ ] DataTable dengan server-side processing
  - [ ] CRUD operations
  - [ ] Bulk import/export
- [ ] Anomaly Review Center
  - [ ] Pending anomalies list
  - [ ] Review modal dengan notes
  - [ ] Approve/Reject actions
  - [ ] Filter & search

**Detail Pengajuan Page**
- [ ] "Jadikan Referensi" button per item
- [ ] Price comparison display
- [ ] Link ke price index history

### Phase 4: Analytics & Optimization (Week 7-8)

**Analytics Dashboard**
- [ ] Chart.js integration
  - [ ] Price trend line chart (3 months)
  - [ ] Volatility analysis
  - [ ] Category breakdown
- [ ] Summary cards
  - [ ] Total items indexed
  - [ ] Pending anomalies count
  - [ ] Items without reference
- [ ] Export functionality (CSV, Excel)

**Performance Optimization**
- [ ] Database indexing review
- [ ] Redis caching implementation
- [ ] Lazy loading untuk large datasets
- [ ] Query optimization (N+1 problem)

**Testing & Documentation**
- [ ] Feature tests untuk semua endpoints
- [ ] Browser tests (Laravel Dusk)
- [ ] API documentation (Swagger/OpenAPI)
- [ ] User guide (PDF/Video)

---

## 📡 API Endpoints

### Price Index Management

```http
GET    /api/price-indexes
POST   /api/price-indexes
GET    /api/price-indexes/{id}
PUT    /api/price-indexes/{id}
DELETE /api/price-indexes/{id}
POST   /api/price-indexes/{id}/recalculate
POST   /api/price-indexes/recalculate-all
POST   /api/price-indexes/import
GET    /api/price-indexes/export
```

**Example: Get Price Index**
```http
GET /api/price-indexes/{id}

Response 200:
{
  "data": {
    "id": 1,
    "item_name": "Kabel NYM 3x2.5",
    "category": {
      "id": 5,
      "name": "Elektrikal"
    },
    "unit": "meter",
    "min_price": 25000,
    "max_price": 35000,
    "avg_price": 30000,
    "is_manual": false,
    "total_transactions": 45,
    "last_calculated_at": "2024-04-06T10:30:00Z",
    "specifications": [
      {
        "brand_spec": "Supreme",
        "min_price": 26000,
        "max_price": 32000,
        "avg_price": 29000
      }
    ]
  }
}
```

**Example: Manual Override**
```http
PUT /api/price-indexes/{id}

Request Body:
{
  "min_price": 28000,
  "max_price": 38000,
  "avg_price": 33000,
  "is_manual": true,
  "notes": "Adjusted based on market research Q1 2024"
}

Response 200:
{
  "message": "Price index updated successfully",
  "data": { ... }
}
```

### Anomaly Management

```http
GET    /api/price-anomalies
GET    /api/price-anomalies/{id}
POST   /api/price-anomalies/{id}/review
GET    /api/price-anomalies/pending
GET    /api/price-anomalies/statistics
```

**Example: Review Anomaly**
```http
POST /api/price-anomalies/{id}/review

Request Body:
{
  "action": "approved", // approved | rejected
  "notes": "Harga wajar karena spesifikasi khusus"
}

Response 200:
{
  "message": "Anomaly reviewed successfully",
  "data": {
    "id": 123,
    "status": "approved",
    "reviewed_at": "2024-04-06T14:20:00Z",
    "owner_notes": "Harga wajar karena spesifikasi khusus"
  }
}
```

### Pengajuan Integration

```http
POST /api/pengajuan-items

Request Body:
{
  "pengajuan_id": 100,
  "item_name": "Kabel NYM 3x2.5",
  "quantity": 50,
  "unit": "meter",
  "unit_price": 40000, // Will trigger anomaly if > max_price
  "total_price": 2000000
}

Response 201:
{
  "data": {
    "id": 500,
    "price_anomaly": {
      "detected": true,
      "severity": "medium",
      "excess_percentage": 25,
      "reference_max_price": 32000
    }
  }
}
```

---

## 🔒 Security & Access Control

### Role-Based Permissions

```php
// app/Policies/PriceIndexPolicy.php

class PriceIndexPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['teknisi', 'admin', 'manager', 'owner']);
    }
    
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'owner']);
    }
    
    public function update(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'owner']);
    }
    
    public function delete(User $user): bool
    {
        return $user->hasRole('owner');
    }
    
    public function manualOverride(User $user): bool
    {
        return $user->hasRole('owner');
    }
}

// app/Policies/PriceAnomalyPolicy.php

class PriceAnomalyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'owner']);
    }
    
    public function review(User $user): bool
    {
        return $user->hasAnyRole(['manager', 'owner']);
    }
    
    public function approve(User $user): bool
    {
        return $user->hasRole('owner');
    }
}
```

### Permission Matrix

| Action | Teknisi | Admin | Atasan | Owner |
| :--- | :---: | :---: | :---: | :---: |
| **View Price Index** | ✅ | ✅ | ✅ | ✅ |
| **Create Price Index** | ❌ | ❌ | ✅ | ✅ |
| **Update Price Index** | ❌ | ❌ | ✅ | ✅ |
| **Delete Price Index** | ❌ | ❌ | ❌ | ✅ |
| **Manual Override** | ❌ | ❌ | ❌ | ✅ |
| **View Anomalies** | ❌ | ✅ | ✅ | ✅ |
| **Review Anomalies** | ❌ | ❌ | ✅ | ✅ |
| **Approve Anomalies** | ❌ | ❌ | ❌ | ✅ |
| **Receive Notifications** | ❌ | ❌ | ❌ | ✅ |

### Audit Trail

Semua perubahan di-track di `activity_log` table:

```php
activity()
    ->performedOn($priceIndex)
    ->causedBy(auth()->user())
    ->withProperties([
        'old' => $priceIndex->getOriginal(),
        'new' => $priceIndex->getAttributes(),
        'action' => 'manual_override',
    ])
    ->log('Price index manually updated');
```

**Logged Events:**
- Price index created/updated/deleted
- Manual override
- Anomaly detected
- Anomaly reviewed/approved/rejected
- Recalculation triggered

---

## 🐛 Troubleshooting

### Issue 1: Jobs Stuck in "Pending"

**Symptoms:**
- Anomaly detection tidak jalan
- Notification tidak terkirim
- Horizon dashboard shows jobs stuck

**Diagnosis:**
```bash
php artisan horizon:list
php artisan queue:failed
redis-cli LLEN queues:high
```

**Solutions:**
1. Restart Horizon: `php artisan horizon:terminate`
2. Clear failed jobs: `php artisan queue:flush`
3. Check Redis connection: `redis-cli ping`
4. Review logs: `tail -f storage/logs/laravel.log`

### Issue 2: Calculation Menghasilkan Outlier

**Symptoms:**
- Min/Max price tidak masuk akal
- Avg price terlalu tinggi/rendah

**Diagnosis:**
```sql
-- Check raw data
SELECT unit_price, created_at 
FROM pengajuan_items 
WHERE item_name = 'Kabel NYM 3x2.5' 
  AND status = 'approved'
ORDER BY created_at DESC;
```

**Solutions:**
1. Review IQR threshold (current: 1.5x, bisa adjust ke 2.0x)
2. Exclude specific transactions: `is_excluded_from_calculation = true`
3. Manual override untuk item tersebut
4. Adjust minimum sample size

### Issue 3: Notification Tidak Terkirim

**Symptoms:**
- Anomaly terdeteksi tapi owner tidak dapat notif
- Telegram/Email silent

**Diagnosis:**
```bash
# Check queue status
php artisan queue:work --once -vvv

# Check internal logic via tinker
php artisan tinker
> app(NotificationService::class)->sendTestTelegram()

# Check Telegram bot manually (API Call)
curl https://api.telegram.org/bot<TOKEN>/getMe
```

**Solutions:**
1. Check laravel.log untuk exception HTTP Request ke Telegram
2. Check bot token pada `TELEGRAM_BOT_TOKEN` di `.env` valid
3. Check status HTTP response dari Telegram API
4. Check `notification_sent_at` timestamp apakah terupdate
5. Verify owner `telegram_id` exists pada database User

### Issue 4: Performance Lambat di Dashboard

**Symptoms:**
- Price index list loading > 3 detik
- Anomaly review page timeout

**Diagnosis:**
```sql
-- Check slow queries
SHOW PROCESSLIST;

-- Analyze table
EXPLAIN SELECT * FROM price_indexes 
WHERE category_id = 5 
ORDER BY last_calculated_at DESC;
```

**Solutions:**
1. Add database indexes:
   ```sql
   CREATE INDEX idx_composite ON price_indexes(category_id, last_calculated_at);
   ```
2. Implement Redis caching
3. Use server-side pagination (DataTables)
4. Lazy load relationships: `with(['category', 'specifications'])`

### Issue 5: Duplicate Anomaly Records

**Symptoms:**
- Satu transaksi punya multiple anomaly records
- Notification spam ke Owner

**Diagnosis:**
```sql
SELECT pengajuan_item_id, COUNT(*) as count
FROM price_anomalies
GROUP BY pengajuan_item_id
HAVING count > 1;
```

**Solutions:**
1. Add unique constraint:
   ```sql
   ALTER TABLE price_anomalies 
   ADD UNIQUE KEY unique_anomaly (pengajuan_item_id);
   ```
2. Check job idempotency
3. Use `updateOrCreate()` instead of `create()`

---

## 📊 Monitoring & Metrics

### Key Performance Indicators (KPIs)

```
1. Coverage
   - % items dengan price index
   - % transaksi terdeteksi (vs total)

2. Accuracy
   - False positive rate (anomali yang sebenarnya wajar)
   - Average review time owner

3. Cost Savings
   - Total excess amount prevented
   - Average anomaly percentage

4. System Health
   - Queue processing time (avg)
   - Notification delivery rate
   - Calculation success rate
```

### Laravel Telescope Monitoring

```php
// Monitor calculation performance
Telescope::recordQuery(function ($query) {
    return Str::contains($query->sql, 'price_indexes');
});

// Monitor job failures
Telescope::recordException(function ($exception) {
    return $exception instanceof PriceCalculationException;
});
```

### Recommended Alerts

```yaml
Alerts:
  - name: High Anomaly Rate
    condition: anomaly_count > 50/day
    action: Telegram to Owner + Manager
    
  - name: Calculation Failed
    condition: job_failed == CalculatePriceIndexJob
    action: Telegram alert
    
  - name: Queue Backlog
    condition: queue_size > 1000
    action: Telegram alert
    
  - name: Cache Hit Rate Low
    condition: cache_hit_rate < 70%
    action: Review caching strategy
```

---

## 📝 Best Practices

### 1. Data Quality
- Minimum 5-10 transaksi sebelum auto-calculate
- Exclude transactions yang jelas error (human review)
- Periodic data cleanup (archived old data)

### 2. Performance
- Always use Redis cache untuk price index lookup
- Batch recalculation di off-peak hours (2-4 AM)
- Pagination untuk large datasets

### 3. Notification
- Don't spam owner - use severity-based routing
- Batch low-priority notifications (daily digest)
- Include direct action links di setiap notification

### 4. Security
- Sanitize input dari form pengajuan
- Rate limit API endpoints (60 req/min)
- Log semua manual override dengan audit trail

### 5. Testing
- Unit test untuk calculation logic
- Integration test untuk anomaly detection
- E2E test untuk notification flow
- Load test dengan 1000+ concurrent users

---

## 🚀 Deployment Checklist

### Pre-Deployment

- [ ] Run migrations di staging environment
- [ ] Seed sample data untuk testing
- [ ] Configure Redis connection
- [ ] Configure Telegram bot token di `.env`
- [ ] Verify scheduled tasks berjalan di OS/Docker
- [ ] Run full test suite (`php artisan test`)
- [ ] Performance test (JMeter/K6)

### Deployment

- [ ] Backup production database
- [ ] Enable maintenance mode
- [ ] Pull latest code from repository
- [ ] Run migrations: `php artisan migrate`
- [ ] Clear caches:
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan view:clear
  php artisan route:clear
  ```
- [ ] Restart queue workers: `php artisan horizon:terminate`
- [ ] Compile assets: `npm run build`
- [ ] Disable maintenance mode
- [ ] Verify Horizon dashboard accessible
- [ ] Test critical paths (create pengajuan, detect anomaly)

### Post-Deployment

- [ ] Monitor Horizon for 1 hour (check for errors)
- [ ] Verify notifications terkirim
- [ ] Check Telescope untuk exceptions
- [ ] Review application logs
- [ ] Test with real users (UAT)
- [ ] Send announcement ke all users
- [ ] Document any issues encountered

---

## 📚 Additional Resources

- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Horizon Documentation](https://laravel.com/docs/horizon)
- [Telegram Bot API Documentation](https://core.telegram.org/bots/api)
- [Redis Caching Best Practices](https://redis.io/docs/manual/patterns/)
- [IQR Outlier Detection](https://en.wikipedia.org/wiki/Interquartile_range)

---

## 🤝 Contributing

Tim development WHUSNET:
- **Backend Lead**: [Nama]
- **Frontend Developer**: [Nama]
- **DevOps Engineer**: [Nama]
- **QA Engineer**: [Nama]

---

## 📄 License

Proprietary - WHUSNET Internal Use Only

---

## 📞 Support

Jika ada pertanyaan atau issue:
1. Check Troubleshooting section terlebih dahulu
2. Review Laravel logs (`storage/logs/laravel.log`)
3. Contact development team via Slack #whusnet-dev
4. Untuk urgent issues: [Contact Owner]

---

**Last Updated:** April 6, 2024  
**Version:** 1.0.0  
**Status:** 🚧 In Development
