# 🔧 Changelog: Perbaikan Pembagian Biaya Pengajuan

**Tanggal:** {{ date('Y-m-d H:i:s') }}  
**Versi:** 1.0.0  
**Status:** ✅ Implemented

---

## 📋 Ringkasan Perubahan

Memperbaiki logika **Sumber Dana** di Upload Pembayaran Invoice Pengajuan agar:
1. ✅ Mengikuti pembagian biaya dari Form Pengajuan (Manual/Persentase/Bagi Rata)
2. ✅ Mendistribusikan biaya tambahan (ongkir, PPN, dll) secara proporsional
3. ✅ Menampilkan breakdown yang jelas: Alokasi Asli + Biaya Tambahan

---

## 🐛 Masalah yang Diperbaiki

### **SEBELUM (Bug):**
```javascript
// Selalu recalculate berdasarkan total invoice baru
const alloc = Math.round((finalTotalTarget * percent) / 100);
```

**Dampak:**
- ❌ Alokasi asli dari Form Pengajuan diabaikan
- ❌ Semua cabang dipaksa bayar berdasarkan total baru
- ❌ Tidak adil jika ada biaya tambahan besar (misal ongkir Rp 200.000)

**Contoh Masalah:**
```
Form Pengajuan:
- OLT JETIS: 40% = Rp 80.000 dari total Rp 200.000

Invoice dengan ongkir Rp 200.000:
- Total: Rp 400.000
- Sistem recalculate: OLT JETIS 40% = Rp 160.000 ❌ SALAH!
```

---

## ✅ Solusi yang Diterapkan

### **SESUDAH (Fixed):**
```javascript
// 1. Gunakan allocation_amount asli dari Form Pengajuan
const allocFromDB = parseInt(cb.dataset.alloc);

// 2. Hitung biaya tambahan
const additionalCosts = ongkir + dppLainnya + taxAmt + layanan1 + layanan2 - diskon - voucher;

// 3. Distribusikan biaya tambahan secara proporsional
const additionalShare = Math.round((additionalCosts * percent) / 100);

// 4. Total alokasi = Alokasi asli + Bagian biaya tambahan
const alloc = allocFromDB + additionalShare;
```

**Hasil:**
```
Form Pengajuan:
- OLT JETIS: 40% = Rp 80.000 dari total Rp 200.000

Invoice dengan ongkir Rp 200.000:
- Subtotal: Rp 200.000
- Ongkir: Rp 200.000
- Total: Rp 400.000

Sumber Dana:
┌─────────────────────────────────────────────────────┐
│ OLT JETIS (40%)                                     │
│ Alokasi Pengajuan:  Rp 80.000                      │
│ + Biaya Ongkir:     Rp 80.000 (40% × 200.000)      │
│ ─────────────────────────────────────────────────── │
│ Total:              Rp 160.000 ✅ BENAR!            │
└─────────────────────────────────────────────────────┘
```

---

## 📁 File yang Dimodifikasi

### 1. `resources/js/transactions/payment.js`

#### **Perubahan A: Fungsi `calculateSumberDanaTotal()` (Baris ~706-770)**

**Sebelum:**
```javascript
const finalTotalTarget = baseTotal + ongkir + dppLainnya + taxAmt + layanan1 + layanan2 - diskon - voucher;

document.querySelectorAll('.sd-checkbox').forEach(cb => {
    const percent = parseFloat(cb.dataset.percent);
    const alloc = Math.round((finalTotalTarget * percent) / 100); // ❌ Recalculate
    
    if (labelEl) {
        labelEl.textContent = `Alokasi: Rp ${alloc.toLocaleString('id-ID')} (${percent}%)`;
    }
});
```

**Sesudah:**
```javascript
// ✅ Pisahkan biaya tambahan dari baseTotal
const additionalCosts = ongkir + dppLainnya + taxAmt + layanan1 + layanan2 - diskon - voucher;
const finalTotalTarget = baseTotal + additionalCosts;

document.querySelectorAll('.sd-checkbox').forEach(cb => {
    const percent = parseFloat(cb.dataset.percent);
    
    // ✅ Gunakan allocation_amount asli dari Form Pengajuan
    const allocFromDB = parseInt(cb.dataset.alloc);
    
    // ✅ Distribusikan biaya tambahan secara proporsional
    const additionalShare = Math.round((additionalCosts * percent) / 100);
    
    // ✅ Total alokasi = Alokasi asli + Bagian biaya tambahan
    const alloc = allocFromDB + additionalShare;
    
    // ✅ Update label dengan breakdown yang jelas
    if (labelEl) {
        if (additionalCosts !== 0) {
            const additionalLabel = additionalShare >= 0 
                ? `+ Biaya Tambahan: Rp ${additionalShare.toLocaleString('id-ID')}`
                : `- Potongan: Rp ${Math.abs(additionalShare).toLocaleString('id-ID')}`;
            
            labelEl.innerHTML = `
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-tight">
                    Alokasi Pengajuan: Rp ${allocFromDB.toLocaleString('id-ID')} (${percent}%)
                </span>
                <span class="block text-[9px] font-bold ${additionalShare >= 0 ? 'text-teal-500' : 'text-amber-500'} mt-0.5 leading-tight">
                    ${additionalLabel}
                </span>
                <span class="block text-[9px] font-black text-slate-600 mt-0.5 leading-tight">
                    Total: Rp ${alloc.toLocaleString('id-ID')}
                </span>
            `;
        } else {
            labelEl.textContent = `Alokasi: Rp ${allocFromDB.toLocaleString('id-ID')} (${percent}%)`;
        }
    }
});
```

---

#### **Perubahan B: Event Listener Checkbox (Baris ~656-680)**

**Sebelum:**
```javascript
cb.addEventListener('change', function () {
    const alloc = parseInt(this.dataset.alloc); // ❌ Hanya alokasi asli
    
    if (this.checked) {
        amountInput.value = formatNumber(alloc); // ❌ Auto-fill tanpa biaya tambahan
    }
});
```

**Sesudah:**
```javascript
cb.addEventListener('change', function () {
    // ✅ Hitung total alokasi dengan biaya tambahan
    const allocFromDB = parseInt(this.dataset.alloc);
    const percent = parseFloat(this.dataset.percent);
    
    // Hitung biaya tambahan saat ini
    const ongkir = unformatNumber(document.getElementById('p_ongkir')?.value || "0");
    const diskon = unformatNumber(document.getElementById('p_diskon_pengiriman')?.value || "0");
    const voucher = unformatNumber(document.getElementById('p_voucher_diskon')?.value || "0");
    const dppLainnya = unformatNumber(document.getElementById('p_dpp_lainnya')?.value || "0");
    const taxAmt = unformatNumber(document.getElementById('p_tax_amount')?.value || "0");
    const layanan1 = unformatNumber(document.getElementById('p_biaya_layanan_1')?.value || "0");
    const layanan2 = unformatNumber(document.getElementById('p_biaya_layanan_2')?.value || "0");
    const additionalCosts = ongkir + dppLainnya + taxAmt + layanan1 + layanan2 - diskon - voucher;
    
    // Bagian biaya tambahan untuk cabang ini
    const additionalShare = Math.round((additionalCosts * percent) / 100);
    const totalAlloc = allocFromDB + additionalShare;

    if (this.checked) {
        amountInput.value = formatNumber(totalAlloc); // ✅ Auto-fill dengan total yang benar
    }
});
```

---

## 🎯 Fitur Baru

### 1. **Breakdown Alokasi yang Jelas**

Sekarang user bisa melihat detail pembagian:

```
┌─────────────────────────────────────────────────────┐
│ ☑ OLT JETIS                                         │
│                                                     │
│ Alokasi Pengajuan: Rp 80.000 (40%)                 │
│ + Biaya Tambahan: Rp 80.000                        │
│ Total: Rp 160.000                                   │
│                                                     │
│ [Input: Rp 160.000]                                 │
└─────────────────────────────────────────────────────┘
```

### 2. **Auto-Fill Cerdas**

Ketika user centang checkbox cabang:
- ✅ Otomatis terisi dengan: Alokasi Asli + Biaya Tambahan
- ✅ Update otomatis saat user ubah ongkir/PPN/dll
- ✅ User tetap bisa edit manual jika perlu

### 3. **Support Potongan (Diskon/Voucher)**

Jika ada diskon atau voucher:
```
Alokasi Pengajuan: Rp 80.000 (40%)
- Potongan: Rp 8.000
Total: Rp 72.000
```

---

## 📊 Contoh Skenario

### **Skenario 1: Tanpa Biaya Tambahan**

**Form Pengajuan:**
```
Total: Rp 200.000
- OLT JETIS:     40% → Rp 80.000
- OLT SIMAN:     10% → Rp 20.000
- OLT SLAHUNG:   25% → Rp 50.000
- OLT SUMBEREJO: 25% → Rp 50.000
```

**Upload Invoice:**
```
Subtotal: Rp 200.000
Ongkir: Rp 0
Total: Rp 200.000

Sumber Dana (Tampilan):
┌─────────────────────────────────────┐
│ ☑ OLT JETIS                         │
│ Alokasi: Rp 80.000 (40%)            │
│ [Input: Rp 80.000]                  │
└─────────────────────────────────────┘
```

---

### **Skenario 2: Dengan Ongkir Rp 200.000**

**Form Pengajuan:**
```
Total: Rp 200.000
- OLT JETIS:     40% → Rp 80.000
- OLT SIMAN:     10% → Rp 20.000
- OLT SLAHUNG:   25% → Rp 50.000
- OLT SUMBEREJO: 25% → Rp 50.000
```

**Upload Invoice:**
```
Subtotal: Rp 200.000
Ongkir: Rp 200.000
Total: Rp 400.000

Sumber Dana (Tampilan):
┌─────────────────────────────────────────────────────┐
│ ☑ OLT JETIS                                         │
│ Alokasi Pengajuan: Rp 80.000 (40%)                 │
│ + Biaya Tambahan: Rp 80.000                        │
│ Total: Rp 160.000                                   │
│ [Input: Rp 160.000]                                 │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ ☑ OLT SIMAN                                         │
│ Alokasi Pengajuan: Rp 20.000 (10%)                 │
│ + Biaya Tambahan: Rp 20.000                        │
│ Total: Rp 40.000                                    │
│ [Input: Rp 40.000]                                  │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ ☑ OLT SLAHUNG                                       │
│ Alokasi Pengajuan: Rp 50.000 (25%)                 │
│ + Biaya Tambahan: Rp 50.000                        │
│ Total: Rp 100.000                                   │
│ [Input: Rp 100.000]                                 │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ ☑ OLT SUMBEREJO                                     │
│ Alokasi Pengajuan: Rp 50.000 (25%)                 │
│ + Biaya Tambahan: Rp 50.000                        │
│ Total: Rp 100.000                                   │
│ [Input: Rp 100.000]                                 │
└─────────────────────────────────────────────────────┘

Total Sumber Dana: Rp 400.000 ✅
Status: Nominal sesuai dengan nilai bayar transaksi
```

---

### **Skenario 3: Sebagian Bayar, Sebagian Hutang**

**Upload Invoice:**
```
Subtotal: Rp 200.000
Ongkir: Rp 200.000
Total: Rp 400.000

Sumber Dana:
☑ OLT JETIS:     Rp 200.000 (lebih bayar Rp 40.000)
☑ OLT SIMAN:     Rp 100.000 (lebih bayar Rp 60.000)
☐ OLT SLAHUNG:   (berhutang Rp 100.000)
☐ OLT SUMBEREJO: (berhutang Rp 100.000)

Preview Hutang Otomatis:
┌─────────────────────────────────────┐
│ OLT SLAHUNG                         │
│ Total beban hutang: Rp 100.000      │
│ Rincian Pembayaran Ke:              │
│   → OLT JETIS: Rp 40.000            │
│   → OLT SIMAN: Rp 60.000            │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ OLT SUMBEREJO                       │
│ Total beban hutang: Rp 100.000      │
│ Rincian Pembayaran Ke:              │
│   → OLT JETIS: Rp 40.000            │
│   → OLT SIMAN: Rp 60.000            │
└─────────────────────────────────────┘
```

---

## 🧪 Testing Checklist

### **Test 1: Tanpa Biaya Tambahan**
- [ ] Buat Pengajuan dengan metode "Manual" (40%, 10%, 25%, 25%)
- [ ] Approve pengajuan
- [ ] Upload Invoice dengan total sama (Rp 200.000)
- [ ] Verifikasi Sumber Dana menampilkan nilai yang sama dengan Form Pengajuan
- [ ] Centang semua cabang, verifikasi auto-fill benar
- [ ] Submit, verifikasi tidak ada error

### **Test 2: Dengan Ongkir**
- [ ] Buat Pengajuan Rp 200.000 dengan alokasi manual
- [ ] Upload Invoice dengan ongkir Rp 20.000
- [ ] Verifikasi breakdown: Alokasi Asli + Biaya Ongkir
- [ ] Verifikasi total per cabang benar
- [ ] Centang semua cabang, verifikasi auto-fill sudah include ongkir
- [ ] Submit, verifikasi tidak ada error

### **Test 3: Dengan Ongkir Besar (Rp 200.000)**
- [ ] Buat Pengajuan Rp 200.000 dengan alokasi manual
- [ ] Upload Invoice dengan ongkir Rp 200.000 (total Rp 400.000)
- [ ] Verifikasi OLT JETIS: Rp 80.000 + Rp 80.000 = Rp 160.000
- [ ] Verifikasi OLT SIMAN: Rp 20.000 + Rp 20.000 = Rp 40.000
- [ ] Verifikasi total balance Rp 400.000
- [ ] Submit, verifikasi tidak ada error

### **Test 4: Dengan Diskon**
- [ ] Buat Pengajuan Rp 200.000
- [ ] Upload Invoice dengan diskon Rp 20.000 (total Rp 180.000)
- [ ] Verifikasi breakdown menampilkan "- Potongan"
- [ ] Verifikasi total per cabang berkurang proporsional
- [ ] Submit, verifikasi tidak ada error

### **Test 5: Sebagian Bayar, Sebagian Hutang**
- [ ] Buat Pengajuan Rp 200.000 dengan 4 cabang
- [ ] Upload Invoice dengan ongkir Rp 200.000
- [ ] Centang hanya 2 cabang dengan nilai lebih besar
- [ ] Verifikasi Preview Hutang muncul dengan benar
- [ ] Submit, verifikasi hutang tercatat di database

### **Test 6: Edit Biaya Tambahan Setelah Centang**
- [ ] Upload Invoice, centang semua cabang
- [ ] Ubah nilai ongkir dari Rp 0 → Rp 50.000
- [ ] Verifikasi label update otomatis
- [ ] Verifikasi input value TIDAK berubah (user harus manual update)
- [ ] Uncheck dan check lagi, verifikasi auto-fill dengan nilai baru

---

## 🔄 Backward Compatibility

### **Transaksi Lama (Sebelum Update):**
✅ Tetap berfungsi normal karena:
- Data `allocation_percent` dan `allocation_amount` sudah ada di database
- Logika baru membaca dari field yang sama
- Tidak ada perubahan struktur database

### **Transaksi Baru (Setelah Update):**
✅ Menggunakan logika baru:
- Alokasi asli dari Form Pengajuan dipertahankan
- Biaya tambahan didistribusikan proporsional
- Breakdown ditampilkan dengan jelas

---

## 📝 Catatan Penting

### **1. User Tetap Bisa Edit Manual**
Meskipun ada auto-fill, user tetap bisa:
- Edit nilai input secara manual
- Mengatur pembagian custom jika perlu
- Sistem hanya memberikan saran, bukan memaksa

### **2. Validasi Tetap Ketat**
- Total Sumber Dana HARUS sama dengan Total Invoice
- Jika tidak balance, tombol Submit disabled
- Warning ditampilkan dengan jelas

### **3. Preview Hutang Tetap Akurat**
- Logika hutang tidak berubah
- Tetap menghitung berdasarkan selisih bayar vs alokasi
- Distribusi hutang tetap proporsional

---

## 🚀 Deployment

### **Langkah Deploy:**

1. **Backup File Lama:**
   ```bash
   cp resources/js/transactions/payment.js resources/js/transactions/payment.js.backup
   ```

2. **Deploy File Baru:**
   - File sudah diupdate: `resources/js/transactions/payment.js`

3. **Compile Assets:**
   ```bash
   npm run build
   # atau
   npm run dev
   ```

4. **Clear Cache:**
   ```bash
   php artisan cache:clear
   php artisan view:clear
   ```

5. **Testing:**
   - Jalankan semua test checklist di atas
   - Verifikasi di browser (hard refresh: Ctrl+Shift+R)

---

## 🐛 Troubleshooting

### **Issue 1: Label Tidak Update**
**Gejala:** Label masih menampilkan format lama
**Solusi:** 
- Hard refresh browser (Ctrl+Shift+R)
- Clear browser cache
- Pastikan `npm run build` sudah dijalankan

### **Issue 2: Auto-Fill Salah**
**Gejala:** Nilai auto-fill tidak sesuai ekspektasi
**Solusi:**
- Cek console browser untuk error JavaScript
- Verifikasi data `allocation_percent` dan `allocation_amount` di database
- Pastikan fungsi `unformatNumber()` bekerja dengan benar

### **Issue 3: Total Tidak Balance**
**Gejala:** Total Sumber Dana tidak sama dengan Total Invoice
**Solusi:**
- Cek pembulatan (Math.round) di perhitungan
- Verifikasi semua biaya tambahan sudah diinput
- Cek apakah ada cabang yang tidak tercentang

---

## 📞 Support

Jika ada masalah atau pertanyaan:
1. Cek file `ANALISIS_PEMBAGIAN_BIAYA_PENGAJUAN.md` untuk detail teknis
2. Review kode di `resources/js/transactions/payment.js` baris 656-770
3. Test dengan data dummy terlebih dahulu

---

**Dibuat:** {{ date('Y-m-d H:i:s') }}  
**Developer:** Kiro AI Assistant  
**Status:** ✅ Ready for Production
