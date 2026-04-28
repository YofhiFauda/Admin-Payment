# Refactoring Structure: Transactions Module

This document outlines the proposed clean-code structure for `resources/views/transactions` to improve readability and maintainability.

## 📁 File Tree transactions/index

```text
resources/
├── js/
│   └── transactions/
│       ├── main.js             
│       ├── config.js          
│       ├── modal-export-excel.js     
│       ├── modals.js      
│       ├── payments.js           
│       ├── realtime.js          
│       ├── rendering.js           
│       ├── search-engine.js 
│       └── utils.js  
│
└── views/
    └── transactions/
        ├── index.blade.php 
        └── partials/
            └── 📂 partials/
    		    ├── 📂 index/
    		    │   ├── desktop-table.blade.php 
   		        ├── filter-toolbar.blade.php
   		        ├── mobile-list.php  
    		    │   ├── pagination.blade.php    
    		    │   └── status-tabs.blade.php     
    		    │
    		    └── 📂 modals/
        		    ├── branch-debt-modal.blade.php   
        		    ├── export-excel-modal.blade.php
        		    ├── force-approve-modal.blade.php
        		    ├── image-pdf-view-modal.blade.php
        		    ├── override-modal.blade.php
        		    ├── payment-upload-modal.blade.php
        		    ├── reject-modal.blade.php
        		    └── view-detail-modal.blade.php

```

## 📄 Deskripsi File (JavaScript - Index)

| File | Deskripsi |
| :--- | :--- |
| **main.js** | Entry point utama untuk halaman daftar transaksi. Mengoordinasikan inisialisasi filter, modal, dan realtime engine. |
| **config.js** | Single source of truth untuk konfigurasi API, konstanta status, warna, dan threshold pencarian (Hybrid Search). |
| **search-engine.js** | Engine inti untuk pencarian. Secara otomatis beralih antara Client-side (Data < 5k) dan Server-side (Data >= 5k). |
| **payments.js** | Mengelola logika pengunggahan bukti bayar, baik metode Transfer maupun Tunai. |
| **modals.js** | Handler global untuk membuka/menutup berbagai modal fungsional di halaman index. |
| **realtime.js** | Integrasi Laravel Reverb untuk pembaruan data secara instan tanpa reload halaman. |
| **modal-export-excel.js** | Logika pemrosesan ekspor data ke Excel dengan filter yang aktif di UI. |

## 📄 Deskripsi File (Blade Partials - Index)

### 📂 Index (Layout Daftar)
*   **filter-toolbar.blade.php**: Toolbar atas yang berisi input pencarian, filter cabang, dan picker rentang tanggal.
*   **status-tabs.blade.php**: Navigasi tab untuk memfilter transaksi berdasarkan status (Menunggu, Disetujui, Selesai, dll).
*   **desktop-table.blade.php**: Layout tabel responsif untuk tampilan layar desktop/tablet.
*   **mobile-list.blade.php**: Tampilan berbasis kartu yang dioptimalkan khusus untuk layar ponsel.
*   **pagination.blade.php**: Komponen navigasi halaman untuk dataset yang besar.

### 📂 Modals (Fungsionalitas)
*   **view-detail-modal.blade.php**: Detail lengkap transaksi termasuk histori, catatan, dan rincian alokasi cabang.
*   **payment-upload-modal.blade.php**: Interface untuk upload bukti pembayaran (mendukung transfer & cash).
*   **branch-debt-modal.blade.php**: Interface khusus untuk pelunasan hutang antar cabang.
*   **override-modal.blade.php**: Fitur manajemen untuk merevisi data yang diajukan teknisi sebelum pembayaran.
*   **image-pdf-view-modal.blade.php**: Viewer ringan untuk melihat nota atau bukti bayar langsung dari tabel.
*   **export-excel-modal.blade.php**: Opsi konfigurasi kolom dan filter sebelum melakukan ekspor laporan.
*   **reject-modal.blade.php**: Dialog untuk memberikan alasan penolakan transaksi oleh atasan/owner.



## 📁 File Tree transactions/form-pengajuan (MODULARIZED)

```text
resources/
├── js/
│   └── transactions/
│       └── form-pengajuan/          <-- Scoped JS modules
│           ├── index.js             <-- Main orchestration & event binding
│           ├── uploader.js          <-- Photo/PDF upload & preview logic
│           ├── item-repeater.js     <-- Dynamic items & Price Index logic
│           ├── distribution.js      <-- Branch allocation & validation logic
│           └── helpers.js           <-- formatNumber, unformatNumber, formatRupiah, escapeHtml
└── views/
    └── transactions/
        ├── form-pengajuan.blade.php <-- Entry point (Cleaned up)
        └── partials/
            └── forms/
                ├── shared/ (REUSABLE COMPONENTS)
                │   ├── photo-section.blade.php       # UI Upload & Drag-drop
                │   ├── branch-distribution.blade.php  # UI Pill Cabang & Alokasi
                │   ├── summary-billing.blade.php      # Kartu Ringkasan (Black Card)
                │   └── image-viewer-modal.blade.php   # Modal Preview Foto/PDF global
                └── pengajuan/ (SPECIFIC COMPONENTS)
                    ├── header.blade.php
                    ├── item-repeater.blade.php        # Kontainer Repeater Barang
                    └── item-template.blade.php        # Template baris (Template Tag)
```

## 📄 Deskripsi File (JavaScript)

| File | Deskripsi |
| :--- | :--- |
| **index.js** | Orchestrator utama yang menginisialisasi semua modul (Uploader, Distribution, ItemRepeater). Menangani event level form seperti validasi sebelum submit. |
| **uploader.js** | Mengelola logika upload foto/PDF, fitur drag-and-drop, dan integrasi dengan modal Image Viewer untuk preview dokumen. |
| **item-repeater.js** | Logika inti untuk baris barang dinamis. Menangani autocomplete nama barang, perhitungan subtotal/total otomatis, dan pengecekan anomali harga (AI Price Index). |
| **distribution.js** | Mengatur pembagian biaya antar cabang (Bagi Rata, Persentase, Manual) dan memastikan total alokasi sesuai dengan total transaksi. |
| **helpers.js** | Kumpulan fungsi utilitas untuk pemformatan angka Rupiah, unformat string ke number, dan keamanan (escape HTML). |

## 📄 Deskripsi File (Blade Partials)

### 📂 Shared (Dapat digunakan di semua jenis transaksi)
*   **photo-section.blade.php**: Komponen UI untuk area upload foto referensi dengan dukungan preview langsung.
*   **branch-distribution.blade.php**: UI untuk pemilihan cabang (pills) dan panel konfigurasi metode pembagian biaya.
*   **summary-billing.blade.php**: Kartu hitam ringkasan akhir yang menampilkan total biaya dan rincian penagihan per cabang.
*   **image-viewer-modal.blade.php**: Modal global untuk memperbesar foto atau melihat dokumen PDF secara full-screen.

### 📂 Pengajuan (Khusus Form Pengajuan Beli)
*   **header.blade.php**: Bagian judul form dan instruksi pengisian awal.
*   **item-repeater.blade.php**: Kontainer utama tempat kartu barang akan diinjeksi oleh JS dan tombol "Tambah Barang".
*   **item-template.blade.php**: Struktur HTML mentah (Template Tag) untuk satu baris barang yang akan digandakan oleh `item-repeater.js`.

## 🚀 Key Improvements
1. **DRY Principle**: Komponen shared (Distribution, Billing, Uploader) dipusatkan agar konsisten di seluruh aplikasi.
2. **ES6 Modules**: Logika JS dipecah berdasarkan tanggung jawab, menghindari file ribuan baris yang sulit dimaintain.
3. **Price Index Support**: Terintegrasi dengan sistem pengecekan harga pasar secara real-time.
4. **Validation Logic**: Memastikan akurasi finansial hingga digit terakhir sebelum data masuk ke database.


