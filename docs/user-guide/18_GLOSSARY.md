# 📖 Glossary - Daftar Istilah

**Untuk Siapa:** Semua Pengguna  
**Waktu Baca:** 8 menit  
**Level:** Pemula

---

## 📋 Daftar Isi
- [Istilah Bisnis](#istilah-bisnis)
- [Istilah Teknis](#istilah-teknis)
- [Status Transaksi](#status-transaksi)
- [Role & Akses](#role--akses)
- [Singkatan](#singkatan)

---

## Istilah Bisnis

### A

**Activity Log**
- Log aktivitas user di sistem
- Mencatat semua aksi: create, update, approve, reject, delete
- Untuk audit trail & accountability

**Alokasi Cabang**
- Pembagian biaya transaksi ke beberapa cabang
- 3 metode: Equal Distribution, Percentage, Manual Amount
- Untuk tracking pengeluaran per cabang

**Anomali Harga**
- Harga yang melebihi referensi Price Index
- Terdeteksi otomatis oleh sistem
- Perlu review Owner

**Approval**
- Persetujuan transaksi oleh Admin/Atasan/Owner
- Multi-level: Admin/Atasan → Owner (jika ≥ 1 Jt)
- Setelah approved, transaksi bisa diproses pembayaran

**Auto-Reject**
- Penolakan otomatis oleh sistem
- Penyebab: Nota > 2 hari kalender
- Bisa di-override oleh Owner

---

### B

**Badge**
- Angka merah di icon notifikasi
- Menunjukkan jumlah notifikasi unread
- Update real-time

**Biaya Admin**
- Biaya transfer bank
- Bisa menyebabkan selisih nominal
- Solusi: Transfer lebih atau gunakan transfer gratis

**Bukti Bayar**
- Foto struk transfer atau bukti cash
- Wajib diupload untuk menyelesaikan transaksi
- Diverifikasi oleh AI (jika transfer)

---

### C

**Cabang**
- Unit bisnis perusahaan (Sudirman, Thamrin, Kuningan, dll.)
- Setiap transaksi dialokasikan ke cabang
- Untuk tracking pengeluaran per cabang

**Cash**
- Metode pembayaran tunai
- Perlu konfirmasi Teknisi via Telegram
- Untuk nominal kecil (< Rp 500.000)

**Cold Start**
- Kondisi saat belum ada data Price Index untuk item tertentu
- Terjadi untuk item baru yang belum pernah dibeli
- Sistem tidak bisa deteksi anomali harga

**Completed**
- Status transaksi yang sudah selesai
- Pembayaran sudah dilakukan
- Tidak bisa diedit lagi (untuk audit trail)

**Confidence**
- Tingkat keyakinan AI dalam membaca nota
- High (> 80%), Medium (60-80%), Low (< 60%)
- Low confidence perlu review manual lebih teliti

---

### D

**Dashboard**
- Halaman utama yang menampilkan overview keuangan
- Hanya untuk Admin/Atasan/Owner
- Menampilkan statistik, rincian biaya, transaksi pending

**DPP (Dasar Pengenaan Pajak)**
- Harga sebelum PPN
- DPP + PPN = Total
- Contoh: DPP Rp 4.500.000 + PPN Rp 495.000 = Total Rp 4.995.000

**Dual-Version System**
- Sistem tracking perubahan Pengajuan oleh Management
- 2 versi: Versi Pengaju (original) & Versi Management (edited)
- Untuk transparansi & audit trail

**Duplikasi**
- Nota yang sama diupload 2x
- Terdeteksi berdasarkan hash file
- Sistem otomatis reject

---

### F

**Flagged**
- Status transaksi yang di-flag karena selisih nominal
- Perlu review Owner untuk Force Approve
- Penyebab: Nominal struk ≠ nominal transaksi

**Force Approve**
- Fitur khusus Owner untuk approve transaksi yang di-flag
- Harus tulis alasan
- Tercatat di audit log

---

### G

**Gudang (Warehouse)**
- Modul untuk mencatat belanja gudang internal
- Tanpa OCR, tanpa konfirmasi Telegram
- Khusus untuk Admin/Atasan/Owner

---

### H

**Hutang Antar Cabang**
- Hutang satu cabang ke cabang lain
- Terjadi saat satu cabang bayar untuk cabang lain
- Perlu dilunasi dengan upload bukti transfer

---

### I

**In-App Notification**
- Notifikasi di dalam sistem (browser)
- Icon lonceng (🔔) di header
- Badge merah menunjukkan jumlah unread

**Invoice**
- Nota pembelian untuk Pengajuan
- Diupload setelah barang dibeli
- Untuk verifikasi & pembayaran

**IQR (Interquartile Range)**
- Algoritma untuk filter outlier harga
- Digunakan di Price Index System
- Membuang data harga yang tidak wajar

---

### K

**Kategori**
- Klasifikasi transaksi (Instalasi, Pembelian Alat, Maintenance, dll.)
- Untuk tracking pengeluaran per kategori
- Bisa dikelola oleh Admin/Atasan/Owner

**Konfirmasi**
- Konfirmasi penerimaan uang cash via Telegram
- Teknisi klik "✅ Ya, Sudah Terima"
- Setelah konfirmasi, status jadi Completed

---

### M

**Management**
- Atasan atau Owner
- Bisa edit Pengajuan (Dual-Version)
- Bisa approve transaksi

---

### N

**Nominal**
- Jumlah uang transaksi
- Dalam Rupiah (Rp)
- Termasuk PPN (jika ada)

**Nota**
- Bukti pembelian dari toko
- Difoto untuk upload ke sistem
- Dibaca oleh OCR AI (untuk Rembush)

---

### O

**OCR (Optical Character Recognition)**
- Teknologi AI untuk membaca teks dari foto
- Menggunakan Gemini AI
- Untuk auto-fill data transaksi

**Override**
- Fitur khusus Owner untuk approve transaksi yang auto-reject
- Harus tulis alasan
- Tercatat di audit log

---

### P

**Pending**
- Status transaksi yang menunggu approval
- Perlu action dari Admin/Atasan/Owner
- Target: Approve/reject dalam 1-2 hari kerja

**Pengajuan (Pembelian)**
- Sistem untuk mengajukan pembelian SEBELUM dibeli
- Approval dulu, beli kemudian
- Untuk pembelian besar/terencana

**Piutang**
- Cabang lain berhutang ke cabang ini
- Cabang ini akan menerima uang
- Kebalikan dari Hutang

**PPN (Pajak Pertambahan Nilai)**
- Pajak 11% dari DPP
- PPN = DPP × 11%
- Total = DPP + PPN

**Price Index**
- Referensi harga belanja otomatis
- Dihitung dari riwayat transaksi
- Untuk deteksi anomali harga

**Prive (Withdrawal)**
- Pencatatan pengambilan dana pribadi Owner
- Tracking sumber dana cabang
- Dengan bukti transfer

---

### R

**Rembush (Reimbursement)**
- Penggantian biaya yang sudah dikeluarkan
- Beli dulu → Upload nota → Dapat penggantian
- Dengan OCR AI untuk auto-fill

**Rekening Bank**
- Rekening bank/e-wallet cabang
- Dikelola oleh Owner
- Admin/Atasan read-only

**Rejected**
- Status transaksi yang ditolak
- Ada alasan penolakan
- Bisa submit ulang setelah diperbaiki

---

### S

**Selisih Nominal**
- Perbedaan nominal struk vs transaksi
- Penyebab: Biaya admin, salah transfer, AI salah baca
- Transaksi di-flag, perlu Force Approve

**Settlement Phase**
- Fase pelunasan hutang antar cabang
- Transaksi tidak bisa diedit saat settlement
- Untuk menjaga integritas data

**Snapshot**
- Salinan data original Pengajuan
- Disimpan saat submit
- Untuk perbandingan dengan versi Management

**Status**
- Kondisi transaksi saat ini
- Contoh: Pending, Approved, Completed, Rejected, dll.
- Update otomatis sesuai alur

**Struk Transfer**
- Bukti transfer dari m-banking/ATM
- Diupload sebagai bukti bayar
- Diverifikasi oleh AI

**Submitter**
- User yang submit transaksi
- Biasanya Teknisi
- Bisa juga Admin/Atasan/Owner

---

### T

**Telegram Bot**
- Bot Telegram untuk notifikasi
- Perlu setup dengan kode verifikasi
- Untuk notifikasi real-time & konfirmasi cash

**Transfer Bank**
- Metode pembayaran via transfer
- Perlu upload struk transfer
- Diverifikasi oleh AI

**Transaksi**
- Record pembelian/pengeluaran
- 3 tipe: Rembush, Pengajuan, Gudang
- Dengan alur approval & pembayaran

---

### V

**Vendor**
- Toko/supplier tempat belanja
- Dicatat di setiap transaksi
- Untuk tracking & analisis

**Verifikasi AI**
- Pengecekan nominal struk vs transaksi oleh AI
- Untuk mencegah kesalahan transfer
- Jika tidak cocok, transaksi di-flag

---

### W

**Waiting Payment**
- Status transaksi yang menunggu pembayaran
- Sudah disetujui, tinggal upload bukti bayar
- Setelah bukti upload, status jadi Completed

**WebSocket**
- Teknologi untuk notifikasi real-time
- Menggunakan Laravel Reverb
- Untuk update dashboard & notifikasi

---

## Istilah Teknis

### A

**API (Application Programming Interface)**
- Interface untuk komunikasi antar sistem
- Contoh: OCR API, Telegram API
- Untuk integrasi eksternal

**Audit Trail**
- Jejak aktivitas user di sistem
- Semua aksi tercatat (who, what, when)
- Untuk accountability & compliance

---

### B

**Badge**
- Indikator visual (angka merah)
- Menunjukkan jumlah item baru/unread
- Update real-time

**Broadcast**
- Pengiriman notifikasi ke banyak user sekaligus
- Via WebSocket (Laravel Reverb)
- Untuk update real-time

---

### C

**Cache**
- Penyimpanan sementara data
- Untuk mempercepat loading
- Bisa di-clear jika ada masalah

**CRUD**
- Create, Read, Update, Delete
- Operasi dasar database
- Untuk manajemen data

---

### D

**Database**
- Penyimpanan data sistem
- Menggunakan MySQL
- Semua transaksi tersimpan di sini

---

### E

**Export**
- Mengunduh data ke file Excel
- Untuk laporan & analisis
- Bisa filter periode & status

---

### F

**Filter**
- Penyaringan data berdasarkan kriteria
- Contoh: Filter status, tipe, tanggal, cabang
- Untuk mencari data spesifik

---

### H

**Hash**
- Kode unik untuk identifikasi file
- Digunakan untuk deteksi duplikasi
- Tidak bisa di-reverse

**Horizon**
- Dashboard untuk monitoring queue
- Untuk Admin/Developer
- Melihat job yang sedang diproses

---

### L

**Log**
- Catatan aktivitas sistem
- Untuk debugging & audit
- Tersimpan di file log

---

### N

**n8n**
- Platform workflow automation
- Untuk OCR processing
- Integrasi dengan Gemini AI

---

### O

**Outlier**
- Data yang jauh dari rata-rata
- Difilter oleh algoritma IQR
- Untuk akurasi Price Index

---

### Q

**Queue**
- Antrian job yang diproses background
- Contoh: OCR processing
- Menggunakan Redis

---

### R

**Real-time**
- Update langsung tanpa refresh
- Menggunakan WebSocket
- Untuk notifikasi & dashboard

**Redis**
- Database in-memory untuk cache & queue
- Sangat cepat
- Untuk performa optimal

**Reverb**
- WebSocket server Laravel
- Untuk notifikasi real-time
- Menggantikan polling

---

### S

**Session**
- Sesi login user
- Expired setelah 2 jam tidak aktif
- Untuk keamanan

---

### W

**Webhook**
- URL untuk menerima callback
- Contoh: OCR callback dari n8n
- Untuk integrasi asynchronous

---

## Status Transaksi

| Status | Arti | Warna |
|--------|------|-------|
| **Pending** | Menunggu approval | 🟡 Kuning |
| **Approved** | Disetujui Admin, tunggu Owner (≥ 1 Jt) | 🔵 Biru |
| **Waiting Payment** | Menunggu pembayaran | 🟠 Orange |
| **Completed** | Selesai | 🟢 Hijau |
| **Rejected** | Ditolak | 🔴 Merah |
| **Flagged** | Di-flag (selisih nominal) | 🟣 Ungu |
| **Auto-Reject** | Ditolak otomatis (nota > 2 hari) | ⚫ Hitam |

---

## Role & Akses

| Role | Level | Akses |
|------|-------|-------|
| **Teknisi** | 1 | Submit transaksi, lihat transaksi sendiri |
| **Admin** | 2 | Approve < 1 Jt, upload bukti bayar, kelola master data |
| **Atasan** | 3 | Approve < 1 Jt, full edit Pengajuan, monitoring |
| **Owner** | 4 | Full access, approve semua, Force Approve, Override |

---

## Singkatan

| Singkatan | Kepanjangan | Arti |
|-----------|-------------|------|
| **OCR** | Optical Character Recognition | Teknologi baca teks dari foto |
| **AI** | Artificial Intelligence | Kecerdasan buatan |
| **DPP** | Dasar Pengenaan Pajak | Harga sebelum PPN |
| **PPN** | Pajak Pertambahan Nilai | Pajak 11% |
| **IQR** | Interquartile Range | Algoritma filter outlier |
| **API** | Application Programming Interface | Interface komunikasi sistem |
| **CRUD** | Create, Read, Update, Delete | Operasi dasar database |
| **REM** | Rembush | Prefix ID transaksi Rembush |
| **PEN** | Pengajuan | Prefix ID transaksi Pengajuan |
| **GUD** | Gudang | Prefix ID transaksi Gudang |
| **INV** | Invoice | Nota pembelian |
| **GP** | Gudang/Prive | Kategori transaksi |
| **UP** | Upload | Aksi upload file |
| **PL** | Pelunasan | Pelunasan hutang |

---

## 📚 Dokumentasi Terkait

- 📖 [Pengenalan Sistem](00_PENGENALAN_SISTEM.md)
- 🚀 [Cara Memulai](01_MEMULAI.md)
- 👥 [Peran Pengguna](02_PERAN_PENGGUNA.md)
- 💰 [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md)
- 📝 [Panduan Pengajuan](04_PENGAJUAN_PEMBELIAN.md)
- ❓ [FAQ Umum](17_FAQ_UMUM.md)

---

## 📞 Butuh Bantuan?

Jika ada istilah yang tidak ada di glossary ini:

- 💬 **Telegram:** @WhusnetSupport
- 📧 **Email:** support@whusnet.com
- 📱 **WhatsApp:** +62 xxx-xxxx-xxxx
- 🕐 **Jam Operasional:** Senin-Jumat, 08:00-17:00 WIB

---

**Pahami istilah untuk menggunakan sistem dengan lebih baik!** 📖✨

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
