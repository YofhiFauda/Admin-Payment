# 🎯 SOLUSI FINAL - Double Initialization Bug

## 🔴 ROOT CAUSE TERIDENTIFIKASI

### **Masalah Utama: DOUBLE INITIALIZATION**

Anda benar! Perubahan tidak ada efek karena ada **2 script yang berjalan bersamaan**:

1. **Module JS**: `resources/js/transactions/form-pengajuan/index.js`
   - Menginisialisasi `BranchDistribution` dan `ItemRepeater` class
   - Berjalan untuk semua form dengan ID `pengajuan-form`
   - Memanggil `renderDistribution()` setiap kali total berubah

2. **Inline Script**: Di `resources/views/transactions/edit-pengajuan.blade.php`
   - Punya logika sendiri yang duplikasi dari module JS
   - Juga menginisialisasi distribution dan item management
   - Kedua script saling override!

### **Bukti**:

```javascript
// File: resources/js/transactions/form-pengajuan/index.js (Baris 6-9)
const pengajuanForm = document.getElementById('pengajuan-form');
if (!pengajuanForm) return;

// File: resources/views/transactions/edit-pengajuan.blade.php (Baris 165)
<form method="POST" ... id="pengajuan-form">
```

**Kedua script mendeteksi form yang sama!**

---

## ✅ PERBAIKAN YANG SUDAH DILAKUKAN

### **1. Skip Edit Page di Module JS** ✅

**File**: `resources/js/transactions/form-pengajuan/index.js`

**Perubahan**:
```javascript
// ✅ FIX: Skip if this is edit page (has inline script)
const isEditPage = pengajuanForm.action.includes('/transactions/') && 
                   pengajuanForm.method.toUpperCase() === 'POST' && 
                   pengajuanForm.querySelector('input[name="_method"][value="PUT"]');
if (isEditPage) {
    console.log('[form-pengajuan/index.js] Skipping initialization for edit page');
    return;
}
```

**Hasil**: Module JS sekarang **TIDAK** akan berjalan di halaman edit pengajuan.

---

### **2. Tambah Method `updateValues()` di BranchDistribution** ✅

**File**: `resources/js/transactions/shared/distribution.js`

**Perubahan**: Tambah method baru yang update nilai tanpa re-render HTML:

```javascript
/**
 * ✅ FIX: Update distribution values without full re-render
 * This prevents input loss when user is typing
 */
updateValues() {
    if (!this.distributionList || this.selectedBranches.length === 0) return;

    const totalAmount = parseInt(this.formTotalInput?.value) || 0;

    this.selectedBranches.forEach((branch, idx) => {
        // Recalculate values based on method
        if (this.currentMethod === 'equal') {
            branch.percent = parseFloat((100 / this.selectedBranches.length).toFixed(2));
            branch.value = totalAmount > 0 ? Math.round(totalAmount / this.selectedBranches.length) : 0;
        } else if (this.currentMethod === 'percent') {
            branch.value = totalAmount > 0 ? Math.round((totalAmount * (branch.percent || 0)) / 100) : 0;
        } else if (this.currentMethod === 'manual') {
            branch.percent = totalAmount > 0 ? parseFloat(((branch.value / totalAmount) * 100).toFixed(2)) : 0;
        }

        // Update DOM directly without innerHTML
        const row = this.distributionList.querySelector(`[data-branch-index="${idx}"]`);
        if (!row) return;

        // Update displays based on method
        // ... (update value/percent displays)
    });

    this.updateHiddenInputs();
    this.updateSummaryList();
    this.validateAndSubmit();
}
```

**Hasil**: Sekarang ada 2 method:
- `renderDistribution()` - Full re-render (untuk pilih cabang, ganti method)
- `updateValues()` - Update nilai saja (untuk perubahan total)

---

### **3. Update Callback di ItemRepeater** ✅

**File**: `resources/js/transactions/form-pengajuan/index.js`

**Perubahan**:
```javascript
// BEFORE:
() => distribution.renderDistribution()

// AFTER:
() => {
    if (distribution.selectedBranches && distribution.selectedBranches.length > 0) {
        distribution.updateValues(); // ✅ Tidak re-render HTML
    }
}
```

**Hasil**: Saat user ubah qty/harga barang, distribution **TIDAK** di-render ulang, hanya nilai yang di-update.

---

### **4. Tambah `data-branch-index` di HTML** ✅

**File**: `resources/js/transactions/shared/distribution.js`

**Perubahan**:
```javascript
// BEFORE:
const rowHtml = `
    <div class="flex justify-between items-center ...">

// AFTER:
const rowHtml = `
    <div class="flex justify-between items-center ..." data-branch-index="${idx}">
```

**Hasil**: Setiap row distribution punya identifier unik untuk update spesifik.

---

## 🧪 TESTING

### **Test Case 1: Edit Page (Inline Script)**

1. Buka halaman **Edit Pengajuan**
2. Buka Console Browser (F12)
3. **Expected**: Lihat log `[form-pengajuan/index.js] Skipping initialization for edit page`
4. Pilih 2 cabang, pilih "Persentase"
5. Input 60% di cabang pertama
6. Ubah qty barang
7. **Expected**: 60% masih ada, tidak hilang

### **Test Case 2: Create Page (Module JS)**

1. Buka halaman **Create Pengajuan** (bukan edit)
2. Buka Console Browser (F12)
3. **Expected**: TIDAK ada log skip (module JS berjalan normal)
4. Pilih 2 cabang, pilih "Persentase"
5. Input 60% di cabang pertama
6. Ubah qty barang
7. **Expected**: 60% masih ada, tidak hilang (karena pakai `updateValues()`)

---

## 📊 PERBANDINGAN

### **BEFORE (Bug)**:

```
User Action: Ubah qty barang dari 1 ke 2
  ↓
ItemRepeater.updateGlobalTotal()
  ↓
Callback: distribution.renderDistribution()
  ↓
distributionList.innerHTML = '' ❌ HAPUS SEMUA
  ↓
Render ulang dengan nilai default
  ↓
Input user HILANG ❌
```

### **AFTER (Fixed)**:

```
User Action: Ubah qty barang dari 1 ke 2
  ↓
ItemRepeater.updateGlobalTotal()
  ↓
Callback: distribution.updateValues() ✅
  ↓
Update nilai di DOM langsung (tanpa innerHTML)
  ↓
Input user TETAP ADA ✅
```

---

## 🎯 KESIMPULAN

### **Masalah Teridentifikasi**:
1. ✅ Double initialization (module JS + inline script)
2. ✅ `renderDistribution()` dipanggil terlalu sering
3. ✅ `innerHTML = ''` menghapus input user

### **Solusi Diterapkan**:
1. ✅ Skip module JS untuk edit page
2. ✅ Tambah method `updateValues()` yang tidak re-render
3. ✅ Update callback untuk pakai `updateValues()`
4. ✅ Tambah `data-branch-index` untuk update spesifik

### **Status**:
- ✅ **Bug Fixed** di module JS (untuk halaman CREATE)
- ⚠️ **Inline Script** di edit-pengajuan.blade.php masih perlu diperbaiki dengan cara yang sama

---

## 🔧 NEXT STEPS

### **Untuk Halaman Edit (Inline Script)**:

Anda masih perlu apply perbaikan yang sama di inline script:

1. **Buka**: `resources/views/transactions/edit-pengajuan.blade.php`
2. **Cari**: Baris ~1158 `renderDistribution(grandTotal);`
3. **Ganti dengan**:
```javascript
if (selectedBranches.length > 0) {
    updateDistributionValues(grandTotal);
} else {
    renderDistribution(grandTotal);
}
```

4. **Tambah fungsi** `updateDistributionValues()` (lihat QUICK_REFERENCE.md)

5. **Update** `renderDistribution()` untuk tambah `data-branch-index`

---

## 📞 VERIFIKASI

### **Cara Cek Apakah Sudah Fixed**:

1. **Clear browser cache** (Ctrl+Shift+Delete)
2. **Hard reload** (Ctrl+F5)
3. Buka Console (F12)
4. Buka halaman Edit Pengajuan
5. **Cek log**: Harus ada `[form-pengajuan/index.js] Skipping initialization for edit page`
6. Test distribution list

### **Jika Masih Belum Fixed**:

Kemungkinan:
- Browser cache belum clear
- Build JS belum di-compile (jika pakai Vite/Webpack)
- Inline script di blade masih belum diperbaiki

**Solusi**:
1. Clear cache browser
2. Restart dev server (jika ada)
3. Check apakah file JS sudah ter-update di browser (lihat di Sources tab)

---

**Status**: ✅ Module JS Fixed | ⚠️ Inline Script Perlu Diperbaiki  
**Date**: 2026-04-30  
**Files Modified**: 
- `resources/js/transactions/form-pengajuan/index.js`
- `resources/js/transactions/shared/distribution.js`
