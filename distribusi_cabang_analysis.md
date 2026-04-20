# 🛡️ Analisis Keamanan & Distribusi Cabang (Rembush & Pengajuan)

Analisis ini mengidentifikasi celah manipulasi pada distribusi alokasi cabang yang memungkinkan terjadinya selisih antara total transaksi dengan total nominal yang dibebankan ke cabang.

## 🔍 Temuan Utama: Celah "Mismatched Allocation"

Masalah utama bukan terletak pada perhitungan persentase, melainkan pada **prioritas pengambilan data di Backend** (khususnya pada fitur Edit/Update) dan **kurangnya validasi total nominal di Frontend**.

### 1. Backend Vulnerability (Logic Flaw)
Pada `TransactionController@update`, terdapat logika yang "terlalu percaya" pada input manual `allocation_amount` jika tersedia, tanpa memvalidasi apakah total nominal tersebut sesuai dengan total transaksi.

**Potongan Kode Bermasalah (`app/Http/Controllers/TransactionController.php`):**
```php
$allocAmount = isset($branchData['allocation_amount']) && $branchData['allocation_amount']
    ? intval($branchData['allocation_amount']) // ❗ VULNERABILITY: Diambil langsung dari request
    : intval(round(($effectiveAmount * $allocPercent) / 100)); // Recalculate hanya jika amount kosong
```

### 2. Frontend Validation Gap
Pada form `Manual` dan `Bagi Rata`, sistem hanya mengandalkan perhitungan otomatis tanpa memblokir tombol submit jika terjadi ketidaksinkronan data.

---

## 💡 Rekomendasi Perbaikan

### 1. Penguatan Backend (Wajib)
Hapus kepercayaan pada `allocation_amount` dari request. Backend **HARUS selalu menghitung ulang** nominal berdasarkan persentase yang dikirim.

### 2. Pengetatan Frontend
Tambahkan validasi real-time pada fungsi `validateAndToggleSubmit()` untuk semua metode distribusi (Manual, Persen, Bagi Rata).

---

## 📝 Catatan Khusus: Kasus Rembush (OCR Flow)
Terdapat perbedaan alur antara **Pengajuan** (nominal diketahui di awal) dan **Rembush** (nominal bisa 0 karena menunggu proses OCR).

### Penanganan Alur Rembush:
1.  **Integritas Persentase:** Pada form Rembush awal, sistem mewajibkan total persentase **100%** meskipun nominal transaksi masih Rp 0.
2.  **Mekanisme Overwrite Otomatis:** Sistem telah dikonfigurasi untuk menutup celah manipulasi nominal Rp 0 melalui `OcrNotaController`. Segera setelah OCR menemukan nominal final, sistem akan **menghitung ulang secara otomatis** seluruh alokasi cabang berdasarkan persentase yang disimpan di awal.
3.  **Anti-Bypass:** User tidak bisa memanipulasi nominal akhir karena sistem akan selalu melakukan *re-calculation* di tahap finalisasi (Finalization).

---

## ✅ Status Implementasi Terakhir

1.  **Backend (`TransactionController.php`):** 
    - ✅ **FIXED.** Logika `update` telah diubah untuk selalu melakukan *re-calculation* dari persentase.
    - ✅ **FIXED.** Menambahkan *Rounding Adjustment* pada cabang terakhir untuk memastikan total alokasi pas (mencegah selisih Rp 1).
2.  **Frontend (`blade.php`):**
    - ✅ **FIXED.** `form-pengajuan.blade.php`, `edit-rembush.blade.php`, dan `edit-pengajuan.blade.php` telah diperbarui dengan validasi `Math.abs(totalAllocated - totalAmount) > 2`.
    - ✅ **FIXED.** Tombol Submit akan terkunci jika nominal tidak sinkron.

---

## 🚀 Kesimpulan Akhir
Celah manipulasi "Mismatched Allocation" telah **tertutup sepenuhnya** baik untuk transaksi baru (Store) maupun pengeditan (Update), termasuk pada kasus khusus Rembush yang menggunakan OCR. Persentase alokasi kini menjadi "Single Source of Truth" untuk perhitungan beban biaya antar cabang.
