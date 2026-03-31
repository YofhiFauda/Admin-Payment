# Rancangan Implementasi Filter Multi-Cabang (Multi-Select)

Fitur ini akan memungkinkan Admin/Owner untuk memfilter daftar transaksi berdasarkan satu atau lebih cabang secara instan (Client-Side) tanpa reload halaman.

## User Review Required

> [!IMPORTANT]
> **Penempatan UI**: Filter ini direncanakan diletakkan di sebelah search bar pada toolbar atas. Apakah Anda memiliki preferensi posisi lain?
> **Cara Kerja Filter**: Jika 2 cabang dipilih (misal: Cabang A dan B), sistem akan menampilkan transaksi yang memiliki alokasi ke Cabang A **ATAU** Cabang B.

## Proposed Changes

### 1. Backend: Model & Controller

#### [MODIFY] [Transaction.php](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/app/Models/Transaction.php)
*   Memperbarui `toSearchArray()` untuk menyertakan `branch_ids` demi akurasi filter yang lebih baik daripada sekadar nama string.

#### [MODIFY] [TransactionController.php](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/app/Http/Controllers/TransactionController.php)
*   Mengirimkan data koleksi `$branches` ke view `index` agar dropdown bisa di-populate dengan daftar cabang yang ada di database.

---

### 2. Frontend: UI & Logic (Riwayat Transaksi)

#### [MODIFY] [index.blade.php](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/resources/views/transactions/index.blade.php)

**A. User Interface (HTML/CSS):**
*   Menambahkan komponen **Custom Multi-select Dropdown** yang modern menggunakan Tailwind CSS.
*   Menggunakan icon Lucide (seperti `map-pin` atau `filter`) untuk mempercantik tampilan.
*   Menyediakan tombol "Reset Filter" atau "Clear All" jika ada cabang yang terpilih.

**B. Search Logic (JavaScript):**
*   Memperbarui objek `SearchEngine` untuk mendukung state `selectedBranches`.
*   Menambahkan fungsi `updateBranchFilter()` yang dipicu saat user mencentang/menghapus pilihan cabang.
*   Logika filter akan dijalankan secara instan menggabungkan filter Pencarian Teks, Status, dan Cabang.

---

## Open Questions

1.  **Default State**: Apakah saat pertama kali buka halaman, semua cabang langsung terpilih atau lebih baik kosong (menampilkan semua)? *Saran: Kosong berarti "Semua Cabang".*
2.  **Display Mode**: Di baris tabel transaksi, apakah perlu ada indikator visual jika data tersebut sedang difilter berdasarkan cabang tertentu?

## Verification Plan

### Manual Verification
1.  Buka halaman Riwayat Transaksi.
2.  Klik dropdown Cabang.
3.  Pilih satu cabang -> Data harus terupdate instan.
4.  Pilih cabang kedua -> Data yang muncul harus bertambah (OR logic).
5.  Klik "Clear" -> Data kembali ke semula (tampil semua).
6.  Coba gabungkan dengan Pencarian Teks (Cari "Bensin" di Cabang "Jakarta").
