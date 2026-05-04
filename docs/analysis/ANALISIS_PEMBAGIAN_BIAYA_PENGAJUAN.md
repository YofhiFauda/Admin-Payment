# Analisis Pembagian Biaya Pengajuan & Sumber Dana

## 📋 Ringkasan Eksekutif

Berdasarkan analisis mendalam terhadap kode sistem, ditemukan **MASALAH KRITIS** pada logika Sumber Dana di Upload Pembayaran Invoice Pengajuan. Sistem saat ini **TIDAK mengikuti** metode pembagian biaya yang dipilih di Form Pengajuan, melainkan **selalu melakukan "Bagi Rata"** secara otomatis.

---

## 🔍 Temuan Utama

### ✅ **SUDAH BENAR** - Form Pengajuan & Edit Pengajuan

#### 1. Form Pengajuan (`form-pengajuan.blade.php`)
- ✅ Memiliki 3 metode pembagian: **Bagi Rata**, **Persentase**, **Manual**
- ✅ Data disimpan dengan benar ke database via `PengajuanController::store()`
- ✅ Menyimpan `allocation_percent` dan `allocation_amount` ke tabel `transaction_branches`

**Contoh Data Tersimpan:**
```
OLT JETIS:     40% → Rp 80.000
OLT SIMAN:     10% → Rp 20.000
OLT SLAHUNG:   25% → Rp 50.000
OLT SUMBEREJO: 25% → Rp 50.000
Total: 100% → Rp 200.000
```

#### 2. Edit Pengajuan (`edit-pengajuan.blade.php`)
- ✅ Membaca data pembagian dari database dengan benar
- ✅ Menampilkan metode yang sama dengan Form Pengajuan
- ✅ Pre-fill data cabang dan alokasi sesuai data asli

**Kode Pre-fill (Baris 470-471):**
```blade
data-preset-percent="{{ $transaction->branches->find($branch->id)->pivot->allocation_percent ?? 0 }}"
data-preset-amount="{{ $transaction->branches->find($branch->id)->pivot->allocation_amount ?? 0 }}"
```

---

### ❌ **MASALAH KRITIS** - Upload Pembayaran Invoice

#### Lokasi Bug: `payment.js` → Fungsi `renderPaymentModalDetails()`

**Baris 618-636:**
```javascript
if (d.type === 'pengajuan') {
    const container = document.getElementById('p_sumber_dana_container');
    if (container) {
        container.innerHTML = '';
        d.branches_raw.forEach((b, idx) => {
            const html = `
                <input type="checkbox" id="sd_check_${b.id}" 
                    class="sd-checkbox peer sr-only" 
                    value="${b.id}" 
                    data-alloc="${b.allocation_amount}"      // ✅ Data benar dari DB
                    data-percent="${b.allocation_percent}"   // ✅ Data benar dari DB
                    data-name="${b.name}">
                ...
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
    }
}
```

**Masalah terjadi di fungsi `calculateSumberDanaTotal()` - Baris 730-735:**
```javascript
document.querySelectorAll('.sd-checkbox').forEach(cb => {
    const id = cb.value;
    const name = cb.dataset.name;
    const percent = parseFloat(cb.dataset.percent);  // ✅ Baca dari data asli

    // ❌ BUG: Menghitung ulang dengan BAGI RATA berdasarkan finalTotalTarget
    const alloc = Math.round((finalTotalTarget * percent) / 100);
    
    // ❌ Ini mengabaikan allocation_amount asli dari Form Pengajuan
    // ❌ Selalu recalculate berdasarkan total baru
});
```

---

## 🎯 Skenario Masalah

### **Skenario yang Diharapkan User:**

**Form Pengajuan (Manual):**
```
Total Pengajuan: Rp 200.000
- OLT JETIS:     40% → Rp 80.000
- OLT SIMAN:     10% → Rp 20.000
- OLT SLAHUNG:   25% → Rp 50.000
- OLT SUMBEREJO: 25% → Rp 50.000
```

**Upload Pembayaran Invoice:**
```
Total Invoice: Rp 200.000 (sama dengan pengajuan)

Sumber Dana yang SEHARUSNYA muncul:
- OLT JETIS:     Rp 80.000 (40%)
- OLT SIMAN:     Rp 20.000 (10%)
- OLT SLAHUNG:   Rp 50.000 (25%)
- OLT SUMBEREJO: Rp 50.000 (25%)
```

### **Skenario yang TERJADI Saat Ini:**

**Upload Pembayaran Invoice:**
```
Total Invoice: Rp 200.000

❌ Sistem RECALCULATE dengan persentase asli:
- OLT JETIS:     Rp 80.000 (40% dari 200.000) ✅ Kebetulan sama
- OLT SIMAN:     Rp 20.000 (10% dari 200.000) ✅ Kebetulan sama
- OLT SLAHUNG:   Rp 50.000 (25% dari 200.000) ✅ Kebetulan sama
- OLT SUMBEREJO: Rp 50.000 (25% dari 200.000) ✅ Kebetulan sama
```

**Terlihat benar karena total sama!** Tapi...

### **Masalah Muncul Ketika Total Berubah:**

**Jika Invoice Aktual = Rp 220.000 (ada ongkir Rp 20.000):**
```
❌ Sistem RECALCULATE:
- OLT JETIS:     Rp 88.000 (40% dari 220.000) ← SALAH!
- OLT SIMAN:     Rp 22.000 (10% dari 220.000) ← SALAH!
- OLT SLAHUNG:   Rp 55.000 (25% dari 220.000) ← SALAH!
- OLT SUMBEREJO: Rp 55.000 (25% dari 220.000) ← SALAH!

✅ Yang BENAR seharusnya:
- OLT JETIS:     Rp 80.000 (tetap sesuai pengajuan)
- OLT SIMAN:     Rp 20.000 (tetap sesuai pengajuan)
- OLT SLAHUNG:   Rp 50.000 (tetap sesuai pengajuan)
- OLT SUMBEREJO: Rp 50.000 (tetap sesuai pengajuan)
- Ongkir Rp 20.000 → Dibagi rata atau metode lain
```

---

## 🔧 Akar Masalah

### 1. **Logika Recalculation yang Salah**

File: `resources/js/transactions/payment.js` (Baris 730-735)

```javascript
// ❌ MASALAH: Selalu recalculate berdasarkan finalTotalTarget
const alloc = Math.round((finalTotalTarget * percent) / 100);
```

**Seharusnya:**
```javascript
// ✅ SOLUSI: Gunakan allocation_amount asli dari database
const alloc = parseInt(cb.dataset.alloc); // Sudah tersimpan dari Form Pengajuan
```

### 2. **Tidak Ada Pembedaan Metode Distribusi**

Sistem tidak menyimpan informasi **metode distribusi** yang dipilih di Form Pengajuan:
- Tidak ada field `distribution_method` di tabel `transactions`
- Tidak ada cara untuk tahu apakah user memilih "Manual", "Persentase", atau "Bagi Rata"

### 3. **Biaya Tambahan (Ongkir, PPN, dll) Tidak Terdistribusi**

Ketika ada biaya tambahan di invoice:
- Ongkir: Rp 20.000
- PPN: Rp 10.000
- Biaya Layanan: Rp 5.000

Sistem tidak punya logika untuk mendistribusikan biaya tambahan ini ke cabang-cabang.

---

## 💡 Solusi yang Direkomendasikan

### **Solusi 1: Gunakan Allocation Amount Asli (Quick Fix)**

**File:** `resources/js/transactions/payment.js`

**Ubah Baris 730-735:**
```javascript
// SEBELUM (SALAH):
const alloc = Math.round((finalTotalTarget * percent) / 100);

// SESUDAH (BENAR):
const allocFromDB = parseInt(cb.dataset.alloc); // Dari Form Pengajuan
const alloc = allocFromDB; // Gunakan nilai asli, jangan recalculate
```

**Ubah Baris 738-740 (Update Label):**
```javascript
// SEBELUM:
if (labelEl) {
    labelEl.textContent = `Alokasi: Rp ${alloc.toLocaleString('id-ID')} (${percent}%)`;
}

// SESUDAH:
if (labelEl) {
    labelEl.textContent = `Alokasi: Rp ${allocFromDB.toLocaleString('id-ID')} (${percent}%)`;
}
```

**Hasil:**
- ✅ Sumber Dana akan menampilkan nilai PERSIS seperti di Form Pengajuan
- ✅ Tidak ada recalculation otomatis
- ✅ User harus manual adjust jika ada biaya tambahan

---

### **Solusi 2: Distribusi Biaya Tambahan (Recommended)**

**Tambahkan Logika Distribusi Biaya Tambahan:**

```javascript
function calculateSumberDanaTotal(baseTotal) {
    // ... kode existing ...
    
    // Hitung biaya tambahan
    const ongkir = unformatNumber(document.getElementById('p_ongkir')?.value || "0");
    const diskon = unformatNumber(document.getElementById('p_diskon_pengiriman')?.value || "0");
    const voucher = unformatNumber(document.getElementById('p_voucher_diskon')?.value || "0");
    const dppLainnya = unformatNumber(document.getElementById('p_dpp_lainnya')?.value || "0");
    const taxAmt = unformatNumber(document.getElementById('p_tax_amount')?.value || "0");
    const layanan1 = unformatNumber(document.getElementById('p_biaya_layanan_1')?.value || "0");
    const layanan2 = unformatNumber(document.getElementById('p_biaya_layanan_2')?.value || "0");

    // Total biaya tambahan
    const additionalCosts = ongkir + dppLainnya + taxAmt + layanan1 + layanan2 - diskon - voucher;
    
    document.querySelectorAll('.sd-checkbox').forEach(cb => {
        const id = cb.value;
        const name = cb.dataset.name;
        const percent = parseFloat(cb.dataset.percent);
        
        // ✅ Gunakan allocation_amount asli dari Form Pengajuan
        const allocFromDB = parseInt(cb.dataset.alloc);
        
        // ✅ Distribusikan biaya tambahan berdasarkan persentase
        const additionalShare = Math.round((additionalCosts * percent) / 100);
        
        // ✅ Total alokasi = Alokasi asli + Bagian biaya tambahan
        const alloc = allocFromDB + additionalShare;
        
        // Update label dengan breakdown
        const statusEl = document.getElementById('sd_status_' + id);
        const labelEl = document.querySelector(`label[for="sd_check_${id}"] div.text-slate-400`);
        
        if (labelEl) {
            if (additionalCosts !== 0) {
                labelEl.innerHTML = `
                    Alokasi Pengajuan: Rp ${allocFromDB.toLocaleString('id-ID')} (${percent}%)<br>
                    <span class="text-[9px] text-teal-500">+ Biaya Tambahan: Rp ${additionalShare.toLocaleString('id-ID')}</span><br>
                    <span class="text-[9px] font-black text-slate-600">Total: Rp ${alloc.toLocaleString('id-ID')}</span>
                `;
            } else {
                labelEl.textContent = `Alokasi: Rp ${allocFromDB.toLocaleString('id-ID')} (${percent}%)`;
            }
        }
        
        branches[id] = { id, name, alloc };
        
        // ... sisa kode untuk debt calculation ...
    });
}
```

**Hasil:**
- ✅ Alokasi asli dari Form Pengajuan tetap dipertahankan
- ✅ Biaya tambahan didistribusikan secara proporsional
- ✅ User bisa lihat breakdown: Alokasi Asli + Biaya Tambahan
- ✅ Total tetap balance dengan invoice

---

### **Solusi 3: Simpan Metode Distribusi (Long-term)**

**1. Tambah Field di Database:**

```php
// Migration: add_distribution_method_to_transactions
Schema::table('transactions', function (Blueprint $table) {
    $table->enum('distribution_method', ['equal', 'percent', 'manual'])
          ->default('equal')
          ->after('branches');
});
```

**2. Simpan Metode di Controller:**

```php
// PengajuanController::store()
$transaction->distribution_method = $request->input('distribution_method', 'equal');
```

**3. Gunakan Metode di Payment Modal:**

```javascript
// Baca metode dari transaction data
const distributionMethod = d.distribution_method || 'equal';

if (distributionMethod === 'manual') {
    // Gunakan allocation_amount asli, jangan recalculate
    const alloc = parseInt(cb.dataset.alloc);
} else if (distributionMethod === 'percent') {
    // Recalculate berdasarkan persentase (behavior saat ini)
    const alloc = Math.round((finalTotalTarget * percent) / 100);
} else {
    // Bagi rata
    const alloc = Math.round(finalTotalTarget / totalBranches);
}
```

---

## 📊 Skenario Testing

### **Skenario 1: Semua Cabang Bayar Sesuai Alokasi**

**Form Pengajuan (Manual):**
```
Total: Rp 200.000
- OLT JETIS:     Rp 80.000 (40%)
- OLT SIMAN:     Rp 20.000 (10%)
- OLT SLAHUNG:   Rp 50.000 (25%)
- OLT SUMBEREJO: Rp 50.000 (25%)
```

**Upload Pembayaran:**
```
Invoice Total: Rp 200.000
Sumber Dana:
✅ OLT JETIS:     Rp 80.000 (checked)
✅ OLT SIMAN:     Rp 20.000 (checked)
✅ OLT SLAHUNG:   Rp 50.000 (checked)
✅ OLT SUMBEREJO: Rp 50.000 (checked)

Total Sumber Dana: Rp 200.000
Status: ✅ Tidak ada warning
Hutang: Tidak ada
```

---

### **Skenario 2: Sebagian Cabang Bayar, Sebagian Berhutang**

**Form Pengajuan (Manual):**
```
Total: Rp 200.000
- OLT JETIS:     Rp 80.000 (40%)
- OLT SIMAN:     Rp 20.000 (10%)
- OLT SLAHUNG:   Rp 50.000 (25%)
- OLT SUMBEREJO: Rp 50.000 (25%)
```

**Upload Pembayaran:**
```
Invoice Total: Rp 200.000
Sumber Dana:
✅ OLT JETIS:     Rp 130.000 (checked) → Lebih bayar Rp 50.000
✅ OLT SIMAN:     Rp 70.000 (checked)  → Lebih bayar Rp 50.000
❌ OLT SLAHUNG:   (unchecked)          → Berhutang Rp 50.000
❌ OLT SUMBEREJO: (unchecked)          → Berhutang Rp 50.000

Total Sumber Dana: Rp 200.000
Status: ✅ Tidak ada warning

Preview Hutang Otomatis:
┌─────────────────────────────────────┐
│ OLT SLAHUNG                         │
│ Total beban hutang: Rp 50.000       │
│ Rincian Pembayaran Ke:              │
│   → OLT JETIS:     Rp 25.000        │
│   → OLT SIMAN:     Rp 25.000        │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ OLT SUMBEREJO                       │
│ Total beban hutang: Rp 50.000       │
│ Rincian Pembayaran Ke:              │
│   → OLT JETIS:     Rp 25.000        │
│   → OLT SIMAN:     Rp 25.000        │
└─────────────────────────────────────┘
```

**Catatan:** Logika distribusi hutang sudah benar di kode existing (Baris 820-860).

---

## 🚀 Implementasi Rekomendasi

### **Priority 1: Quick Fix (Solusi 1)**

**File yang Diubah:**
- `resources/js/transactions/payment.js` (2 baris)

**Estimasi Waktu:** 5 menit

**Testing:**
1. Buat Pengajuan dengan metode "Manual"
2. Set alokasi tidak rata (40%, 10%, 25%, 25%)
3. Approve pengajuan
4. Upload Invoice dengan total yang sama
5. Verifikasi Sumber Dana menampilkan nilai yang sama dengan Form Pengajuan

---

### **Priority 2: Distribusi Biaya Tambahan (Solusi 2)**

**File yang Diubah:**
- `resources/js/transactions/payment.js` (fungsi `calculateSumberDanaTotal`)

**Estimasi Waktu:** 30 menit

**Testing:**
1. Buat Pengajuan Rp 200.000 dengan alokasi manual
2. Upload Invoice dengan:
   - Subtotal: Rp 200.000
   - Ongkir: Rp 20.000
   - PPN: Rp 10.000
   - Total: Rp 230.000
3. Verifikasi:
   - Alokasi asli tetap Rp 80.000, Rp 20.000, Rp 50.000, Rp 50.000
   - Biaya tambahan Rp 30.000 terdistribusi: Rp 12.000, Rp 3.000, Rp 7.500, Rp 7.500
   - Total per cabang: Rp 92.000, Rp 23.000, Rp 57.500, Rp 57.500

---

### **Priority 3: Simpan Metode Distribusi (Solusi 3)**

**File yang Diubah:**
- Migration baru
- `app/Models/Transaction.php`
- `app/Http/Controllers/PengajuanController.php`
- `resources/js/transactions/shared/distribution.js`
- `resources/js/transactions/payment.js`

**Estimasi Waktu:** 2 jam

**Testing:** Full regression testing semua flow Pengajuan

---

## 📝 Kesimpulan

### **Masalah Utama:**
❌ Sistem **TIDAK mengikuti** metode pembagian biaya dari Form Pengajuan
❌ Selalu **recalculate** berdasarkan total invoice baru
❌ Tidak ada mekanisme distribusi biaya tambahan (ongkir, PPN, dll)

### **Solusi Tercepat:**
✅ Gunakan `allocation_amount` asli dari database (2 baris kode)
✅ Jangan recalculate berdasarkan total baru
✅ Biarkan user manual adjust jika ada biaya tambahan

### **Solusi Ideal:**
✅ Pertahankan alokasi asli dari Form Pengajuan
✅ Distribusikan biaya tambahan secara proporsional
✅ Tampilkan breakdown yang jelas ke user
✅ Simpan metode distribusi untuk referensi

---

## 🔗 File Terkait

1. **Form Pengajuan:**
   - `resources/views/transactions/form-pengajuan.blade.php`
   - `resources/js/transactions/shared/distribution.js`
   - `app/Http/Controllers/PengajuanController.php`

2. **Edit Pengajuan:**
   - `resources/views/transactions/edit-pengajuan.blade.php`

3. **Upload Pembayaran:**
   - `resources/views/transactions/partials/modals/payment-upload-modal.blade.php`
   - `resources/js/transactions/payment.js` (Baris 618-860)

4. **Database:**
   - `database/migrations/2026_02_14_000003_create_transaction_branches_table.php`
   - Tabel: `transaction_branches` (pivot table)
   - Fields: `allocation_percent`, `allocation_amount`

---

**Dibuat:** {{ date('Y-m-d H:i:s') }}
**Versi:** 1.0
**Status:** Ready for Implementation
