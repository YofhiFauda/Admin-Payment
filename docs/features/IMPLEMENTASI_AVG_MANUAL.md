# Implementasi Harga AVG Manual untuk Price Index

## 📋 Ringkasan Perubahan

Sistem Price Index sekarang mendukung **dua jenis harga AVG**:

1. **`avg_price`** (Otomatis) - Dihitung otomatis dari transaksi yang masuk dalam range [Min, Max]
2. **`avg_price_manual`** (Manual Override) - Nilai manual yang diset oleh Owner/Atasan

### Logika Prioritas:
```
Jika avg_price_manual IS NOT NULL → Gunakan avg_price_manual
Jika avg_price_manual IS NULL     → Gunakan avg_price (otomatis)
```

---

## 🗄️ Perubahan Database

### Migration Baru
File: `database/migrations/2026_05_04_100000_add_avg_price_manual_to_price_indexes.php`

```sql
ALTER TABLE price_indexes 
ADD COLUMN avg_price_manual DECIMAL(15,2) NULL 
COMMENT 'Harga rata-rata manual (override). Jika NULL, gunakan avg_price otomatis';
```

**Cara Menjalankan:**
```bash
php artisan migrate
```

---

## 📦 Perubahan Model

### File: `app/Models/PriceIndex.php`

#### Tambahan Fillable:
```php
'avg_price_manual',  // Kolom baru
```

#### Tambahan Cast:
```php
'avg_price_manual' => 'float',
```

#### Method Baru:

1. **`getEffectiveAvgPrice()`** - Mendapatkan harga AVG yang efektif
```php
public function getEffectiveAvgPrice(): float
{
    return $this->avg_price_manual ?? $this->avg_price;
}
```

2. **`isAvgManual()`** - Cek apakah menggunakan AVG manual
```php
public function isAvgManual(): bool
{
    return $this->avg_price_manual !== null;
}
```

3. **Update `getSourceLabelAttribute()`**
```php
public function getSourceLabelAttribute(): string
{
    if ($this->isAvgManual()) {
        return 'Manual (AVG)';
    }
    return $this->is_manual ? 'Manual' : 'Auto';
}
```

4. **Update `getFormattedAvgAttribute()`**
```php
public function getFormattedAvgAttribute(): string
{
    $effectiveAvg = $this->getEffectiveAvgPrice();
    return 'Rp ' . number_format($effectiveAvg, 0, ',', '.');
}
```

---

## 🔧 Perubahan Service

### File: `app/Services/PriceIndex/PriceIndexService.php`

#### Method: `processApprovedItem()`

**Perubahan Utama:**
- `avg_price` SELALU dihitung otomatis dari transaksi dalam range [Min, Max]
- `avg_price_manual` TIDAK PERNAH disentuh oleh proses otomatis
- Menggunakan Incremental Moving Average untuk efisiensi

```php
// ✅ Update avg_price (otomatis), JANGAN sentuh avg_price_manual
$pi->update([
    'avg_price'          => round($newAvg, 2),
    'total_transactions' => $newTotal,
    'last_calculated_at' => now(),
    // avg_price_manual tetap tidak berubah
]);
```

#### Method: `recalculateFromHistory()`

**Perubahan:**
- Saat create baru: `'avg_price_manual' => null`
- Saat update: TIDAK menyentuh `avg_price_manual`

---

## 🎮 Perubahan Controller

### File: `app/Http/Controllers/PriceIndexController.php`

#### Method Baru: `updateAvgManual()`

Endpoint khusus untuk set/update/hapus AVG manual:

```php
POST /price-index/{id}/update-avg-manual

Parameters:
- avg_price_manual: nullable|numeric|min:0
- manual_reason: nullable|string|max:500

Response:
{
    "success": true,
    "message": "Harga AVG Manual berhasil diset.",
    "data": {
        "avg_price": 50000,        // Otomatis
        "avg_price_manual": 55000, // Manual
        "effective_avg": 55000     // Yang digunakan
    }
}
```

**Cara Menghapus AVG Manual:**
Kirim `avg_price_manual` sebagai `null` atau kosong.

#### Update Method: `lookup()`

Sekarang mengembalikan informasi lengkap:
```json
{
    "found": true,
    "avg_price": 50000,        // Effective (prioritas manual)
    "avg_price_auto": 48000,   // Nilai otomatis
    "avg_price_manual": 55000, // Nilai manual (bisa null)
    "is_avg_manual": true
}
```

#### Update Method: `check()`

Sama seperti `lookup()`, mengembalikan informasi lengkap tentang AVG.

---

## 🛣️ Perubahan Routes

### File: `routes/web.php`

Tambahan route baru:
```php
Route::post('/price-index/{id}/update-avg-manual', 
    [PriceIndexController::class, 'updateAvgManual'])
    ->name('price-index.update-avg-manual');
```

---

## 🎨 Perubahan UI (Rekomendasi)

### View yang Perlu Diupdate:

1. **`resources/views/price-index/index.blade.php`**
   - Tambah kolom/badge untuk menunjukkan AVG Manual vs Auto
   - Tambah tombol "Set AVG Manual" di modal edit
   - Tampilkan kedua nilai (auto dan manual) jika ada

2. **Modal Edit - Tambahan Field:**
```html
<div>
    <label>Harga AVG (Otomatis)</label>
    <input type="text" readonly value="{{ $pi->avg_price }}" />
    <small>Dihitung dari {{ $pi->total_transactions }} transaksi</small>
</div>

<div>
    <label>Harga AVG Manual (Override)</label>
    <input type="number" id="avg_price_manual" 
           value="{{ $pi->avg_price_manual }}" />
    <button onclick="clearAvgManual()">Reset ke Auto</button>
</div>

<div>
    <label>Harga AVG Efektif (Yang Digunakan)</label>
    <input type="text" readonly 
           value="{{ $pi->getEffectiveAvgPrice() }}" />
</div>
```

3. **JavaScript untuk Update AVG Manual:**
```javascript
function updateAvgManual(priceIndexId) {
    const avgManual = document.getElementById('avg_price_manual').value;
    const reason = document.getElementById('manual_reason').value;
    
    fetch(`/price-index/${priceIndexId}/update-avg-manual`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            avg_price_manual: avgManual || null,
            manual_reason: reason
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        }
    });
}

function clearAvgManual() {
    document.getElementById('avg_price_manual').value = '';
}
```

---

## 📊 Contoh Penggunaan

### Skenario 1: Item Baru (Cold Start)
```
Transaksi 1 approved: Harga = 50,000
→ avg_price = 50,000
→ avg_price_manual = NULL
→ Effective AVG = 50,000 (auto)
```

### Skenario 2: Transaksi Masuk dalam Range
```
Existing: avg_price = 50,000 (dari 10 transaksi)
Transaksi baru approved: Harga = 52,000 (dalam range [45k - 60k])

→ avg_price = ((50,000 × 10) + 52,000) / 11 = 50,182
→ avg_price_manual = NULL (tidak berubah)
→ Effective AVG = 50,182 (auto)
```

### Skenario 3: Owner Set AVG Manual
```
Owner set avg_price_manual = 55,000

→ avg_price = 50,182 (tetap dihitung otomatis)
→ avg_price_manual = 55,000 (manual override)
→ Effective AVG = 55,000 (manual)
```

### Skenario 4: Transaksi Baru Setelah Manual Override
```
Transaksi baru approved: Harga = 51,000

→ avg_price = ((50,182 × 11) + 51,000) / 12 = 50,265 (update otomatis)
→ avg_price_manual = 55,000 (TIDAK BERUBAH)
→ Effective AVG = 55,000 (tetap manual)
```

### Skenario 5: Reset AVG Manual
```
Owner hapus avg_price_manual (set NULL)

→ avg_price = 50,265 (tetap ada)
→ avg_price_manual = NULL
→ Effective AVG = 50,265 (kembali ke auto)
```

---

## ✅ Checklist Testing

- [ ] Migration berhasil dijalankan
- [ ] Kolom `avg_price_manual` ada di database
- [ ] Model method `getEffectiveAvgPrice()` berfungsi
- [ ] Model method `isAvgManual()` berfungsi
- [ ] Service `processApprovedItem()` tidak menyentuh `avg_price_manual`
- [ ] Controller `updateAvgManual()` bisa set nilai manual
- [ ] Controller `updateAvgManual()` bisa hapus nilai manual (set NULL)
- [ ] API `lookup()` mengembalikan data lengkap
- [ ] API `check()` mengembalikan data lengkap
- [ ] UI menampilkan perbedaan AVG Auto vs Manual
- [ ] UI bisa edit AVG Manual
- [ ] UI bisa reset AVG Manual ke Auto

---

## 🔍 Query Testing

### Cek Data Price Index
```sql
SELECT 
    id,
    item_name,
    avg_price as avg_auto,
    avg_price_manual as avg_manual,
    COALESCE(avg_price_manual, avg_price) as avg_effective,
    total_transactions,
    is_manual
FROM price_indexes
ORDER BY item_name;
```

### Set AVG Manual via SQL (Testing)
```sql
UPDATE price_indexes 
SET avg_price_manual = 55000,
    manual_set_by = 1,
    manual_set_at = NOW(),
    manual_reason = 'Testing AVG Manual'
WHERE id = 1;
```

### Reset AVG Manual via SQL
```sql
UPDATE price_indexes 
SET avg_price_manual = NULL
WHERE id = 1;
```

---

## 📝 Catatan Penting

1. **`avg_price` SELALU dihitung otomatis** dari transaksi yang masuk dalam range [Min, Max]
2. **`avg_price_manual` HANYA diubah manual** oleh Owner/Atasan melalui UI atau API
3. **Prioritas: Manual > Auto** - Jika ada manual, gunakan manual
4. **Transparansi**: Sistem menyimpan KEDUA nilai (auto dan manual) untuk audit trail
5. **Fleksibilitas**: Owner bisa kapan saja reset ke auto dengan menghapus nilai manual

---

## 🚀 Deployment

```bash
# 1. Pull perubahan
git pull

# 2. Jalankan migration
php artisan migrate

# 3. Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 4. Restart queue workers (jika ada)
php artisan queue:restart
```

---

## 📞 Support

Jika ada pertanyaan atau issue, silakan hubungi tim development.

**Dokumentasi dibuat:** 4 Mei 2026
**Versi:** 1.0
