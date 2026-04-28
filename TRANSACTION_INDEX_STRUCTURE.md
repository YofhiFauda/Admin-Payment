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
    		    │   ├── filter-toolbar.blade.php
    		    │   ├── mobile-list.php  
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


## 📁 File Tree Form Transactions (MODULARIZED)

```text
resources/
├── js/
│   └── transactions/
│       ├── shared/                  <-- SHARED CORE LOGIC
│       │   ├── distribution.js      # Inti logika pembagian cabang (Equal, Percent, Manual)
│       │   └── helpers.js           # Fungsi utilitas (formatRupiah, unformat, escapeHtml)
│       │
│       ├── form-pengajuan/          <-- Scoped Pengajuan
│       │   ├── index.js             # Orchestrator Pengajuan
│       │   ├── uploader.js          # Logic Photo/PDF
│       │   ├── item-repeater.js     # Logic Item & Price Index
│       │   └── distribution.js      # Bridge ke shared/distribution.js
│       │
│       └── form-rembush/            <-- Scoped Rembush
│           ├── index.js             # Orchestrator Rembush
│           ├── uploader.js          # Logic Photo & OCR Preview
│           ├── item-repeater.js     # Logic Item & Subtotal
│           ├── technician.js        # Logic Rekening Teknisi
│           ├── payment-method.js    # Logic Info Pembayaran (Bank Vendor)
│           └── distribution.js      # Bridge ke shared/distribution.js
│
└── views/
    └── transactions/
        ├── form-pengajuan.blade.php
        ├── form-rembush.blade.php
        └── partials/
            └── forms/
                ├── shared/          <-- REUSABLE UI
                │   ├── photo-section.blade.php
                │   ├── branch-distribution.blade.php
                │   ├── summary-billing.blade.php
                │   └── image-viewer-modal.blade.php
                │
                ├── pengajuan/       <-- SPECIFIC UI
                │   ├── header.blade.php
                │   ├── item-repeater.blade.php
                │   └── item-template.blade.php
                │
                └── rembush/         <-- SPECIFIC UI
                    ├── header.blade.php
                    ├── technician-section.blade.php
                    ├── main-info.blade.php
                    ├── item-repeater.blade.php
                    └── item-template.blade.php
```

## 📄 Deskripsi File (JavaScript)

| File | Deskripsi |
| :--- | :--- |
| **index.js** | Orchestrator utama yang menginisialisasi semua modul. Menangani AI Autofill dan validasi final sebelum submit. |
| **shared/distribution.js** | **Core Logic**. Menghitung alokasi biaya antar cabang dengan dukungan toleransi selisih dan validasi 100%. |
| **shared/helpers.js** | Fungsi utilitas standar untuk pemformatan mata uang dan sanitasi input. |
| **uploader.js** | Mengelola upload, preview foto, dan integrasi dengan modal viewer. |
| **item-repeater.js** | Menangani baris barang dinamis. Versi Pengajuan mendukung *Price Index*, versi Rembush mendukung *OCR Mapping*. |
| **technician.js** | (Khusus Rembush) Mengelola pemilihan teknisi dan auto-load daftar rekening bank terkait. |
| **payment-method.js** | (Khusus Rembush) Menangani visibilitas informasi bank vendor berdasarkan metode pembayaran yang dipilih. |
| **distribution.js (Local)** | Bertindak sebagai **Bridge** ke core logic di `shared/`, memungkinkan kustomisasi parameter (seperti `tolerance` dan `isOptional`) per tipe form. |

## 📄 Deskripsi File (Blade Partials)

### 📂 Shared (Reusable)
*   **photo-section.blade.php**: Area upload foto dengan indikator status "Dari Upload Sebelumnya".
*   **branch-distribution.blade.php**: UI pemilihan cabang dan toggle metode alokasi.
*   **summary-billing.blade.php**: Black card ringkasan total dan rincian penagihan cabang.

### 📂 Form-Specific
*   **technician-section.blade.php**: (Rembush) Input "Atas Nama Teknisi" untuk admin.
*   **main-info.blade.php**: (Rembush) Field Vendor, Tanggal, Kategori, dan Metode Pencairan.
*   **item-template.blade.php**: Struktur HTML (Template Tag) untuk render baris barang secara dinamis via JS.

## 🚀 Key Improvements
1. **DRY Principle**: Logika distribusi yang kompleks dipusatkan di satu tempat (`shared/`).
2. **Feature Parity**: Tetap mempertahankan perilaku unik (Rembush: Cabang Opsional, Pengajuan: Cabang Wajib).
3. **Clean Imports**: Menggunakan ES6 Modules untuk ketergantungan antar file yang jelas.
4. **Maintenance Friendly**: Memperbaiki bug di sistem distribusi akan otomatis memperbaiki kedua form sekaligus.
