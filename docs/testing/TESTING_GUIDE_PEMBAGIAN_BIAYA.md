# 🧪 Testing Guide: Perbaikan Pembagian Biaya Pengajuan

**Tanggal:** {{ date('Y-m-d H:i:s') }}  
**Status:** Ready for Testing

---

## 🎯 Tujuan Testing

Memastikan sistem **Upload Pembayaran Invoice** mengikuti pembagian biaya dari **Form Pengajuan** dan mendistribusikan biaya tambahan secara proporsional.

---

## 📋 Pre-requisites

1. ✅ Assets sudah di-compile (`npm run build`)
2. ✅ Browser cache sudah di-clear (Ctrl+Shift+R)
3. ✅ Login sebagai user dengan role yang bisa approve pengajuan
4. ✅ Minimal 4 cabang tersedia di sistem

---

## 🧪 Test Case 1: Baseline (Tanpa Biaya Tambahan)

### **Tujuan:**
Verifikasi sistem mempertahankan alokasi asli dari Form Pengajuan

### **Langkah:**

1. **Buat Pengajuan Baru:**
   - Masuk ke Form Pengajuan
   - Isi data barang: "Laptop Dell" - Rp 200.000
   - Pilih 4 cabang
   - Pilih metode: **"Manual"**
   - Set alokasi:
     ```
     OLT JETIS:     40% → Rp 80.000
     OLT SIMAN:     10% → Rp 20.000
     OLT SLAHUNG:   25% → Rp 50.000
     OLT SUMBEREJO: 25% → Rp 50.000
     ```
   - Submit pengajuan

2. **Approve Pengajuan:**
   - Login sebagai Owner/Atasan
   - Approve pengajuan tersebut
   - Status berubah menjadi "Menunggu Pembayaran"

3. **Upload Pembayaran Invoice:**
   - Klik tombol "Upload Pembayaran"
   - **JANGAN isi biaya tambahan** (ongkir, PPN, dll = 0)
   - Perhatikan bagian "Rincian Sumber Dana"

### **Expected Result:**

```
✅ Label menampilkan:
   OLT JETIS:     Alokasi: Rp 80.000 (40%)
   OLT SIMAN:     Alokasi: Rp 20.000 (10%)
   OLT SLAHUNG:   Alokasi: Rp 50.000 (25%)
   OLT SUMBEREJO: Alokasi: Rp 50.000 (25%)

✅ Centang semua cabang:
   - Auto-fill dengan nilai yang benar
   - Total Sumber Dana: Rp 200.000
   - Status: "Nominal sesuai dengan nilai bayar transaksi"
   - Tombol Submit: Enabled (biru)
```

### **Screenshot:**
📸 Ambil screenshot bagian "Rincian Sumber Dana"

---

## 🧪 Test Case 2: Dengan Ongkir Kecil (Rp 20.000)

### **Tujuan:**
Verifikasi distribusi biaya tambahan kecil secara proporsional

### **Langkah:**

1. Gunakan pengajuan yang sama dari Test Case 1
2. Upload Pembayaran Invoice
3. **Isi Ongkir: Rp 20.000**
4. Perhatikan perubahan label

### **Expected Result:**

```
✅ Label menampilkan breakdown:

OLT JETIS (40%):
   Alokasi Pengajuan: Rp 80.000 (40%)
   + Biaya Tambahan: Rp 8.000
   Total: Rp 88.000

OLT SIMAN (10%):
   Alokasi Pengajuan: Rp 20.000 (10%)
   + Biaya Tambahan: Rp 2.000
   Total: Rp 22.000

OLT SLAHUNG (25%):
   Alokasi Pengajuan: Rp 50.000 (25%)
   + Biaya Tambahan: Rp 5.000
   Total: Rp 55.000

OLT SUMBEREJO (25%):
   Alokasi Pengajuan: Rp 50.000 (25%)
   + Biaya Tambahan: Rp 5.000
   Total: Rp 55.000

✅ Centang semua cabang:
   - Auto-fill: Rp 88.000, Rp 22.000, Rp 55.000, Rp 55.000
   - Total Sumber Dana: Rp 220.000
   - Status: "Nominal sesuai dengan nilai bayar transaksi"
```

### **Screenshot:**
📸 Ambil screenshot breakdown biaya tambahan

---

## 🧪 Test Case 3: Dengan Ongkir Besar (Rp 200.000)

### **Tujuan:**
Verifikasi sistem tetap adil saat biaya tambahan sangat besar

### **Langkah:**

1. Gunakan pengajuan yang sama
2. Upload Pembayaran Invoice
3. **Isi Ongkir: Rp 200.000**
4. Perhatikan distribusi

### **Expected Result:**

```
✅ Label menampilkan:

OLT JETIS (40%):
   Alokasi Pengajuan: Rp 80.000 (40%)
   + Biaya Tambahan: Rp 80.000
   Total: Rp 160.000

OLT SIMAN (10%):
   Alokasi Pengajuan: Rp 20.000 (10%)
   + Biaya Tambahan: Rp 20.000
   Total: Rp 40.000

OLT SLAHUNG (25%):
   Alokasi Pengajuan: Rp 50.000 (25%)
   + Biaya Tambahan: Rp 50.000
   Total: Rp 100.000

OLT SUMBEREJO (25%):
   Alokasi Pengajuan: Rp 50.000 (25%)
   + Biaya Tambahan: Rp 50.000
   Total: Rp 100.000

✅ Total Invoice: Rp 400.000
✅ Total Sumber Dana: Rp 400.000 (jika semua dicentang)
✅ Distribusi proporsional: 40%, 10%, 25%, 25%
```

### **Verifikasi Matematika:**
```
Alokasi Asli:     Rp 200.000 (80k + 20k + 50k + 50k)
Ongkir:           Rp 200.000
Total:            Rp 400.000

Distribusi Ongkir:
- 40% × 200k = Rp 80.000 → OLT JETIS
- 10% × 200k = Rp 20.000 → OLT SIMAN
- 25% × 200k = Rp 50.000 → OLT SLAHUNG
- 25% × 200k = Rp 50.000 → OLT SUMBEREJO
```

### **Screenshot:**
📸 Ambil screenshot dengan ongkir Rp 200.000

---

## 🧪 Test Case 4: Dengan Multiple Biaya Tambahan

### **Tujuan:**
Verifikasi sistem menangani kombinasi biaya tambahan

### **Langkah:**

1. Gunakan pengajuan yang sama
2. Upload Pembayaran Invoice
3. Isi:
   ```
   Ongkir:          Rp 50.000
   DPP Lainnya:     Rp 30.000
   PPN:             Rp 20.000
   Biaya Layanan 1: Rp 10.000
   ─────────────────────────────
   Total Tambahan:  Rp 110.000
   ```

### **Expected Result:**

```
✅ Total Biaya Tambahan: Rp 110.000

Distribusi:
OLT JETIS (40%):
   Alokasi Pengajuan: Rp 80.000
   + Biaya Tambahan: Rp 44.000 (40% × 110k)
   Total: Rp 124.000

OLT SIMAN (10%):
   Alokasi Pengajuan: Rp 20.000
   + Biaya Tambahan: Rp 11.000 (10% × 110k)
   Total: Rp 31.000

OLT SLAHUNG (25%):
   Alokasi Pengajuan: Rp 50.000
   + Biaya Tambahan: Rp 27.500 (25% × 110k)
   Total: Rp 77.500

OLT SUMBEREJO (25%):
   Alokasi Pengajuan: Rp 50.000
   + Biaya Tambahan: Rp 27.500 (25% × 110k)
   Total: Rp 77.500

✅ Total Invoice: Rp 310.000
✅ Total Sumber Dana: Rp 310.000
```

---

## 🧪 Test Case 5: Dengan Diskon/Potongan

### **Tujuan:**
Verifikasi sistem menangani potongan (nilai negatif)

### **Langkah:**

1. Gunakan pengajuan yang sama
2. Upload Pembayaran Invoice
3. Isi:
   ```
   Ongkir:             Rp 50.000
   Diskon Pengiriman:  Rp 30.000
   ─────────────────────────────
   Total Tambahan:     Rp 20.000
   ```

### **Expected Result:**

```
✅ Label menampilkan:

OLT JETIS (40%):
   Alokasi Pengajuan: Rp 80.000 (40%)
   + Biaya Tambahan: Rp 8.000
   Total: Rp 88.000

(Atau jika diskon lebih besar dari ongkir:)

Ongkir:             Rp 20.000
Diskon Pengiriman:  Rp 50.000
─────────────────────────────
Total Tambahan:     -Rp 30.000

OLT JETIS (40%):
   Alokasi Pengajuan: Rp 80.000 (40%)
   - Potongan: Rp 12.000
   Total: Rp 68.000
```

---

## 🧪 Test Case 6: Sebagian Bayar, Sebagian Hutang

### **Tujuan:**
Verifikasi preview hutang tetap akurat

### **Langkah:**

1. Gunakan pengajuan dengan ongkir Rp 200.000 (total Rp 400.000)
2. Upload Pembayaran Invoice
3. **Hanya centang 2 cabang:**
   - ✅ OLT JETIS: Ubah menjadi Rp 200.000 (lebih bayar Rp 40.000)
   - ✅ OLT SIMAN: Ubah menjadi Rp 200.000 (lebih bayar Rp 160.000)
   - ❌ OLT SLAHUNG: Tidak dicentang (berhutang Rp 100.000)
   - ❌ OLT SUMBEREJO: Tidak dicentang (berhutang Rp 100.000)

### **Expected Result:**

```
✅ Total Sumber Dana: Rp 400.000
✅ Status: "Nominal sesuai dengan nilai bayar transaksi"

✅ Preview Hutang Otomatis muncul:

┌─────────────────────────────────────┐
│ OLT SLAHUNG                         │
│ Total beban hutang: Rp 100.000      │
│ Rincian Pembayaran Ke:              │
│   → OLT JETIS: Rp 20.000            │
│   → OLT SIMAN: Rp 80.000            │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ OLT SUMBEREJO                       │
│ Total beban hutang: Rp 100.000      │
│ Rincian Pembayaran Ke:              │
│   → OLT JETIS: Rp 20.000            │
│   → OLT SIMAN: Rp 80.000            │
└─────────────────────────────────────┘

Proporsi hutang:
- OLT JETIS lebih bayar: Rp 40.000 (20% dari total excess)
- OLT SIMAN lebih bayar: Rp 160.000 (80% dari total excess)

Distribusi ke OLT SLAHUNG:
- 20% × 100k = Rp 20.000 ke OLT JETIS
- 80% × 100k = Rp 80.000 ke OLT SIMAN
```

### **Screenshot:**
📸 Ambil screenshot Preview Hutang

---

## 🧪 Test Case 7: Dynamic Update (Edit Biaya Tambahan)

### **Tujuan:**
Verifikasi label update otomatis saat biaya tambahan diubah

### **Langkah:**

1. Upload Pembayaran Invoice
2. Centang semua cabang (auto-fill dengan nilai awal)
3. **Ubah Ongkir dari Rp 0 → Rp 50.000**
4. Perhatikan perubahan label
5. **Ubah lagi Ongkir dari Rp 50.000 → Rp 100.000**
6. Perhatikan perubahan label lagi

### **Expected Result:**

```
✅ Saat ongkir Rp 0:
   Label: "Alokasi: Rp 80.000 (40%)"
   Input: Rp 80.000

✅ Saat ongkir diubah ke Rp 50.000:
   Label update otomatis:
      Alokasi Pengajuan: Rp 80.000 (40%)
      + Biaya Tambahan: Rp 20.000
      Total: Rp 100.000
   Input: TETAP Rp 80.000 (tidak auto-update)
   Warning: "Kurang Rp 50.000 dari Total Tagihan"

✅ Uncheck dan check lagi:
   Input auto-fill dengan nilai baru: Rp 100.000

✅ Saat ongkir diubah ke Rp 100.000:
   Label update lagi:
      Alokasi Pengajuan: Rp 80.000 (40%)
      + Biaya Tambahan: Rp 40.000
      Total: Rp 120.000
```

### **Catatan:**
- Label update **real-time** saat biaya tambahan diubah
- Input value **TIDAK** update otomatis (user harus manual adjust)
- Ini mencegah data hilang saat user sedang mengetik

---

## 🧪 Test Case 8: Metode Pembagian "Bagi Rata"

### **Tujuan:**
Verifikasi sistem juga bekerja untuk metode "Bagi Rata"

### **Langkah:**

1. Buat Pengajuan Baru dengan metode **"Bagi Rata"**
   - Total: Rp 200.000
   - 4 cabang dipilih
   - Sistem auto-calculate: Rp 50.000 per cabang (25% each)

2. Upload Invoice dengan ongkir Rp 100.000

### **Expected Result:**

```
✅ Setiap cabang:
   Alokasi Pengajuan: Rp 50.000 (25%)
   + Biaya Tambahan: Rp 25.000 (25% × 100k)
   Total: Rp 75.000

✅ Total: Rp 300.000 (4 × 75k)
```

---

## 🧪 Test Case 9: Metode Pembagian "Persentase"

### **Tujuan:**
Verifikasi sistem juga bekerja untuk metode "Persentase"

### **Langkah:**

1. Buat Pengajuan Baru dengan metode **"Persentase"**
   - Total: Rp 200.000
   - Set persentase custom:
     ```
     OLT JETIS:     50% → Rp 100.000
     OLT SIMAN:     30% → Rp 60.000
     OLT SLAHUNG:   20% → Rp 40.000
     ```

2. Upload Invoice dengan ongkir Rp 50.000

### **Expected Result:**

```
✅ Distribusi:
   OLT JETIS:     Rp 100.000 + Rp 25.000 = Rp 125.000
   OLT SIMAN:     Rp 60.000 + Rp 15.000 = Rp 75.000
   OLT SLAHUNG:   Rp 40.000 + Rp 10.000 = Rp 50.000

✅ Total: Rp 250.000
```

---

## 🧪 Test Case 10: Submit & Verifikasi Database

### **Tujuan:**
Verifikasi data tersimpan dengan benar di database

### **Langkah:**

1. Lakukan Test Case 3 (ongkir Rp 200.000)
2. Centang semua cabang
3. Upload foto invoice
4. Submit form
5. Cek database

### **Expected Result:**

```sql
-- Tabel: transactions
SELECT 
    invoice_number,
    amount,                    -- Rp 200.000 (dari pengajuan)
    ongkir,                    -- Rp 200.000
    sumber_dana_data           -- JSON array
FROM transactions 
WHERE invoice_number = 'INV-xxx';

-- Expected sumber_dana_data:
[
    {"branch_id": 1, "amount": 160000},  -- OLT JETIS
    {"branch_id": 2, "amount": 40000},   -- OLT SIMAN
    {"branch_id": 3, "amount": 100000},  -- OLT SLAHUNG
    {"branch_id": 4, "amount": 100000}   -- OLT SUMBEREJO
]

-- Tabel: transaction_branches (tetap menyimpan alokasi asli)
SELECT 
    branch_id,
    allocation_percent,        -- 40, 10, 25, 25
    allocation_amount          -- 80000, 20000, 50000, 50000
FROM transaction_branches 
WHERE transaction_id = xxx;
```

---

## ✅ Acceptance Criteria

Sistem dianggap **PASS** jika:

1. ✅ Alokasi asli dari Form Pengajuan dipertahankan
2. ✅ Biaya tambahan didistribusikan secara proporsional
3. ✅ Breakdown ditampilkan dengan jelas (Alokasi + Biaya Tambahan)
4. ✅ Auto-fill bekerja dengan benar
5. ✅ Label update real-time saat biaya tambahan diubah
6. ✅ Preview hutang tetap akurat
7. ✅ Validasi total tetap ketat (harus balance)
8. ✅ Submit berhasil dan data tersimpan dengan benar
9. ✅ Tidak ada error di console browser
10. ✅ Backward compatible dengan transaksi lama

---

## 🐛 Bug Report Template

Jika menemukan bug, gunakan template ini:

```markdown
### Bug Report

**Test Case:** [Nomor test case]
**Browser:** [Chrome/Firefox/Safari + versi]
**User Role:** [Owner/Admin/Teknisi]

**Steps to Reproduce:**
1. ...
2. ...
3. ...

**Expected Result:**
...

**Actual Result:**
...

**Screenshot:**
[Attach screenshot]

**Console Error:**
[Paste error dari browser console]

**Additional Info:**
- Invoice Number: ...
- Total Pengajuan: ...
- Biaya Tambahan: ...
```

---

## 📊 Testing Summary Template

Setelah selesai testing, isi summary ini:

```markdown
## Testing Summary

**Tester:** [Nama]
**Date:** [Tanggal]
**Browser:** [Browser + versi]

### Test Results:

| Test Case | Status | Notes |
|-----------|--------|-------|
| TC1: Baseline | ✅ PASS | - |
| TC2: Ongkir Kecil | ✅ PASS | - |
| TC3: Ongkir Besar | ✅ PASS | - |
| TC4: Multiple Biaya | ✅ PASS | - |
| TC5: Diskon | ✅ PASS | - |
| TC6: Hutang | ✅ PASS | - |
| TC7: Dynamic Update | ✅ PASS | - |
| TC8: Bagi Rata | ✅ PASS | - |
| TC9: Persentase | ✅ PASS | - |
| TC10: Database | ✅ PASS | - |

### Overall Status: ✅ PASS / ❌ FAIL

### Issues Found:
1. ...
2. ...

### Recommendations:
1. ...
2. ...
```

---

**Happy Testing! 🚀**
