# Analisis Arsitektur Database & Sistem Ekspor WHUSNET Admin Payment

Berdasarkan hasil analisa terhadap struktur database dan kode saat ini (khususnya untuk model `Transaction`, `OtherExpenditure`, dan `SalaryRecord`), berikut adalah evaluasi arsitektural untuk kebutuhan ekspor yang konsisten:

## 1. Kondisi Saat Ini (Status Quo)
*   **Tabel `transactions` (STI - Single Table Inheritance):** Menggabungkan *Rembush* dan *Pengajuan*. Menggunakan kolom `type` sebagai pembeda. Tabel ini bersifat "lebar" (fat table) dengan banyak kolom nullable yang hanya terisi tergantung tipenya (misal: kolom `category` hanya untuk Rembush, sementara `vendor` hanya untuk Pengajuan).
*   **Tabel Terpisah:** `OtherExpenditure` (Pengeluaran Lain) dan `SalaryRecord` (Gaji) berada di tabel yang sepenuhnya berbeda karena struktur datanya sangat spesifik (terutama Gaji yang memiliki banyak komponen breakdown).
*   **Masalah Ekspor:** Jika Anda melakukan ekspor langsung dari tabel `transactions`, CSV/Excel akan memiliki kolom `category` dan `vendor` secara bersamaan, sehingga banyak sel yang kosong (tidak konsisten). Masalah ini akan bertambah rumit jika ingin menggabungkan Gaji dan Pengeluaran Lain ke dalam satu file ekspor.

## 2. Rekomendasi Arsitektur: "Hybrid Approach"

Untuk menjaga kode tetap bersih (Clean Code) namun hasil ekspor konsisten, disarankan pendekatan berikut:

### A. Tetap Pisahkan Tabel secara Fisik (Domain Separation)
Jangan menyatukan Gaji atau Pengeluaran Lain ke dalam tabel `transactions`. Struktur datanya terlalu berbeda. Memaksanya masuk ke satu tabel akan menciptakan tabel "monster" dengan ratusan kolom nullable yang sulit dimaintain.

### B. Gunakan Arsitektur "Normalized Reporting Layer" untuk Ekspor
Untuk mendapatkan kolom yang konsisten saat ekspor, disarankan menggunakan lapisan abstraksi:

1.  **Interface/Contract:** Buat sebuah interface (misal: `ExportableTransaction`) yang diimplementasikan oleh ketiga model tersebut.
2.  **Normalization Method:** Setiap model wajib memiliki method `toExportArray()`. Di sinilah "inkonsistensi" kolom diselesaikan dengan cara pemetaan (mapping):
    *   **Rembush:** Kolom `Keterangan` diisi dari `category`.
    *   **Pengajuan:** Kolom `Keterangan` diisi dari `purchase_reason` + `vendor`.
    *   **Gaji:** Kolom `Keterangan` diisi "Gaji Periode [Periode]".
    *   **Hutang:** Kolom `Keterangan` diisi "Bayar Hutang - [Keterangan]".

Dengan cara ini, file ekspor Anda hanya akan memiliki kolom-kolom standar seperti:
`No Invoice | Tanggal | Tipe | Keterangan | Nominal | Status | Pembuat | Cabang`

### C. Solusi Database: Database View (SQL View)
Jika ingin performa tinggi untuk ribuan data, buatlah **Database View** (misal: `v_all_transactions`) yang melakukan `UNION ALL` terhadap kolom-kolom esensial dari ketiga tabel tersebut.
*   **Kelebihan:** Sangat cepat, konsistensi kolom dijamin di level database, dan bisa langsung di-query oleh Laravel seolah-olah itu adalah satu tabel tunggal khusus untuk laporan.

## 3. Kesimpulan Analisa
*   **Untuk Rembush & Pengajuan:** Tetap di satu tabel `transactions` sudah cukup bagus karena alurnya (OCR/Approval) mirip. Namun, **normalisasi data** harus dilakukan di level aplikasi (menggunakan Presenter/Resource) sebelum dikirim ke file CSV.
*   **Untuk Semua Transaksi:** Gunakan **SQL View** atau **Unified Service** yang memetakan field-field berbeda ke dalam "Header" yang sama.

**Rekomendasi Akhir:** Jangan ubah tabelnya menjadi satu, tapi buatlah sebuah **Reporting Service** yang bertugas menstandarisasi output data dari berbagai tabel tersebut sebelum diekspor ke CSV/Excel agar kolomnya selalu konsisten.
