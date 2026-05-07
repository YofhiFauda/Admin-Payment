# Price Index - Toggle Auto/Manual Update

## 📋 Ringkasan Perubahan

Mengganti tombol "Hitung" dan "Reset ke Auto" dengan **Toggle Switch** yang lebih intuitif untuk mengatur mode perhitungan Price Index.

## ✨ Fitur Baru

### 1. Toggle Switch Auto/Manual
- **Toggle Switch** yang jelas untuk beralih antara mode Auto dan Manual
- Visual feedback dengan warna berbeda:
  - **Auto Mode**: Biru (Blue)
  - **Manual Mode**: Ungu (Purple)

### 2. Behavior Berdasarkan Mode

#### Mode Auto (Toggle OFF)
- ✅ Sumber: **Auto**
- ✅ Harga AVG: **Dihitung otomatis** dari (Min + Max) / 2
- ✅ Field AVG: **Read-only** (tidak bisa diedit)
- ✅ Background AVG: Abu-abu (disabled)
- ✅ Badge: "Auto" (Biru)
- ✅ Saat save: Trigger recalculation dari history transaksi

#### Mode Manual (Toggle ON)
- ✅ Sumber: **Manual**
- ✅ Harga AVG: **Dapat diinput manual**
- ✅ Field AVG: **Editable** (bisa diedit)
- ✅ Background AVG: Normal (enabled)
- ✅ Badge: "Manual" (Ungu)
- ✅ Saat save: Simpan nilai manual yang diinput

### 3. Toast Notifications
- Notifikasi saat toggle berubah:
  - **Auto**: "Mode Auto: Harga AVG akan dihitung otomatis"
  - **Manual**: "Mode Manual: Anda dapat mengatur harga AVG secara manual"
- Notifikasi saat save berhasil dengan info mode yang digunakan

## 🔧 Perubahan Teknis

### Frontend (View)

#### File: `resources/views/price-index/index.blade.php`

**Perubahan:**
1. ❌ **Dihapus**: Tombol "Hitung" pada label Harga AVG
2. ❌ **Dihapus**: Tombol "Reset ke Auto" di footer modal
3. ✅ **Ditambah**: Toggle Switch dengan label dan deskripsi
4. ✅ **Ditambah**: Badge "Auto/Manual" pada field Harga AVG
5. ✅ **Ditambah**: Fungsi `toggleEditMode()` untuk handle perubahan toggle
6. ✅ **Ditambah**: Fungsi `updateEditModeUI()` untuk update UI berdasarkan mode
7. ✅ **Diupdate**: Fungsi `autoCalcAvgEdit()` - hanya jalan di mode Auto
8. ✅ **Diupdate**: Fungsi `submitEdit()` - kirim parameter `is_manual`

**JavaScript Functions:**
```javascript
// Toggle handler
function toggleEditMode() {
    const isManual = document.getElementById('edit_is_manual').checked;
    updateEditModeUI(isManual);
    
    if (!isManual) {
        autoCalcAvgEdit();
        showToast('Mode Auto: Harga AVG akan dihitung otomatis', 'info');
    } else {
        showToast('Mode Manual: Anda dapat mengatur harga AVG secara manual', 'info');
    }
}

// UI updater
function updateEditModeUI(isManual) {
    // Update field AVG (readonly/editable)
    // Update badge (Auto/Manual)
    // Update label dan deskripsi
}
```

### Backend (Controller)

#### File: `app/Http/Controllers/PriceIndexController.php`

**Method: `update()`**

**Perubahan:**
1. ✅ **Ditambah**: Validasi parameter `is_manual` (boolean)
2. ✅ **Ditambah**: Logic conditional berdasarkan mode:
   - **Manual**: Set audit trail (manual_set_by, manual_set_at, manual_reason)
   - **Auto**: Reset manual fields & trigger recalculation
3. ✅ **Diupdate**: Response message sesuai mode yang dipilih

**Logic Flow:**
```php
if ($isManual) {
    // Mode Manual: Simpan nilai manual
    $updateData['manual_set_by'] = Auth::id();
    $updateData['manual_set_at'] = now();
    $updateData['manual_reason'] = $request->input('manual_reason', 'Update manual');
} else {
    // Mode Auto: Reset & recalculate
    $updateData['manual_set_by'] = null;
    $updateData['manual_set_at'] = null;
    $updateData['manual_reason'] = 'Mode Auto diaktifkan oleh ' . Auth::user()->name;
    
    // Trigger recalculation
    $this->priceIndexService->recalculateFromHistory($priceIndex->item_name, $priceIndex->category);
}
```

## 🎨 UI/UX Improvements

### Toggle Switch Design
```html
<label class="relative inline-flex items-center cursor-pointer">
    <input type="checkbox" id="edit_is_manual" class="sr-only peer" onchange="toggleEditMode()">
    <div class="w-14 h-7 bg-blue-200 ... peer-checked:bg-purple-600"></div>
    <span class="ms-3 text-sm font-medium" id="edit_mode_label">Auto</span>
</label>
```

### Visual States
- **Auto Mode**: 
  - Toggle: Kiri (Blue)
  - AVG Field: Read-only (Gray background)
  - Badge: "Auto" (Blue)
  
- **Manual Mode**: 
  - Toggle: Kanan (Purple)
  - AVG Field: Editable (White background)
  - Badge: "Manual" (Purple)

## 📊 User Flow

### Scenario 1: Edit dengan Mode Auto
1. User klik tombol Edit pada item Price Index
2. Modal terbuka dengan toggle di posisi **Auto** (default jika sebelumnya Auto)
3. User ubah Min/Max → AVG otomatis terhitung
4. User tidak bisa edit field AVG (read-only)
5. User klik "Simpan Perubahan"
6. Toast: "Price Index berhasil diupdate (Mode Auto - Dihitung dari history)"
7. Data tersimpan dengan `is_manual = false`

### Scenario 2: Edit dengan Mode Manual
1. User klik tombol Edit pada item Price Index
2. User toggle switch ke **Manual**
3. Toast: "Mode Manual: Anda dapat mengatur harga AVG secara manual"
4. Field AVG menjadi editable
5. User input nilai AVG sesuai keinginan
6. User klik "Simpan Perubahan"
7. Toast: "Price Index berhasil diupdate (Mode Manual)"
8. Data tersimpan dengan `is_manual = true`

### Scenario 3: Switch dari Manual ke Auto
1. User buka item yang sebelumnya Manual
2. Toggle otomatis di posisi **Manual**
3. User toggle ke **Auto**
4. Toast: "Mode Auto: Harga AVG akan dihitung otomatis"
5. AVG otomatis terhitung dari (Min + Max) / 2
6. Field AVG menjadi read-only
7. User klik "Simpan Perubahan"
8. Backend trigger recalculation dari history
9. Data tersimpan dengan `is_manual = false`

## 🔍 Testing Checklist

### Frontend Testing
- [ ] Toggle switch berfungsi dengan baik
- [ ] Visual feedback (warna, badge) berubah sesuai mode
- [ ] Field AVG read-only di mode Auto
- [ ] Field AVG editable di mode Manual
- [ ] Auto-calculation berjalan di mode Auto
- [ ] Toast notification muncul saat toggle berubah
- [ ] Toast notification muncul saat save berhasil

### Backend Testing
- [ ] Parameter `is_manual` diterima dengan benar
- [ ] Mode Manual: Audit trail tersimpan
- [ ] Mode Auto: Recalculation triggered
- [ ] Response message sesuai mode
- [ ] Data tersimpan dengan benar di database

### Integration Testing
- [ ] Edit item Auto → tetap Auto → save → data benar
- [ ] Edit item Auto → switch Manual → save → data benar
- [ ] Edit item Manual → tetap Manual → save → data benar
- [ ] Edit item Manual → switch Auto → save → recalculation jalan
- [ ] Reload page → mode tersimpan dengan benar

## 🚀 Deployment Notes

### Database
Tidak ada perubahan schema database. Kolom yang digunakan:
- `is_manual` (boolean) - sudah ada
- `manual_set_by` (integer) - sudah ada
- `manual_set_at` (timestamp) - sudah ada
- `manual_reason` (text) - sudah ada

### Backward Compatibility
- ✅ Endpoint `resetToAuto()` masih ada di controller (untuk API/future use)
- ✅ Existing data tetap kompatibel
- ✅ Default behavior: Manual (untuk backward compatibility)

## 📝 Notes

### Keuntungan Perubahan Ini:
1. **Lebih Intuitif**: Toggle switch lebih jelas daripada tombol terpisah
2. **Real-time Feedback**: User langsung tahu mode yang aktif
3. **Konsisten**: Satu tempat untuk kontrol mode (toggle)
4. **Visual Clear**: Badge dan warna membedakan mode dengan jelas
5. **Toast Notification**: User mendapat feedback langsung

### Pertimbangan:
- Toggle switch menggunakan Tailwind CSS classes (peer, peer-checked)
- Toast notification menggunakan fungsi `showToast()` yang sudah ada di layout
- Recalculation di mode Auto menggunakan service yang sudah ada

## 🔗 Related Files

### Modified:
- `resources/views/price-index/index.blade.php`
- `app/Http/Controllers/PriceIndexController.php`

### Unchanged (Still Used):
- `app/Services/PriceIndex/PriceIndexService.php`
- `app/Models/PriceIndex.php`
- `resources/views/layouts/app.blade.php` (showToast function)

---

**Status**: ✅ Implemented
**Date**: 2026-05-07
**Author**: Kiro AI Assistant
