# Requirements Document

## Introduction

File `resources/views/transactions/index.blade.php` saat ini memiliki **5.308 baris** kode yang menggabungkan seluruh fitur halaman daftar transaksi dalam satu file monolitik. File ini mencakup toolbar filter responsif (3 layout breakpoint), 3 singleton popover, tab status, tabel desktop, card mobile, footer pagination, 7 modal interaktif, dan lebih dari 3.800 baris JavaScript inline.

Refactoring ini bertujuan memecah file tersebut menjadi komponen/partial Blade yang modular, dengan tanggung jawab tunggal per file, tanpa mengubah tampilan atau fungsionalitas yang sudah ada.

---

## Glossary

- **Blade_Partial**: File Blade yang di-include menggunakan `@include()` atau `@includeIf()`, tidak memiliki layout sendiri.
- **Blade_Component**: Komponen Blade berbasis class atau anonymous yang dapat menerima props/slot.
- **Partial_File**: File `.blade.php` yang merupakan potongan dari halaman utama, disimpan dalam subfolder.
- **Index_View**: File utama `resources/views/transactions/index.blade.php` yang menjadi entry point halaman.
- **Toolbar**: Area header halaman yang berisi search, filter cabang, filter kategori, filter tanggal, dan tombol tipe transaksi.
- **Popover**: Dropdown overlay yang muncul saat tombol filter diklik, berisi daftar pilihan dengan checkbox.
- **Status_Tab**: Navigasi tab horizontal yang memfilter transaksi berdasarkan status.
- **Table_View**: Tampilan tabel transaksi untuk layar desktop (≥ md breakpoint).
- **Card_View**: Tampilan kartu transaksi untuk layar mobile (< md breakpoint).
- **Modal**: Dialog overlay yang muncul di atas halaman untuk aksi tertentu.
- **JS_Module**: Blok JavaScript yang dikelompokkan berdasarkan tanggung jawab fungsional.
- **Breakpoint**: Titik lebar layar yang menentukan layout yang ditampilkan (mobile < md < lg < 1510px+).

---

## Requirements

### Requirement 1: Pemecahan Struktur Folder dan Penamaan Komponen

**User Story:** Sebagai developer, saya ingin ada struktur folder yang jelas dan konsisten untuk partial Blade transaksi, sehingga saya dapat menemukan dan menavigasi komponen dengan cepat.

#### Acceptance Criteria

1. THE Index_View SHALL menggunakan `@include()` untuk menyertakan setiap Partial_File yang telah dipisahkan.
2. THE Partial_File SHALL disimpan dalam folder `resources/views/transactions/partials/`.
3. THE Partial_File untuk modal SHALL disimpan dalam subfolder `resources/views/transactions/partials/modals/`.
4. THE Partial_File untuk JavaScript SHALL disimpan dalam subfolder `resources/views/transactions/partials/scripts/`.
5. THE Partial_File SHALL menggunakan penamaan kebab-case dengan format `[nama-komponen].blade.php`.
6. THE Index_View SHALL tetap menggunakan `@extends('layouts.app')` dan `@section('content')` sebagai wrapper utama.
7. WHEN sebuah Partial_File membutuhkan variabel dari controller, THE Index_View SHALL meneruskan variabel tersebut melalui parameter kedua `@include('path', ['var' => $var])` atau menggunakan variabel yang sudah tersedia di scope view.

---

### Requirement 2: Pemisahan Header Toolbar

**User Story:** Sebagai developer, saya ingin toolbar filter dipisahkan ke file tersendiri, sehingga perubahan pada layout filter tidak mempengaruhi bagian lain halaman.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan toolbar melalui `@include('transactions.partials.toolbar')`.
2. THE Partial_File `toolbar.blade.php` SHALL memuat ketiga layout toolbar: Desktop (1-row/2-row), Tablet, dan Mobile.
3. THE Partial_File `toolbar.blade.php` SHALL memuat tombol tipe filter (Semua, Rembush, Pengajuan, Gudang) untuk semua breakpoint.
4. THE Partial_File `toolbar.blade.php` SHALL mempertahankan semua class CSS, ID elemen, dan atribut `data-target` yang digunakan oleh JavaScript.
5. WHEN role pengguna adalah `teknisi`, THE Partial_File `toolbar.blade.php` SHALL menyembunyikan filter cabang, kategori, dan tanggal sesuai kondisi `@if(auth()->user()->role !== 'teknisi')` yang sudah ada.

---

### Requirement 3: Pemisahan Singleton Popovers

**User Story:** Sebagai developer, saya ingin ketiga popover filter (cabang, kategori, tanggal) dipisahkan ke file tersendiri, sehingga logika popover mudah ditemukan dan dimodifikasi.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan popover melalui `@include('transactions.partials.filter-popovers')`.
2. THE Partial_File `filter-popovers.blade.php` SHALL memuat popover cabang (`#menu-filter-branch`), popover kategori (`#menu-filter-category`), dan popover tanggal (`#menu-filter-date`) dalam satu file.
3. THE Partial_File `filter-popovers.blade.php` SHALL mempertahankan semua ID elemen, class JavaScript hooks (`js-filter-*`, `filter-popover`, `date-preset-btn`, dll.), dan struktur HTML yang digunakan oleh event listener JavaScript.
4. THE Partial_File `filter-popovers.blade.php` SHALL menerima variabel `$branches` dan `$categories` dari scope view untuk merender daftar pilihan.
5. WHEN role pengguna adalah `teknisi`, THE Partial_File `filter-popovers.blade.php` SHALL tidak dirender (dibungkus kondisi yang sama dengan di Index_View).

---

### Requirement 4: Pemisahan Status Tabs

**User Story:** Sebagai developer, saya ingin navigasi tab status dipisahkan ke file tersendiri, sehingga penambahan atau perubahan tab status tidak memerlukan pencarian di file besar.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan tab status melalui `@include('transactions.partials.status-tabs')`.
2. THE Partial_File `status-tabs.blade.php` SHALL memuat semua 8 tab status: Semua, Pending, Auto Reject, Bermasalah, Menunggu Pembayaran, Menunggu Approve Owner, Selesai, Ditolak.
3. THE Partial_File `status-tabs.blade.php` SHALL menerima variabel `$stats` dari scope view untuk menampilkan jumlah transaksi per tab.
4. THE Partial_File `status-tabs.blade.php` SHALL mempertahankan ID `search-results-container` pada wrapper div-nya karena digunakan oleh JavaScript untuk update HTMX/AJAX.
5. THE Partial_File `status-tabs.blade.php` SHALL mempertahankan logika active state berdasarkan `request('status', 'all')`.

---

### Requirement 5: Pemisahan Table View (Desktop)

**User Story:** Sebagai developer, saya ingin tampilan tabel desktop dipisahkan ke file tersendiri, sehingga perubahan kolom atau struktur tabel mudah dilakukan.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan tabel desktop melalui `@include('transactions.partials.table-view')`.
2. THE Partial_File `table-view.blade.php` SHALL memuat seluruh struktur `<table>` beserta header kolom dan baris data transaksi.
3. THE Partial_File `table-view.blade.php` SHALL menerima variabel `$transactions` dari scope view.
4. THE Partial_File `table-view.blade.php` SHALL mempertahankan semua class CSS, atribut `data-*`, dan event handler inline yang digunakan untuk membuka modal detail.
5. THE Partial_File `table-view.blade.php` SHALL mempertahankan kondisi visibilitas berbasis role (`auth()->user()->role`) untuk kolom atau aksi tertentu.

---

### Requirement 6: Pemisahan Card View (Mobile)

**User Story:** Sebagai developer, saya ingin tampilan kartu mobile dipisahkan ke file tersendiri, sehingga perubahan desain kartu tidak bercampur dengan logika tabel.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan card view melalui `@include('transactions.partials.card-view')`.
2. THE Partial_File `card-view.blade.php` SHALL memuat container `#mobile-container` beserta struktur kartu transaksi.
3. THE Partial_File `card-view.blade.php` SHALL mempertahankan ID `mobile-container` karena digunakan oleh JavaScript untuk render dinamis.
4. THE Partial_File `card-view.blade.php` SHALL mempertahankan semua atribut `data-*` dan class yang digunakan oleh JavaScript untuk interaksi kartu.

---

### Requirement 7: Pemisahan Footer dan Pagination

**User Story:** Sebagai developer, saya ingin area footer (info jumlah data, tombol export, pagination) dipisahkan ke file tersendiri, sehingga perubahan pada pagination tidak mempengaruhi komponen lain.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan footer melalui `@include('transactions.partials.footer-pagination')`.
2. THE Partial_File `footer-pagination.blade.php` SHALL memuat informasi jumlah data, tombol export (jika bukan role `teknisi`), dan kontrol pagination.
3. THE Partial_File `footer-pagination.blade.php` SHALL menerima variabel `$transactions` (objek paginator) dari scope view.
4. THE Partial_File `footer-pagination.blade.php` SHALL mempertahankan tombol export dengan ID dan event handler yang digunakan untuk membuka export modal.

---

### Requirement 8: Pemisahan Export Modal

**User Story:** Sebagai developer, saya ingin Export Modal dipisahkan ke file tersendiri, sehingga logika filter laporan Excel mudah ditemukan dan dimodifikasi secara independen.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan export modal melalui `@include('transactions.partials.modals.export-modal')`.
2. THE Partial_File `export-modal.blade.php` SHALL memuat seluruh HTML modal dengan ID `export-modal`.
3. THE Partial_File `export-modal.blade.php` SHALL menerima variabel `$branches` dari scope view untuk merender pilihan cabang.
4. THE Partial_File `export-modal.blade.php` SHALL mempertahankan semua ID elemen form, select, dan button yang digunakan oleh JavaScript (`closeExportModal`, `submitExport`, dll.).
5. WHEN role pengguna adalah `teknisi`, THE Partial_File `export-modal.blade.php` SHALL tidak dirender (kondisi `@if(auth()->user()->role !== 'teknisi')` dipertahankan).

---

### Requirement 9: Pemisahan View Detail Modal

**User Story:** Sebagai developer, saya ingin View Detail Modal dipisahkan ke file tersendiri, sehingga perubahan pada tampilan detail transaksi tidak mempengaruhi modal lain.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan view detail modal melalui `@push('modals')` dan `@include('transactions.partials.modals.view-detail-modal')`.
2. THE Partial_File `view-detail-modal.blade.php` SHALL memuat seluruh HTML modal dengan ID `view-modal`.
3. THE Partial_File `view-detail-modal.blade.php` SHALL mempertahankan semua ID elemen (`v-items-wrap`, `v-items-table-container`, `v-items-div-container`, dll.) yang digunakan oleh JavaScript untuk populate data.
4. THE Partial_File `view-detail-modal.blade.php` SHALL mempertahankan semua tombol aksi (reject, override, force approve, payment upload) beserta atribut `data-*` dan event handler-nya.

---

### Requirement 10: Pemisahan Image/PDF Viewer Modal

**User Story:** Sebagai developer, saya ingin Image/PDF Viewer Modal dipisahkan ke file tersendiri, sehingga komponen viewer dapat digunakan ulang di halaman lain jika diperlukan.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan image viewer modal melalui `@push('modals')` dan `@include('transactions.partials.modals.image-viewer-modal')`.
2. THE Partial_File `image-viewer-modal.blade.php` SHALL memuat seluruh HTML modal dengan ID `image-viewer`.
3. THE Partial_File `image-viewer-modal.blade.php` SHALL mempertahankan semua ID elemen (`viewer-img`, `viewer-iframe`, `viewer-footer`, `viewer-pdf-link`, dll.) yang digunakan oleh JavaScript.

---

### Requirement 11: Pemisahan Reject Modal

**User Story:** Sebagai developer, saya ingin Reject Modal dipisahkan ke file tersendiri, sehingga perubahan pada alur penolakan transaksi mudah dilakukan.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan reject modal melalui `@push('modals')` dan `@include('transactions.partials.modals.reject-modal')`.
2. THE Partial_File `reject-modal.blade.php` SHALL memuat seluruh HTML modal dengan ID `reject-modal`.
3. THE Partial_File `reject-modal.blade.php` SHALL mempertahankan semua ID elemen form, input alasan penolakan, dan tombol konfirmasi yang digunakan oleh JavaScript.

---

### Requirement 12: Pemisahan Override Modal

**User Story:** Sebagai developer, saya ingin Override Modal dipisahkan ke file tersendiri, sehingga logika request override AI dapat dikelola secara independen.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan override modal melalui `@push('modals')` dan `@include('transactions.partials.modals.override-modal')`.
2. THE Partial_File `override-modal.blade.php` SHALL memuat seluruh HTML modal dengan ID `override-modal`.
3. THE Partial_File `override-modal.blade.php` SHALL mempertahankan semua ID elemen dan atribut yang digunakan oleh JavaScript untuk submit request override.

---

### Requirement 13: Pemisahan Force Approve Modal

**User Story:** Sebagai developer, saya ingin Force Approve Modal dipisahkan ke file tersendiri, sehingga perubahan pada alur force approve tidak bercampur dengan modal lain.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan force approve modal melalui `@push('modals')` dan `@include('transactions.partials.modals.force-approve-modal')`.
2. THE Partial_File `force-approve-modal.blade.php` SHALL memuat seluruh HTML modal dengan ID `force-approve-modal`.
3. THE Partial_File `force-approve-modal.blade.php` SHALL mempertahankan semua ID elemen dan tombol konfirmasi yang digunakan oleh JavaScript.

---

### Requirement 14: Pemisahan Payment Upload Modal

**User Story:** Sebagai developer, saya ingin Payment Upload Modal dipisahkan ke file tersendiri, sehingga logika upload bukti pembayaran yang kompleks mudah ditemukan dan dimodifikasi.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan payment upload modal melalui `@push('modals')` dan `@include('transactions.partials.modals.payment-modal')`.
2. THE Partial_File `payment-modal.blade.php` SHALL memuat seluruh HTML modal dengan ID `payment-modal` (~275 baris HTML).
3. THE Partial_File `payment-modal.blade.php` SHALL mempertahankan semua ID elemen (`p-items-wrap`, `p-items-table-container`, `p-items-div-container`, `debt-preview-section`, dll.) yang digunakan oleh JavaScript.
4. THE Partial_File `payment-modal.blade.php` SHALL mempertahankan semua input field untuk multi sumber dana, upload file, dan debt preview.

---

### Requirement 15: Pemisahan Branch Debt Settlement Modal

**User Story:** Sebagai developer, saya ingin Branch Debt Settlement Modal dipisahkan ke file tersendiri, sehingga fitur pembayaran hutang antar cabang dapat dikelola secara independen.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan branch debt modal melalui `@push('modals')` dan `@include('transactions.partials.modals.branch-debt-modal')`.
2. THE Partial_File `branch-debt-modal.blade.php` SHALL memuat seluruh HTML modal dengan ID `branch-debt-modal`.
3. THE Partial_File `branch-debt-modal.blade.php` SHALL mempertahankan semua ID elemen dan struktur form yang digunakan oleh JavaScript untuk submit pembayaran hutang.

---

### Requirement 16: Pemisahan JavaScript ke Modul Terpisah

**User Story:** Sebagai developer, saya ingin JavaScript (~3.800 baris) dipecah menjadi modul-modul yang lebih kecil berdasarkan tanggung jawab fungsional, sehingga debugging dan pengembangan fitur baru lebih mudah.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan setiap JS_Module melalui `@push('scripts')` dan `@include('transactions.partials.scripts.[nama-modul]')`.
2. THE JS_Module `filter-logic.blade.php` SHALL memuat semua logika JavaScript untuk filter (search, branch, category, date popover, URL sync).
3. THE JS_Module `table-render.blade.php` SHALL memuat semua logika JavaScript untuk render tabel desktop dan kartu mobile.
4. THE JS_Module `modal-view-detail.blade.php` SHALL memuat semua logika JavaScript untuk membuka, mengisi, dan menutup View Detail Modal.
5. THE JS_Module `modal-payment.blade.php` SHALL memuat semua logika JavaScript untuk Payment Upload Modal termasuk kalkulasi debt dan multi sumber dana.
6. THE JS_Module `modal-actions.blade.php` SHALL memuat semua logika JavaScript untuk Reject Modal, Override Modal, Force Approve Modal, dan Branch Debt Modal.
7. THE JS_Module `modal-export.blade.php` SHALL memuat semua logika JavaScript untuk Export Modal.
8. THE JS_Module `image-viewer.blade.php` SHALL memuat semua logika JavaScript untuk Image/PDF Viewer Modal.
9. THE JS_Module `init.blade.php` SHALL memuat inisialisasi global (CSRF token, Lucide icons, event listener utama) dan dipanggil terakhir.
10. WHEN sebuah JS_Module membutuhkan variabel PHP, THE JS_Module SHALL menerimanya melalui variabel Blade yang sudah tersedia di scope view (seperti `{{ csrf_token() }}`).

---

### Requirement 17: Pemisahan Styles

**User Story:** Sebagai developer, saya ingin CSS inline yang ada di `@section('styles')` dipisahkan ke file tersendiri, sehingga styling dapat dikelola secara terpusat.

#### Acceptance Criteria

1. THE Index_View SHALL menyertakan styles melalui `@section('styles')` dan `@include('transactions.partials.styles')`.
2. THE Partial_File `styles.blade.php` SHALL memuat semua CSS yang saat ini ada di `@section('styles')` pada Index_View (sekitar 60 baris CSS).
3. THE Partial_File `styles.blade.php` SHALL mempertahankan semua selector CSS yang digunakan oleh komponen HTML di partial lain.

---

### Requirement 18: Jaminan Zero Regression (Tidak Ada Perubahan Fungsionalitas)

**User Story:** Sebagai QA engineer, saya ingin memastikan bahwa refactoring tidak mengubah tampilan atau perilaku halaman sama sekali, sehingga pengguna tidak merasakan perbedaan apapun.

#### Acceptance Criteria

1. WHEN halaman transaksi diakses setelah refactoring, THE Index_View SHALL menghasilkan HTML output yang identik secara fungsional dengan HTML output sebelum refactoring.
2. THE Index_View SHALL mempertahankan semua ID elemen HTML yang digunakan sebagai JavaScript selector (tidak ada ID yang berubah atau hilang).
3. THE Index_View SHALL mempertahankan semua class CSS yang digunakan sebagai JavaScript selector (class dengan prefix `js-` tidak boleh berubah).
4. WHEN semua Partial_File di-include, THE Index_View SHALL mempertahankan urutan render yang sama: Toolbar → Popovers → Status Tabs → Table View → Card View → Footer → Modals → Styles → Scripts.
5. THE Index_View SHALL mempertahankan semua variabel PHP yang diteruskan dari controller (`$transactions`, `$branches`, `$categories`, `$stats`) dan dapat diakses oleh semua Partial_File.
6. IF sebuah Partial_File tidak dapat menemukan variabel yang dibutuhkan, THEN THE Partial_File SHALL menggunakan nilai default yang aman (null coalescing `?? []` atau `?? 0`) untuk mencegah error.
7. WHEN refactoring selesai, THE Index_View SHALL memiliki tidak lebih dari 60 baris kode (hanya berisi `@extends`, `@section`, dan deretan `@include`).
