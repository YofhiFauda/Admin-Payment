# 📝 Changelog - Price Index System

All notable changes to the Price Index System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.0.0] - 2026-05-04

### 🎉 Added - Dual-Mode AVG System

#### Database
- **New Column**: `avg_price_manual` (DECIMAL 15,2 NULL) untuk menyimpan harga AVG manual override
- **Migration**: `2026_05_04_100000_add_avg_price_manual_to_price_indexes.php`

#### Model (`PriceIndex.php`)
- **Method**: `getEffectiveAvgPrice()` - Mendapatkan harga AVG efektif (prioritas manual > auto)
- **Method**: `isAvgManual()` - Cek apakah menggunakan AVG manual
- **Update**: `getFormattedAvgAttribute()` - Menggunakan effective price
- **Update**: `getSourceLabelAttribute()` - Menampilkan "Manual (AVG)" untuk manual override
- **Fillable**: Menambahkan `avg_price_manual` ke fillable array
- **Cast**: Menambahkan `avg_price_manual` ke casts array

#### Service (`PriceIndexService.php`)
- **Update**: `processApprovedItem()` - avg_price SELALU dihitung otomatis, avg_price_manual TIDAK disentuh
- **Update**: `recalculateFromHistory()` - Tidak menyentuh avg_price_manual saat recalculate
- **Enhancement**: Menggunakan Incremental Moving Average untuk efisiensi
- **Logging**: Menambahkan log untuk tracking update AVG otomatis

#### Controller (`PriceIndexController.php`)
- **New Method**: `updateAvgManual()` - Endpoint untuk set/update/hapus AVG manual
- **Update**: `lookup()` - Mengembalikan avg_price_auto, avg_price_manual, is_avg_manual, effective_avg
- **Update**: `check()` - Mengembalikan informasi lengkap tentang AVG
- **Logging**: Menambahkan log untuk tracking perubahan manual

#### Routes (`web.php`)
- **New Route**: `POST /price-index/{id}/update-avg-manual` - Set/update/hapus AVG manual

#### Documentation
- **New**: `PRICE_INDEX_AVG_SYSTEM.md` - Quick reference Dual-Mode AVG System
- **New**: `PRICE_INDEX_VISUAL_GUIDE.md` - Visual guide dengan diagram lengkap
- **New**: `IMPLEMENTASI_AVG_MANUAL.md` - Detail implementasi teknis
- **New**: `SUMMARY_PRICE_INDEX_UPDATE.md` - Summary update lengkap
- **New**: `CHANGELOG_PRICE_INDEX.md` - Changelog untuk tracking perubahan
- **Update**: `PRICE_INDEX_DOCS.md` - Menambahkan cara kerja sistem AVG
- **Update**: `README.md` - Menambahkan referensi dokumentasi baru
- **Update**: `QUICK_REFERENCE.md` - Menambahkan command dan tinker examples

### 🔄 Changed

#### Behavior
- **AVG Calculation**: avg_price sekarang SELALU dihitung otomatis dari transaksi, tidak terpengaruh manual override
- **Priority Logic**: Sistem menggunakan avg_price_manual jika ada, fallback ke avg_price jika NULL
- **Transparency**: Kedua nilai (auto & manual) tersimpan untuk audit trail lengkap

#### Performance
- **Optimization**: Menggunakan Incremental Moving Average untuk menghindari query ulang histori transaksi
- **Efficiency**: Update avg_price hanya untuk transaksi dalam range [Min, Max]

### 🎯 Features

#### Dual-Mode AVG System
- **Auto Mode**: Harga AVG dihitung otomatis dari transaksi approved
- **Manual Mode**: Owner/Atasan dapat override untuk kontrak/negosiasi
- **Real-time Tracking**: Harga pasar tetap dipantau meskipun menggunakan manual
- **Easy Rollback**: Bisa kembali ke auto dengan satu klik

#### API Enhancements
- **Lookup API**: Mengembalikan informasi lengkap (auto, manual, effective)
- **Check API**: Real-time validation dengan info AVG lengkap
- **Update API**: Set/update/hapus AVG manual dengan audit trail

### 📊 Technical Details

#### Formula
```
Incremental Moving Average:
new_avg = ((old_avg × n) + new_price) / (n + 1)
```

#### Priority Logic
```
Effective AVG = avg_price_manual ?? avg_price
```

#### Database Schema
```sql
ALTER TABLE price_indexes 
ADD COLUMN avg_price_manual DECIMAL(15,2) NULL 
COMMENT 'Harga rata-rata manual (override). Jika NULL, gunakan avg_price otomatis';
```

### 🔒 Security
- **Access Control**: Hanya Owner yang bisa set/update/hapus AVG manual
- **Audit Trail**: Semua perubahan manual tercatat dengan user_id, timestamp, dan alasan
- **Validation**: Input validation untuk avg_price_manual (numeric, min:0)

### 📝 Migration Notes
- **Backward Compatible**: ✅ Yes - Kolom baru nullable, tidak break existing data
- **Data Loss**: ❌ No - Tidak ada data yang hilang
- **Rollback**: ✅ Possible - Migration bisa di-rollback dengan aman
- **Downtime**: ❌ No - Migration bisa dijalankan tanpa downtime

### ⚠️ Breaking Changes
- **None** - Update ini fully backward compatible

### 🐛 Bug Fixes
- **None** - Ini adalah feature baru, bukan bug fix

### 🔧 Maintenance
- **Dependencies**: Tidak ada dependency baru
- **Configuration**: Tidak perlu perubahan config
- **Environment**: Tidak perlu perubahan .env

---

## [1.0.0] - 2026-04-11

### 🎉 Initial Release

#### Core Features
- **Auto-Calculated Price Index**: Perhitungan otomatis min/max/avg dari transaksi approved
- **IQR Outlier Detection**: Filter outlier menggunakan Interquartile Range
- **Real-Time Anomaly Detection**: Deteksi harga tidak wajar saat input
- **Smart Autocomplete**: Master Item Catalog dengan fuzzy matching
- **Manual Override**: Owner bisa set harga referensi manual
- **Analytics Dashboard**: Dashboard tren harga dan anomali
- **CSV Export**: Export data price index ke CSV

#### Database
- **Table**: `price_indexes` - Menyimpan referensi harga
- **Table**: `price_anomalies` - Log anomali harga
- **Table**: `master_items` - Catalog barang standar

#### API Endpoints
- `GET /price-index` - List price index
- `GET /api/price-index/lookup` - Lookup harga referensi
- `POST /api/price-index/check` - Real-time price check
- `POST /price-index/set-reference/{transaction}` - Set harga sebagai referensi
- `GET /price-index/analytics` - Analytics dashboard
- `GET /price-index/analytics/export` - Export CSV

#### Commands
- `php artisan price-index:recalculate` - Recalculate price index
- `php artisan items:populate` - Populate master items

#### Documentation
- `PRICE_INDEX_DOCS.md` - Dokumentasi lengkap
- `PRICE_INDEX_SYSTEM_README.md` - System overview
- `Price_index.md` - Arsitektur sistem

---

## Version History

| Version | Date | Description |
|---------|------|-------------|
| 2.0.0 | 2026-05-04 | Dual-Mode AVG System (Auto + Manual) |
| 1.0.0 | 2026-04-11 | Initial Release |

---

## Upgrade Guide

### From 1.0.0 to 2.0.0

#### Step 1: Backup Database
```bash
mysqldump -u root -p admin-payment > backup_before_2.0.0.sql
```

#### Step 2: Pull Changes
```bash
git pull origin main
```

#### Step 3: Install Dependencies (if any)
```bash
composer install
npm install
```

#### Step 4: Run Migration
```bash
php artisan migrate
```

#### Step 5: Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

#### Step 6: Restart Services
```bash
php artisan queue:restart
# Jika menggunakan Docker:
docker-compose restart app horizon reverb
```

#### Step 7: Verify
```bash
# Cek kolom baru
php artisan tinker
>>> \App\Models\PriceIndex::first()->avg_price_manual
>>> exit

# Cek route baru
php artisan route:list | grep price-index
```

#### Step 8: Test
- [ ] Approve transaksi baru → avg_price update
- [ ] Set avg_price_manual via API
- [ ] Cek effective_avg di lookup API
- [ ] Reset avg_price_manual ke NULL

---

## Rollback Guide

### From 2.0.0 to 1.0.0

⚠️ **Warning**: Rollback akan menghapus kolom `avg_price_manual` dan semua data manual override!

#### Step 1: Backup Data Manual
```sql
-- Backup data manual sebelum rollback
SELECT id, item_name, avg_price_manual, manual_reason
FROM price_indexes
WHERE avg_price_manual IS NOT NULL
INTO OUTFILE '/tmp/avg_manual_backup.csv';
```

#### Step 2: Rollback Migration
```bash
php artisan migrate:rollback --step=1
```

#### Step 3: Rollback Code
```bash
git checkout v1.0.0
composer install
```

#### Step 4: Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

#### Step 5: Restart Services
```bash
php artisan queue:restart
docker-compose restart app horizon reverb
```

---

## Support

Untuk pertanyaan atau issue terkait Price Index System:
1. Cek dokumentasi lengkap di folder root project
2. Review changelog ini untuk memahami perubahan
3. Hubungi tim development

---

**Maintained by:** WHUSNET Development Team  
**Last Updated:** 4 Mei 2026
