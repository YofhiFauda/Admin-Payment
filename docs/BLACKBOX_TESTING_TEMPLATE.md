# Template Pengujian BlackBox Testing
## Sistem Admin Payment - Whusnet

**Tanggal Pembuatan:** 29 Mei 2026  
**Versi Aplikasi:** Laravel 12 Admin Payment  
**Metode:** BlackBox Testing (Positive & Negative Scenarios)

---

## Daftar Isi

1. [Modul Autentikasi (Login/Logout)](#1-modul-autentikasi)
2. [Modul Dashboard](#2-modul-dashboard)
3. [Modul Transaksi - Rembush](#3-modul-transaksi-rembush)
4. [Modul Transaksi - Pengajuan](#4-modul-transaksi-pengajuan)
5. [Modul Transaksi - Pembelian (Gudang)](#5-modul-transaksi-pembelian)
6. [Modul Manajemen Status Transaksi](#6-modul-manajemen-status-transaksi)
7. [Modul Export Transaksi](#7-modul-export-transaksi)
8. [Modul Pengeluaran Lain - Bayar Hutang](#8-modul-pengeluaran-lain-bayar-hutang)
9. [Modul Pengeluaran Lain - Piutang Usaha](#9-modul-pengeluaran-lain-piutang-usaha)
10. [Modul Pengeluaran Lain - Prive](#10-modul-pengeluaran-lain-prive)
11. [Modul Pengeluaran Lain - Gaji](#11-modul-pengeluaran-lain-gaji)
12. [Modul Manajemen User](#12-modul-manajemen-user)
13. [Modul Manajemen Cabang (Branch)](#13-modul-manajemen-cabang)
14. [Modul Rekening Bank Cabang](#14-modul-rekening-bank-cabang)
15. [Modul Rekening Bank User](#15-modul-rekening-bank-user)
16. [Modul Kategori Transaksi](#16-modul-kategori-transaksi)
17. [Modul Price Index](#17-modul-price-index)
18. [Modul Anomali Harga](#18-modul-anomali-harga)
19. [Modul Notifikasi](#19-modul-notifikasi)
20. [Modul Activity Log](#20-modul-activity-log)

---

## 1. Modul Autentikasi

### 1.1 Login

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| A-01 | Login dengan data valid (role teknisi) | Positive | Email: teknisi@test.com, Password: valid, Role: teknisi | 1. Buka halaman /login 2. Pilih role "Teknisi" 3. Isi email & password valid 4. Klik Login | Redirect ke halaman /transactions/create | |
| A-02 | Login dengan data valid (role admin) | Positive | Email: admin@test.com, Password: valid, Role: admin | 1. Buka halaman /login 2. Pilih role "Admin" 3. Isi email & password valid 4. Klik Login | Redirect ke halaman /dashboard | |
| A-03 | Login dengan data valid (role atasan) | Positive | Email: atasan@test.com, Password: valid, Role: atasan | 1. Buka halaman /login 2. Pilih role "Atasan" 3. Isi email & password valid 4. Klik Login | Redirect ke halaman /dashboard | |
| A-04 | Login dengan data valid (role owner) | Positive | Email: owner@test.com, Password: valid, Role: owner | 1. Buka halaman /login 2. Pilih role "Owner" 3. Isi email & password valid 4. Klik Login | Redirect ke halaman /dashboard | |
| A-05 | Login dengan email salah | Negative | Email: salah@test.com, Password: valid, Role: admin | 1. Buka halaman /login 2. Isi email yang tidak terdaftar 3. Klik Login | Tampil pesan error "Email, password, atau role tidak sesuai." | |
| A-06 | Login dengan password salah | Negative | Email: valid, Password: salah123, Role: admin | 1. Buka halaman /login 2. Isi password yang salah 3. Klik Login | Tampil pesan error "Email, password, atau role tidak sesuai." | |
| A-07 | Login dengan role tidak sesuai | Negative | Email: teknisi@test.com, Password: valid, Role: admin | 1. Buka halaman /login 2. Pilih role yang tidak sesuai dengan akun 3. Klik Login | Tampil pesan error "Email, password, atau role tidak sesuai." | |
| A-08 | Login dengan email kosong | Negative | Email: (kosong), Password: valid, Role: admin | 1. Buka halaman /login 2. Kosongkan field email 3. Klik Login | Tampil validasi "Email wajib diisi" | |
| A-09 | Login dengan password kosong | Negative | Email: valid, Password: (kosong), Role: admin | 1. Buka halaman /login 2. Kosongkan field password 3. Klik Login | Tampil validasi "Password wajib diisi" | |
| A-10 | Login dengan format email tidak valid | Negative | Email: bukan-email, Password: valid, Role: admin | 1. Buka halaman /login 2. Isi email dengan format salah 3. Klik Login | Tampil validasi format email tidak valid | |

### 1.2 Logout

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| A-11 | Logout berhasil | Positive | User sudah login | 1. Klik tombol Logout 2. Konfirmasi logout | Session dihapus, redirect ke halaman /login | |
| A-12 | Akses halaman setelah logout | Negative | User sudah logout | 1. Logout 2. Akses /dashboard via URL langsung | Redirect ke halaman /login | |
| A-13 | Akses halaman terproteksi tanpa login | Negative | Tidak ada session | 1. Buka browser baru 2. Akses /transactions langsung | Redirect ke halaman /login | |

---

## 2. Modul Dashboard

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| D-01 | Akses dashboard sebagai admin | Positive | Login sebagai admin | 1. Login sebagai admin 2. Akses /dashboard | Halaman dashboard tampil dengan metrik bulanan | |
| D-02 | Akses dashboard sebagai owner | Positive | Login sebagai owner | 1. Login sebagai owner 2. Akses /dashboard | Halaman dashboard tampil lengkap dengan semua widget | |
| D-03 | Akses dashboard sebagai teknisi | Negative | Login sebagai teknisi | 1. Login sebagai teknisi 2. Akses /dashboard via URL | Redirect ke /transactions/create | |
| D-04 | Melihat data biaya per cabang | Positive | Login sebagai admin, ada data transaksi | 1. Buka dashboard 2. Lihat chart biaya per cabang | Chart menampilkan data biaya per cabang bulan ini | |
| D-05 | Melihat daftar transaksi pending | Positive | Login sebagai admin, ada transaksi pending | 1. Buka dashboard 2. Lihat widget pending list | Daftar transaksi pending tampil dengan jumlah & total | |
| D-06 | Filter data dashboard berdasarkan periode | Positive | Login sebagai admin | 1. Buka dashboard 2. Ubah filter periode/bulan | Data dashboard berubah sesuai periode yang dipilih | |

---

## 3. Modul Transaksi - Rembush

### 3.1 Upload Nota

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| R-01 | Upload nota format JPG valid | Positive | File: nota.jpg (< 10MB) | 1. Buka halaman input transaksi 2. Pilih "Rembush" 3. Upload file JPG 4. Submit | File tersimpan, redirect ke form rembush | |
| R-02 | Upload nota format PNG valid | Positive | File: nota.png (< 10MB) | 1. Pilih "Rembush" 2. Upload file PNG 3. Submit | File tersimpan, redirect ke form rembush | |
| R-03 | Upload nota format PDF valid | Positive | File: nota.pdf (< 10MB) | 1. Pilih "Rembush" 2. Upload file PDF 3. Submit | File tersimpan, redirect ke form rembush | |
| R-04 | Upload file melebihi 10MB | Negative | File: large.jpg (> 10MB) | 1. Pilih "Rembush" 2. Upload file > 10MB 3. Submit | Tampil error validasi ukuran file | |
| R-05 | Upload file format tidak didukung | Negative | File: document.docx | 1. Pilih "Rembush" 2. Upload file .docx 3. Submit | Tampil error validasi format file | |
| R-06 | Upload tanpa memilih file | Negative | File: (kosong) | 1. Pilih "Rembush" 2. Tidak pilih file 3. Submit | Tampil error "File wajib diisi" | |
| R-07 | Upload melebihi batas rate limit (>5/menit) | Negative | 6 file dalam 1 menit | 1. Upload 5 file berturut-turut 2. Upload file ke-6 | Tampil error "Terlalu banyak upload. Tunggu X detik." | |

### 3.2 Form & Store Rembush

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| R-08 | Simpan rembush dengan data lengkap (cash) | Positive | Customer: Toko A, Kategori: valid, Amount: 50000, Payment: cash, Branch: valid | 1. Isi semua field wajib 2. Pilih metode cash 3. Pilih cabang 4. Submit | Transaksi tersimpan, redirect ke halaman konfirmasi | |
| R-09 | Simpan rembush dengan transfer ke penjual | Positive | Payment: transfer_penjual, Bank: BCA, Rek: 1234567890, Nama: John | 1. Isi form 2. Pilih "Transfer ke Penjual" 3. Isi detail bank 4. Submit | Transaksi tersimpan dengan data bank di field specs | |
| R-10 | Simpan rembush dengan transfer ke teknisi | Positive | Payment: transfer_teknisi, Pilih rekening teknisi | 1. Isi form 2. Pilih "Transfer ke Teknisi" 3. Pilih rekening 4. Submit | Transaksi tersimpan dengan bank_account_id di specs | |
| R-11 | Simpan rembush tanpa kategori | Negative | Kategori: (kosong) | 1. Isi form tanpa memilih kategori 2. Submit | Tampil error validasi "Kategori wajib dipilih" | |
| R-12 | Simpan rembush dengan kategori tidak valid | Negative | Kategori: "kategori_palsu" | 1. Isi form dengan kategori yang tidak ada di database 2. Submit | Tampil error "Kategori tidak valid" | |
| R-13 | Simpan rembush tanpa payment method | Negative | Payment method: (kosong) | 1. Isi form tanpa memilih metode pembayaran 2. Submit | Tampil error validasi payment_method | |
| R-14 | Simpan rembush transfer_penjual tanpa detail bank | Negative | Payment: transfer_penjual, Bank: (kosong) | 1. Pilih "Transfer ke Penjual" 2. Kosongkan detail bank 3. Submit | Tampil error "Nama Bank, Nama Rekening, dan Nomor Rekening wajib diisi" | |
| R-15 | Simpan rembush dengan alokasi cabang tidak 100% | Negative | Branch allocation: 50% + 30% = 80% | 1. Isi form 2. Set alokasi cabang total < 100% 3. Submit | Tampil error "Total alokasi harus 100%" | |
| R-16 | Simpan rembush dengan amount negatif | Negative | Amount: -5000 | 1. Isi amount dengan nilai negatif 2. Submit | Tampil error validasi min:0 | |

---

## 4. Modul Transaksi - Pengajuan

### 4.1 Upload Foto (Opsional)

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| P-01 | Upload foto referensi valid | Positive | File: referensi.jpg (< 10MB) | 1. Pilih "Pengajuan" 2. Upload foto referensi 3. Submit | File tersimpan di session, redirect ke form pengajuan | |
| P-02 | Upload foto format tidak valid | Negative | File: document.xlsx | 1. Pilih "Pengajuan" 2. Upload file .xlsx | Tampil error validasi format file | |
| P-03 | Upload foto melebihi 10MB | Negative | File: large.png (> 10MB) | 1. Upload file > 10MB | Tampil error validasi ukuran file | |

### 4.2 Form & Store Pengajuan

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| P-04 | Simpan pengajuan dengan 1 item valid | Positive | Item: {customer: "Baut 10mm", category: valid, estimated_price: 5000, quantity: 10} | 1. Buka form pengajuan 2. Isi 1 item lengkap 3. Submit | Transaksi tersimpan, redirect ke konfirmasi | |
| P-05 | Simpan pengajuan dengan multi-item | Positive | Items: 3 item berbeda dengan data lengkap | 1. Buka form 2. Tambah 3 item 3. Isi semua field 4. Submit | Transaksi tersimpan dengan array items berisi 3 item | |
| P-06 | Simpan pengajuan dengan alokasi cabang | Positive | Branches: [{branch_id: 1, allocation_percent: 60}, {branch_id: 2, allocation_percent: 40}] | 1. Isi form 2. Pilih 2 cabang dengan alokasi 60%+40% 3. Submit | Transaksi tersimpan dengan pivot branch allocation | |
| P-07 | Simpan pengajuan dengan biaya tambahan (PPN, DPP, Layanan) | Positive | dpp_lainnya: 10000, tax_amount: 5000, biaya_layanan_1: 3000 | 1. Isi form 2. Isi biaya tambahan 3. Submit | Total amount = sum(items) + dpp + ppn + layanan | |
| P-08 | Simpan pengajuan tanpa item | Negative | Items: (kosong/array kosong) | 1. Buka form 2. Tidak menambah item 3. Submit | Tampil error "Minimal harus ada 1 item" | |
| P-09 | Simpan pengajuan dengan nama barang kosong | Negative | Item: {customer: "", ...} | 1. Tambah item tanpa nama barang 2. Submit | Tampil error "Nama Barang/Jasa wajib diisi" | |
| P-10 | Simpan pengajuan dengan estimasi harga kosong | Negative | Item: {estimated_price: null, ...} | 1. Tambah item tanpa estimasi harga 2. Submit | Tampil error "Estimasi harga satuan wajib diisi" | |
| P-11 | Simpan pengajuan dengan quantity 0 | Negative | Item: {quantity: 0, ...} | 1. Isi quantity dengan 0 2. Submit | Tampil error validasi min:1 | |
| P-12 | Simpan pengajuan dengan kategori tidak valid | Negative | Item: {category: "kategori_palsu"} | 1. Isi kategori yang tidak ada di database 2. Submit | Tampil error "Alasan/Kategori tidak valid" | |
| P-13 | Simpan pengajuan dengan alokasi cabang tidak 100% | Negative | Branches total: 70% | 1. Set alokasi cabang total 70% 2. Submit | Tampil error "Total alokasi harus 100%" | |
| P-14 | Deteksi anomali harga otomatis | Positive | Item dengan harga melebihi max_price di Price Index | 1. Isi item dengan harga di atas batas 2. Submit | Transaksi tersimpan, anomali terdeteksi, notifikasi dikirim | |

---

## 5. Modul Transaksi - Pembelian (Gudang)

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| G-01 | Simpan pembelian dengan data lengkap | Positive | Date: valid, Category: valid, Items: [{name, qty, price}], Amount: valid, Payment: cash, Branches: valid | 1. Login sebagai atasan/owner 2. Buka form pembelian 3. Isi semua field 4. Submit | Transaksi tersimpan dengan type "gudang" | |
| G-02 | Simpan pembelian dengan nota (file upload) | Positive | Nota: file.jpg (< 10MB) | 1. Isi form pembelian 2. Upload nota 3. Submit | Transaksi tersimpan dengan file_path terisi | |
| G-03 | Simpan pembelian dengan transfer ke penjual | Positive | Payment: transfer_penjual, Bank details lengkap | 1. Pilih transfer_penjual 2. Isi bank details 3. Submit | Transaksi tersimpan dengan specs berisi data bank | |
| G-04 | Akses form pembelian sebagai teknisi | Negative | Login sebagai teknisi | 1. Login sebagai teknisi 2. Akses /pembelian/form via URL | Tampil error 403 / redirect | |
| G-05 | Akses form pembelian sebagai admin | Negative | Login sebagai admin | 1. Login sebagai admin 2. Akses /pembelian/form via URL | Tampil error 403 / redirect | |
| G-06 | Simpan pembelian tanpa tanggal | Negative | Date: (kosong) | 1. Kosongkan field tanggal 2. Submit | Tampil error "Tanggal pembelian wajib diisi" | |
| G-07 | Simpan pembelian tanpa item | Negative | Items: (kosong) | 1. Tidak menambah item 2. Submit | Tampil error "Minimal harus ada 1 item barang" | |
| G-08 | Simpan pembelian tanpa cabang | Negative | Branches: (kosong) | 1. Tidak memilih cabang 2. Submit | Tampil error "Minimal harus memilih 1 cabang" | |
| G-09 | Simpan pembelian dengan alokasi cabang tidak 100% | Negative | Branches total: 85% | 1. Set alokasi 85% 2. Submit | Tampil error "Total alokasi cabang harus 100%" | |
| G-10 | Simpan pembelian transfer_penjual tanpa bank_name | Negative | Payment: transfer_penjual, bank_name: (kosong) | 1. Pilih transfer_penjual 2. Kosongkan nama bank 3. Submit | Tampil error validasi bank_name required | |

---

## 6. Modul Manajemen Status Transaksi

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| S-01 | Approve transaksi pending | Positive | Transaksi status: pending, User: admin/atasan/owner | 1. Buka detail transaksi pending 2. Klik Approve | Status berubah menjadi "approved", notifikasi terkirim | |
| S-02 | Reject transaksi pending dengan alasan | Positive | Transaksi status: pending, Rejection reason: "Harga terlalu mahal" | 1. Buka detail transaksi 2. Klik Reject 3. Isi alasan 4. Submit | Status berubah menjadi "rejected", alasan tersimpan | |
| S-03 | Update status ke completed | Positive | Transaksi status: approved/waiting_payment | 1. Buka detail transaksi 2. Update status ke completed | Status berubah, paid_at terisi | |
| S-04 | Edit transaksi oleh management | Positive | Login sebagai admin/atasan/owner, Transaksi editable | 1. Buka halaman edit 2. Ubah data 3. Submit | Data terupdate, is_edited_by_management = true, revision_count bertambah | |
| S-05 | Teknisi mencoba approve transaksi | Negative | Login sebagai teknisi | 1. Login sebagai teknisi 2. Akses endpoint updateStatus via URL | Tampil error 403 | |
| S-06 | Teknisi mencoba edit transaksi | Negative | Login sebagai teknisi | 1. Login sebagai teknisi 2. Akses /transactions/{id}/edit | Tampil error 403 | |
| S-07 | Hapus transaksi yang sudah completed | Negative | Transaksi status: completed | 1. Coba hapus transaksi completed | Tampil error / tidak diizinkan | |
| S-08 | Edit transaksi yang sudah completed | Negative | Transaksi status: completed | 1. Akses halaman edit transaksi completed | Redirect / error "Transaksi tidak dapat diedit" | |

---

## 7. Modul Export Transaksi

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| E-01 | Export async dengan filter bulan | Positive | Month: 5, Year: 2026 | 1. Buka halaman transaksi 2. Set filter bulan 3. Klik Export | Job di-dispatch, export_id dikembalikan, file bisa didownload setelah selesai | |
| E-02 | Export async dengan filter type | Positive | Type: rembush | 1. Set filter type "rembush" 2. Klik Export | Hanya transaksi rembush yang di-export | |
| E-03 | Export async dengan filter status | Positive | Status: approved | 1. Set filter status "approved" 2. Klik Export | Hanya transaksi approved yang di-export | |
| E-04 | Export sync (legacy) | Positive | Filter: default | 1. Akses endpoint /transactions/export/sync | File Excel langsung didownload (streaming) | |
| E-05 | Cek status export yang sedang berjalan | Positive | Export sedang processing | 1. Dispatch export 2. Poll status endpoint | Response berisi status "processing" dengan progress | |
| E-06 | Download file export yang sudah selesai | Positive | Export status: completed | 1. Tunggu export selesai 2. Akses download endpoint | File Excel berhasil didownload | |
| E-07 | Export saat sudah ada export aktif | Negative | Ada export queued/processing | 1. Dispatch export 2. Dispatch export lagi sebelum selesai | Response berisi export_id yang sudah ada (tidak duplikat) | |
| E-08 | Export dengan filter bulan tidak valid | Negative | Month: 13 | 1. Set month = 13 2. Submit export | Tampil error validasi "month max:12" | |
| E-09 | Export tanpa autentikasi | Negative | User belum login | 1. Akses endpoint export tanpa login | Response 401 Unauthenticated | |

---

## 8. Modul Pengeluaran Lain - Bayar Hutang

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| BH-01 | Tambah record bayar hutang dengan data lengkap | Positive | Tanggal, nominal, cabang, bukti transfer | 1. Buka menu Bayar Hutang 2. Klik Tambah 3. Isi form lengkap 4. Submit | Record tersimpan dengan status pending | |
| BH-02 | Lihat daftar bayar hutang | Positive | Login sebagai admin | 1. Buka menu Bayar Hutang | Daftar record tampil dengan pagination | |
| BH-03 | Filter berdasarkan cabang | Positive | branch_id: valid | 1. Buka daftar 2. Pilih filter cabang | Hanya record cabang terpilih yang tampil | |
| BH-04 | Filter berdasarkan status (belum lunas) | Positive | record_status: belum_lunas | 1. Pilih filter "Belum Lunas" | Hanya record yang masih pending yang tampil | |
| BH-05 | Settle (lunasi) pembayaran hutang | Positive | Record approved, ada sisa | 1. Buka record 2. Klik Settle/Lunasi 3. Isi nominal 4. Submit | Record child terbuat, sisa hutang berkurang | |
| BH-06 | Hapus record bayar hutang | Positive | Record editable | 1. Buka record 2. Klik Hapus 3. Konfirmasi | Record terhapus dari database | |
| BH-07 | Akses bayar hutang sebagai teknisi | Negative | Login sebagai teknisi | 1. Login teknisi 2. Akses /pengeluaran-lain/bayar-hutang | Tampil error 403 | |
| BH-08 | Tambah record tanpa nominal | Negative | Nominal: (kosong) | 1. Kosongkan field nominal 2. Submit | Tampil error validasi | |

---

## 9. Modul Pengeluaran Lain - Piutang Usaha

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| PU-01 | Tambah record piutang usaha | Positive | Data lengkap: tanggal, nominal, cabang | 1. Buka menu Piutang Usaha 2. Klik Tambah 3. Isi form 4. Submit | Record tersimpan | |
| PU-02 | Lihat history pembayaran piutang | Positive | Record dengan children | 1. Buka record piutang 2. Klik History | Daftar pembayaran cicilan tampil | |
| PU-03 | Settle piutang usaha | Positive | Record approved | 1. Buka record 2. Klik Settle 3. Isi data 4. Submit | Pembayaran cicilan tercatat | |
| PU-04 | Akses piutang usaha sebagai teknisi | Negative | Login sebagai teknisi | 1. Login teknisi 2. Akses URL piutang usaha | Tampil error 403 | |

---

## 10. Modul Pengeluaran Lain - Prive

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| PR-01 | Tambah record prive (sebagai atasan) | Positive | Login: atasan, Data lengkap | 1. Login atasan 2. Buka menu Prive 3. Tambah record 4. Submit | Record tersimpan dengan status "pending" | |
| PR-02 | Tambah record prive (sebagai owner) | Positive | Login: owner, Data lengkap | 1. Login owner 2. Buka menu Prive 3. Tambah record 4. Submit | Record tersimpan | |
| PR-03 | Approve prive oleh owner | Positive | Login: owner, Record status: pending | 1. Login owner 2. Buka record prive 3. Klik Approve | Status berubah menjadi "approved" | |
| PR-04 | Reject prive oleh owner | Positive | Login: owner, Record status: pending | 1. Login owner 2. Buka record prive 3. Klik Reject | Status berubah menjadi "rejected" | |
| PR-05 | Akses prive sebagai admin | Negative | Login sebagai admin | 1. Login admin 2. Akses /pengeluaran-lain/prive | Tampil error 403 "Hanya Atasan dan Owner" | |
| PR-06 | Akses prive sebagai teknisi | Negative | Login sebagai teknisi | 1. Login teknisi 2. Akses URL prive | Tampil error 403 | |
| PR-07 | Approve prive oleh atasan (bukan owner) | Negative | Login: atasan | 1. Login atasan 2. Coba approve prive | Tampil error 403 "Hanya Owner yang bisa menyetujui" | |
| PR-08 | Approve prive yang sudah di-approve | Negative | Record status: approved | 1. Coba approve record yang sudah approved | Tidak ada perubahan / error | |

---

## 11. Modul Pengeluaran Lain - Gaji

### 11.1 CRUD Gaji

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| GJ-01 | Tambah data gaji dengan data lengkap | Positive | user_id: valid teknisi, periode: "Mei 2026", gaji_pokok: 3000000, bonus_1: 500000 | 1. Buka menu Gaji 2. Klik Tambah 3. Isi form lengkap 4. Submit | Record gaji tersimpan dengan status "draft" | |
| GJ-02 | Lihat daftar gaji | Positive | Login sebagai admin/atasan/owner | 1. Buka menu Gaji | Daftar gaji tampil dengan pagination | |
| GJ-03 | Lihat detail gaji | Positive | Record gaji ada | 1. Klik salah satu record gaji | Detail gaji tampil lengkap (komponen, total, status) | |
| GJ-04 | Edit gaji status draft | Positive | Record status: draft | 1. Buka detail gaji draft 2. Klik Edit 3. Ubah data 4. Submit | Data gaji terupdate | |
| GJ-05 | Hapus gaji status draft | Positive | Record status: draft | 1. Buka detail gaji draft 2. Klik Hapus 3. Konfirmasi | Record terhapus | |
| GJ-06 | Tambah gaji tanpa user_id | Negative | user_id: (kosong) | 1. Kosongkan field karyawan 2. Submit | Tampil error "user_id wajib diisi" | |
| GJ-07 | Tambah gaji tanpa periode | Negative | periode: (kosong) | 1. Kosongkan field periode 2. Submit | Tampil error validasi | |
| GJ-08 | Tambah gaji tanpa gaji_pokok | Negative | gaji_pokok: (kosong) | 1. Kosongkan gaji pokok 2. Submit | Tampil error validasi | |
| GJ-09 | Tambah gaji dengan gaji_pokok negatif | Negative | gaji_pokok: -1000000 | 1. Isi gaji pokok negatif 2. Submit | Tampil error validasi min:0 | |
| GJ-10 | Tambah gaji dengan user_id tidak valid | Negative | user_id: 99999 (tidak ada) | 1. Isi user_id yang tidak ada 2. Submit | Tampil error "user_id tidak valid" | |
| GJ-11 | Akses gaji sebagai teknisi | Negative | Login sebagai teknisi | 1. Login teknisi 2. Akses /pengeluaran-lain/gaji | Tampil error 403 | |

### 11.2 Approval & Payment Gaji

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| GJ-12 | Approve gaji draft (sebagai atasan) | Positive | Login: atasan, Record status: draft | 1. Login atasan 2. Buka detail gaji 3. Klik Approve | Status berubah "approved", approved_by & approved_at terisi | |
| GJ-13 | Approve gaji draft (sebagai owner) | Positive | Login: owner, Record status: draft | 1. Login owner 2. Approve gaji | Status berubah "approved" | |
| GJ-14 | Tandai gaji sudah dibayar | Positive | Record status: approved | 1. Buka detail gaji approved 2. Klik "Tandai Sudah Dibayar" | Status berubah "paid", notifikasi Telegram terkirim ke karyawan | |
| GJ-15 | Approve gaji oleh admin (bukan atasan/owner) | Negative | Login: admin | 1. Login admin 2. Coba approve gaji | Tampil error 403 | |
| GJ-16 | Approve gaji yang bukan draft | Negative | Record status: approved | 1. Coba approve gaji yang sudah approved | Tampil pesan "Hanya gaji dengan status Draft yang bisa disetujui" | |
| GJ-17 | Bayar gaji yang belum approved | Negative | Record status: draft | 1. Coba tandai bayar gaji draft | Tampil pesan "Hanya gaji yang sudah disetujui yang bisa ditandai sudah dibayar" | |
| GJ-18 | Edit gaji yang sudah approved | Negative | Record status: approved | 1. Coba edit gaji approved | Tampil pesan "Gaji yang sudah disetujui tidak dapat diedit" | |
| GJ-19 | Hapus gaji yang sudah approved | Negative | Record status: approved | 1. Coba hapus gaji approved | Tampil pesan "Gaji yang sudah disetujui tidak bisa dihapus" | |

---

## 12. Modul Manajemen User

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| U-01 | Tambah user baru dengan data valid | Positive | Name: "John Doe", Email: john@test.com, Password: Pass123, Role: teknisi | 1. Buka menu Users 2. Klik Tambah 3. Isi form lengkap 4. Submit | User tersimpan, redirect ke daftar user | |
| U-02 | Lihat daftar user | Positive | Login sebagai admin | 1. Buka menu Users | Daftar user tampil dengan pagination | |
| U-03 | Filter user berdasarkan role | Positive | Role: teknisi | 1. Buka daftar user 2. Pilih filter role "teknisi" | Hanya user dengan role teknisi yang tampil | |
| U-04 | Search user berdasarkan nama | Positive | Search: "John" | 1. Ketik "John" di field search | User dengan nama mengandung "John" tampil | |
| U-05 | Edit user (ubah nama) | Positive | Name: "John Updated" | 1. Buka halaman edit user 2. Ubah nama 3. Submit | Nama user terupdate | |
| U-06 | Edit user (ubah password) | Positive | Password: NewPass123, Confirm: NewPass123 | 1. Edit user 2. Isi password baru + konfirmasi 3. Submit | Password terupdate | |
| U-07 | Hapus user (bukan diri sendiri) | Positive | User lain yang bisa dikelola | 1. Buka daftar user 2. Klik Hapus pada user lain 3. Konfirmasi | User terhapus | |
| U-08 | Owner menambah user dengan role owner | Positive | Login: owner, Role: owner | 1. Login owner 2. Tambah user role owner | User tersimpan dengan role owner | |
| U-09 | Tambah user dengan email duplikat | Negative | Email: sudah_ada@test.com | 1. Isi email yang sudah terdaftar 2. Submit | Tampil error "Email sudah terdaftar" | |
| U-10 | Tambah user tanpa nama | Negative | Name: (kosong) | 1. Kosongkan field nama 2. Submit | Tampil error "Nama wajib diisi" | |
| U-11 | Tambah user tanpa email | Negative | Email: (kosong) | 1. Kosongkan field email 2. Submit | Tampil error "Email wajib diisi" | |
| U-12 | Tambah user dengan format email tidak valid | Negative | Email: "bukan-email" | 1. Isi email format salah 2. Submit | Tampil error "Format email tidak valid" | |
| U-13 | Tambah user dengan password < 6 karakter | Negative | Password: "123" | 1. Isi password kurang dari 6 karakter 2. Submit | Tampil error "Password minimal 6 karakter" | |
| U-14 | Tambah user dengan konfirmasi password tidak cocok | Negative | Password: "Pass123", Confirm: "Pass456" | 1. Isi password & konfirmasi berbeda 2. Submit | Tampil error "Konfirmasi password tidak cocok" | |
| U-15 | Admin mencoba tambah user role admin | Negative | Login: admin, Role: admin | 1. Login admin 2. Coba tambah user role admin | Role admin tidak tersedia di pilihan (hanya teknisi) | |
| U-16 | Admin mencoba edit user role atasan | Negative | Login: admin, Target: user atasan | 1. Login admin 2. Coba edit user atasan | Tampil error 403 | |
| U-17 | Hapus diri sendiri | Negative | User mencoba hapus akunnya sendiri | 1. Klik hapus pada akun sendiri | Tampil pesan "Anda tidak dapat menghapus akun Anda sendiri" | |
| U-18 | Akses manajemen user sebagai teknisi | Negative | Login sebagai teknisi | 1. Login teknisi 2. Akses /users | Tampil error 403 | |

---

## 13. Modul Manajemen Cabang

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| BR-01 | Tambah cabang baru | Positive | Name: "Cabang Baru" | 1. Buka menu Cabang 2. Klik Tambah 3. Isi nama 4. Submit | Cabang tersimpan | |
| BR-02 | Lihat daftar cabang | Positive | Login sebagai admin | 1. Buka menu Cabang | Daftar cabang tampil dengan jumlah transaksi | |
| BR-03 | Edit nama cabang | Positive | Name: "Cabang Updated" | 1. Klik Edit pada cabang 2. Ubah nama 3. Submit | Nama cabang terupdate (uppercase) | |
| BR-04 | Hapus cabang tanpa transaksi | Positive | Cabang tanpa relasi transaksi | 1. Klik Hapus pada cabang kosong 2. Konfirmasi | Cabang terhapus | |
| BR-05 | Tambah cabang dengan nama duplikat | Negative | Name: "Cabang Existing" (sudah ada) | 1. Isi nama cabang yang sudah ada 2. Submit | Tampil error "Nama cabang sudah terdaftar" | |
| BR-06 | Tambah cabang tanpa nama | Negative | Name: (kosong) | 1. Kosongkan field nama 2. Submit | Tampil error "Nama cabang wajib diisi" | |
| BR-07 | Tambah cabang dengan nama > 100 karakter | Negative | Name: (101+ karakter) | 1. Isi nama > 100 karakter 2. Submit | Tampil error "Nama cabang maksimal 100 karakter" | |
| BR-08 | Hapus cabang yang memiliki transaksi | Negative | Cabang dengan relasi transaksi | 1. Coba hapus cabang yang punya transaksi | Tampil error "Cabang tidak dapat dihapus karena masih memiliki transaksi terkait" | |
| BR-09 | Akses manajemen cabang sebagai teknisi | Negative | Login sebagai teknisi | 1. Login teknisi 2. Akses /branches | Tampil error 403 | |

---

## 14. Modul Rekening Bank Cabang

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| RBC-01 | Tambah rekening bank cabang | Positive | Branch: valid, Bank: BCA, Account: 1234567890, Name: "PT ABC" | 1. Buka detail cabang 2. Tambah rekening 3. Isi data 4. Submit | Rekening bank tersimpan | |
| RBC-02 | Lihat daftar rekening bank cabang | Positive | Cabang memiliki rekening | 1. Buka detail cabang | Daftar rekening bank tampil | |
| RBC-03 | Edit rekening bank cabang | Positive | Data baru valid | 1. Klik Edit rekening 2. Ubah data 3. Submit | Data rekening terupdate | |
| RBC-04 | Hapus rekening bank cabang | Positive | Rekening tidak digunakan | 1. Klik Hapus rekening 2. Konfirmasi | Rekening terhapus | |
| RBC-05 | Tambah rekening tanpa nama bank | Negative | Bank: (kosong) | 1. Kosongkan nama bank 2. Submit | Tampil error validasi | |
| RBC-06 | Tambah rekening tanpa nomor rekening | Negative | Account: (kosong) | 1. Kosongkan nomor rekening 2. Submit | Tampil error validasi | |

---

## 15. Modul Rekening Bank User

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| RBU-01 | Tambah rekening bank user | Positive | User: valid, Bank: BRI, Account: 9876543210, Name: "John Doe" | 1. Buka profil user 2. Tambah rekening 3. Isi data 4. Submit | Rekening bank user tersimpan | |
| RBU-02 | Lihat daftar rekening bank user | Positive | User memiliki rekening | 1. Akses endpoint /user-bank-accounts/{user_id} | Daftar rekening tampil | |
| RBU-03 | Edit rekening bank user | Positive | Data baru valid | 1. Edit rekening 2. Ubah data 3. Submit | Data terupdate | |
| RBU-04 | Hapus rekening bank user | Positive | Rekening ada | 1. Hapus rekening 2. Konfirmasi | Rekening terhapus | |
| RBU-05 | Tambah rekening tanpa data wajib | Negative | Semua field kosong | 1. Submit form kosong | Tampil error validasi untuk semua field wajib | |

---

## 16. Modul Kategori Transaksi

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| KT-01 | Tambah kategori baru | Positive | Name: "Kategori Baru", Type: rembush | 1. Buka menu Kategori 2. Klik Tambah 3. Isi data 4. Submit | Kategori tersimpan dengan is_active = true | |
| KT-02 | Lihat daftar kategori | Positive | Login sebagai admin | 1. Buka menu Kategori | Daftar kategori tampil | |
| KT-03 | Edit kategori | Positive | Name: "Kategori Updated" | 1. Klik Edit 2. Ubah nama 3. Submit | Nama kategori terupdate | |
| KT-04 | Toggle aktif/nonaktif kategori | Positive | Kategori aktif | 1. Klik Toggle pada kategori aktif | Status berubah menjadi nonaktif | |
| KT-05 | Hapus kategori | Positive | Kategori tidak digunakan | 1. Klik Hapus 2. Konfirmasi | Kategori terhapus | |
| KT-06 | Tambah kategori tanpa nama | Negative | Name: (kosong) | 1. Kosongkan nama 2. Submit | Tampil error validasi | |
| KT-07 | Akses kategori sebagai teknisi | Negative | Login sebagai teknisi | 1. Login teknisi 2. Akses /transaction-categories | Tampil error 403 | |

---

## 17. Modul Price Index

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| PI-01 | Tambah price index manual | Positive | item_name: "Baut 10mm", unit: "pcs", min: 500, max: 1500, avg: 1000 | 1. Login owner 2. Buka Price Index 3. Tambah baru 4. Submit | Price index tersimpan dengan is_manual = true | |
| PI-02 | Lihat daftar price index | Positive | Login sebagai owner | 1. Buka menu Price Index | Daftar price index tampil dengan pagination | |
| PI-03 | Search price index | Positive | Search: "Baut" | 1. Ketik "Baut" di search 2. Submit | Hanya item mengandung "Baut" yang tampil | |
| PI-04 | Filter berdasarkan kategori | Positive | Category: "Material" | 1. Pilih filter kategori | Hanya item kategori terpilih yang tampil | |
| PI-05 | Edit price index | Positive | Data baru valid | 1. Klik Edit 2. Ubah harga 3. Submit | Data terupdate, cache di-flush | |
| PI-06 | Update AVG Manual (override) | Positive | avg_price_manual: 1200, reason: "Harga pasar naik" | 1. Set AVG manual 2. Submit | avg_price_manual terisi, effective avg menggunakan manual | |
| PI-07 | Reset ke mode Auto | Positive | Price index mode manual | 1. Klik "Reset ke Auto" | is_manual = false, harga dihitung ulang dari history | |
| PI-08 | Hapus price index | Positive | Price index ada | 1. Klik Hapus 2. Konfirmasi | Price index terhapus | |
| PI-09 | Set transaksi sebagai referensi harga | Positive | Transaksi dengan item valid | 1. Buka transaksi 2. Klik "Set as Reference" | Price index diupdate berdasarkan harga transaksi | |
| PI-10 | Export CSV price index | Positive | Login sebagai owner | 1. Klik Export CSV | File CSV terdownload dengan semua data price index | |
| PI-11 | Lihat analytics dashboard | Positive | Login sebagai owner | 1. Buka menu Analytics | Dashboard analytics tampil (trend, top volatile, breakdown) | |
| PI-12 | Tambah price index dengan min > max | Negative | min_price: 2000, max_price: 1000 | 1. Isi min > max 2. Submit | Tampil error "Harga minimum tidak boleh lebih besar dari harga maksimum" | |
| PI-13 | Tambah price index dengan item duplikat | Negative | item_name: (sudah ada) | 1. Isi nama item yang sudah ada 2. Submit | Tampil error "Item sudah ada di Price Index" | |
| PI-14 | Tambah price index tanpa item_name | Negative | item_name: (kosong) | 1. Kosongkan nama item 2. Submit | Tampil error validasi | |
| PI-15 | Tambah price index dengan harga negatif | Negative | min_price: -100 | 1. Isi harga negatif 2. Submit | Tampil error "Harga tidak boleh negatif" | |
| PI-16 | Akses price index sebagai admin | Negative | Login sebagai admin | 1. Login admin 2. Akses /price-index | Tampil error 403 (hanya owner) | |
| PI-17 | Akses price index sebagai teknisi | Negative | Login sebagai teknisi | 1. Login teknisi 2. Akses /price-index | Tampil error 403 | |

### 17.1 API Price Index (Lookup & Check)

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| PI-18 | Lookup item yang ada di index | Positive | item_name: "Baut 10mm" | 1. Request GET /api/price-index/lookup?item_name=Baut 10mm | Response: found=true, data harga lengkap | |
| PI-19 | Lookup item yang tidak ada | Positive | item_name: "Item Tidak Ada" | 1. Request GET /api/price-index/lookup?item_name=Item Tidak Ada | Response: found=false | |
| PI-20 | Check harga normal (tidak anomali) | Positive | item_name: "Baut 10mm", unit_price: 1000 (dalam range) | 1. POST /api/price-index/check | Response: is_anomaly=false | |
| PI-21 | Check harga anomali (melebihi max) | Positive | item_name: "Baut 10mm", unit_price: 5000 (di atas max) | 1. POST /api/price-index/check | Response: is_anomaly=true, severity & excess_percentage terisi | |
| PI-22 | Lookup dengan item_name < 2 karakter | Negative | item_name: "A" | 1. Request lookup dengan 1 karakter | Tampil error validasi min:2 | |

---

## 18. Modul Anomali Harga

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| AN-01 | Lihat daftar anomali pending | Positive | Login sebagai owner, ada anomali | 1. Buka menu Anomali Harga | Daftar anomali pending tampil, diurutkan severity | |
| AN-02 | Filter anomali berdasarkan severity | Positive | Severity: critical | 1. Pilih filter severity "critical" | Hanya anomali critical yang tampil | |
| AN-03 | Search anomali berdasarkan nama item | Positive | Search: "Baut" | 1. Ketik "Baut" di search | Anomali dengan item "Baut" tampil | |
| AN-04 | Review anomali - Approve | Positive | Anomali status: pending | 1. Buka anomali 2. Klik Approve 3. Isi notes (opsional) 4. Submit | Status berubah "approved", reviewed_at terisi | |
| AN-05 | Review anomali - Reject | Positive | Anomali status: pending | 1. Buka anomali 2. Klik Reject 3. Isi notes 4. Submit | Status berubah "rejected" | |
| AN-06 | Bulk review anomali | Positive | Multiple anomali pending | 1. Pilih beberapa anomali 2. Klik Bulk Approve/Reject 3. Submit | Semua anomali terpilih terupdate | |
| AN-07 | Review anomali yang sudah di-review | Negative | Anomali status: approved | 1. Coba review anomali yang sudah approved | Tampil error "Anomali ini sudah pernah di-review" | |
| AN-08 | Bulk review dengan action tidak valid | Negative | Action: "invalid_action" | 1. Submit bulk review dengan action salah | Tampil error validasi | |
| AN-09 | Akses anomali sebagai non-owner | Negative | Login sebagai admin | 1. Login admin 2. Akses /price-index/anomalies | Tampil error 403 | |

---

## 19. Modul Notifikasi

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| N-01 | Lihat daftar notifikasi | Positive | User memiliki notifikasi | 1. Buka menu Notifikasi | Daftar notifikasi tampil | |
| N-02 | Cek jumlah notifikasi belum dibaca | Positive | Ada notifikasi unread | 1. Request GET /notifications/unread-count | Response: count > 0 | |
| N-03 | Tandai satu notifikasi sebagai dibaca | Positive | Notifikasi unread | 1. Klik notifikasi / mark as read | Notifikasi ditandai read | |
| N-04 | Tandai semua notifikasi sebagai dibaca | Positive | Ada beberapa notifikasi unread | 1. Klik "Tandai Semua Dibaca" | Semua notifikasi menjadi read | |
| N-05 | Hapus satu notifikasi | Positive | Notifikasi ada | 1. Klik Hapus pada notifikasi | Notifikasi terhapus | |
| N-06 | Hapus semua notifikasi | Positive | Ada notifikasi | 1. Klik "Hapus Semua" 2. Konfirmasi | Semua notifikasi terhapus | |
| N-07 | Notifikasi otomatis saat transaksi di-approve | Positive | Transaksi di-approve oleh admin | 1. Admin approve transaksi | Teknisi menerima notifikasi status update | |
| N-08 | Akses notifikasi tanpa login | Negative | User belum login | 1. Akses /notifications tanpa login | Redirect ke login | |

---

## 20. Modul Activity Log

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| AL-01 | Lihat daftar activity log | Positive | Login sebagai admin, ada log | 1. Buka menu Activity Log | Daftar log aktivitas tampil dengan pagination | |
| AL-02 | Log tercatat saat transaksi dibuat | Positive | Buat transaksi baru | 1. Buat transaksi 2. Cek activity log | Log "create transaction" tercatat | |
| AL-03 | Log tercatat saat status diubah | Positive | Ubah status transaksi | 1. Approve transaksi 2. Cek activity log | Log "update status" tercatat | |
| AL-04 | Akses activity log sebagai teknisi | Negative | Login sebagai teknisi | 1. Login teknisi 2. Akses /activity-logs | Tampil error 403 | |

---

## Ringkasan Jumlah Test Case

| Modul | Positive | Negative | Total |
|-------|----------|----------|-------|
| 1. Autentikasi | 5 | 8 | 13 |
| 2. Dashboard | 5 | 1 | 6 |
| 3. Transaksi Rembush | 6 | 10 | 16 |
| 4. Transaksi Pengajuan | 8 | 6 | 14 |
| 5. Transaksi Pembelian | 3 | 7 | 10 |
| 6. Manajemen Status | 4 | 4 | 8 |
| 7. Export Transaksi | 6 | 3 | 9 |
| 8. Bayar Hutang | 5 | 3 | 8 |
| 9. Piutang Usaha | 3 | 1 | 4 |
| 10. Prive | 4 | 4 | 8 |
| 11. Gaji | 10 | 9 | 19 |
| 12. Manajemen User | 8 | 10 | 18 |
| 13. Manajemen Cabang | 4 | 5 | 9 |
| 14. Rekening Bank Cabang | 4 | 2 | 6 |
| 15. Rekening Bank User | 4 | 1 | 5 |
| 16. Kategori Transaksi | 5 | 2 | 7 |
| 17. Price Index | 16 | 6 | 22 |
| 18. Anomali Harga | 6 | 3 | 9 |
| 19. Notifikasi | 7 | 1 | 8 |
| 20. Activity Log | 3 | 1 | 4 |
| **TOTAL** | **116** | **87** | **203** |

---

## Catatan Pengujian

### Prasyarat Umum
1. Aplikasi sudah di-deploy dan berjalan normal
2. Database sudah di-migrate dan di-seed dengan data awal
3. Terdapat minimal 1 user untuk setiap role (teknisi, admin, atasan, owner)
4. Terdapat minimal 2 cabang (branch) aktif
5. Terdapat minimal 1 kategori aktif untuk rembush dan pengajuan
6. Redis dan Queue worker berjalan (untuk fitur OCR & export async)
7. Telegram Bot terkonfigurasi (untuk fitur notifikasi gaji)

### Lingkungan Pengujian
- **Browser:** Chrome/Firefox/Edge versi terbaru
- **Resolusi:** Desktop (1920x1080) dan Mobile (375x667)
- **Koneksi:** Stabil (untuk test upload & real-time features)

### Konvensi Status
| Status | Keterangan |
|--------|------------|
| ✅ Pass | Hasil sesuai yang diharapkan |
| ❌ Fail | Hasil tidak sesuai yang diharapkan |
| ⏳ Pending | Belum diuji |
| ⚠️ Partial | Sebagian sesuai, ada catatan |

### Penguji

| Nama | Role | Tanggal Mulai | Tanggal Selesai | Tanda Tangan |
|------|------|---------------|-----------------|--------------|
| | | | | |
| | | | | |

---

*Dokumen ini dibuat secara otomatis berdasarkan analisis source code project Admin Payment.*


---

## TAMBAHAN - Modul yang Belum Tercakup

---

## 21. Modul Pembayaran - Upload Bukti Cash

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| PC-01 | Upload bukti cash dengan data valid | Positive | foto_penyerahan: file.jpg, upload_id: valid, transaksi_id: valid | 1. Buka transaksi waiting_payment 2. Pilih "Bayar Cash" 3. Upload foto penyerahan 4. Submit | Bukti tersimpan, notifikasi Telegram terkirim ke teknisi | |
| PC-02 | Konfirmasi penerimaan cash oleh teknisi (terima) | Positive | action: "terima", upload_id: valid, teknisi_id: valid | 1. Teknisi menerima notifikasi Telegram 2. Klik tombol "Terima" | Status transaksi berubah ke completed/approved, konfirmasi_at terisi | |
| PC-03 | Tolak penerimaan cash oleh teknisi | Positive | action: "tolak", catatan: "Nominal tidak sesuai" | 1. Teknisi klik tombol "Tolak" di Telegram 2. Isi catatan | Status berubah "Ditolak Teknisi", activity log tercatat | |
| PC-04 | Upload bukti cash tanpa file | Negative | foto_penyerahan: (kosong) | 1. Submit tanpa upload file | Tampil error "Bukti penyerahan wajib diunggah" | |
| PC-05 | Upload bukti cash pada transaksi bukan waiting_payment | Negative | Transaksi status: pending | 1. Coba upload bukti pada transaksi pending | Tampil error "Transaksi belum disetujui atau sudah dibayar" | |
| PC-06 | Konfirmasi cash yang sudah dikonfirmasi (double-click) | Negative | Transaksi sudah completed | 1. Klik tombol konfirmasi lagi | Tampil error "Transaksi ini sudah dikonfirmasi sebelumnya" | |
| PC-07 | Teknisi konfirmasi transaksi milik orang lain | Negative | teknisi_id ≠ submitted_by | 1. Teknisi A coba konfirmasi transaksi teknisi B | Tampil error "Anda hanya bisa konfirmasi pengajuan Anda sendiri" | |

---

## 22. Modul Pembayaran - Upload Bukti Transfer

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| PT-01 | Upload bukti transfer dengan data valid | Positive | bukti_transfer: file.jpg, upload_id: valid, expected_nominal: 150000 | 1. Buka transaksi waiting_payment 2. Pilih "Upload Bukti Transfer" 3. Upload file 4. Submit | File tersimpan, dikirim ke n8n untuk OCR verifikasi | |
| PT-02 | Upload bukti transfer dengan kode unik & biaya admin | Positive | expected_nominal: 150000, kode_unik: 123, biaya_admin: 2500 | 1. Isi nominal + kode unik + biaya admin 2. Upload bukti 3. Submit | expected_total = nominal + kode_unik + biaya_admin | |
| PT-03 | Verifikasi transfer MATCH (nominal sesuai) | Positive | n8n callback: status=match, actual_total=expected_total | 1. Upload bukti 2. n8n OCR selesai 3. Callback match | Status berubah completed/approved, notifikasi Telegram ke teknisi | |
| PT-04 | Verifikasi transfer FLAGGED (ada selisih) | Positive | n8n callback: status=flagged, selisih > tolerance | 1. Upload bukti 2. n8n OCR menemukan selisih | Status berubah "flagged", audit record tercatat, notifikasi ke owner | |
| PT-05 | Upload bukti transfer tanpa file | Negative | bukti_transfer: (kosong) | 1. Submit tanpa upload file | Tampil error "Bukti transfer wajib diunggah" | |
| PT-06 | Upload bukti transfer pada transaksi bukan waiting_payment | Negative | Transaksi status: pending | 1. Coba upload pada transaksi pending | Tampil error "Transaksi belum disetujui atau sudah dibayar" | |
| PT-07 | Upload bukti transfer file > 10MB | Negative | File: large.jpg (> 10MB) | 1. Upload file > 10MB | Tampil error validasi ukuran file | |
| PT-08 | Zero-trust: n8n bilang match tapi backend temukan selisih | Positive | n8n: match, tapi actual ≠ expected (selisih > 1000) | 1. n8n callback match 2. Backend hitung ulang | Backend override ke "flagged" meskipun n8n bilang match | |

---

## 23. Modul Upload Invoice Pengajuan

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| INV-01 | Upload invoice pengajuan dengan sumber dana tunggal | Positive | invoice: file.jpg, sumber_dana: [{branch_id: 1, amount: 100000}] | 1. Buka pengajuan approved 2. Upload invoice 3. Pilih sumber dana 4. Submit | Invoice tersimpan, status berubah, sumber dana tercatat | |
| INV-02 | Upload invoice pengajuan dengan multi sumber dana | Positive | sumber_dana: [{branch_id: 1, amount: 60000}, {branch_id: 2, amount: 40000}] | 1. Pilih 2 sumber dana 2. Isi nominal masing-masing 3. Submit | Hutang antar cabang (BranchDebt) tercatat otomatis | |
| INV-03 | Upload invoice dengan biaya tambahan (ongkir, PPN, diskon) | Positive | ongkir: 15000, tax_amount: 5000, discount: 10000 | 1. Isi biaya tambahan 2. Submit | Total final dihitung dengan benar | |
| INV-04 | Upload invoice tanpa file | Negative | invoice: (kosong) | 1. Submit tanpa upload file | Tampil error validasi | |
| INV-05 | Upload invoice dengan total sumber dana ≠ final amount | Negative | Total sumber dana: 80000, Final amount: 100000 | 1. Isi sumber dana tidak sesuai total 2. Submit | Tampil error "Total sumber dana harus sama dengan total akhir" | |

---

## 24. Modul Override & Force Approve

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| OF-01 | Override transaksi auto-reject dengan alasan valid | Positive | override_reason: "Nota valid, OCR salah baca" (≥5 karakter) | 1. Buka transaksi auto-reject 2. Klik Override 3. Isi alasan 4. Submit | Status berubah "waiting_payment", alasan tercatat | |
| OF-02 | Force approve transaksi flagged dengan alasan valid | Positive | force_approve_reason: "Selisih karena biaya admin" (≥5 karakter) | 1. Buka transaksi flagged 2. Klik Force Approve 3. Isi alasan 4. Submit | Status berubah "completed", notifikasi Telegram ke owner & teknisi | |
| OF-03 | Override tanpa alasan | Negative | override_reason: (kosong) | 1. Klik Override tanpa isi alasan 2. Submit | Tampil error "Alasan override wajib diisi (minimal 5 karakter)" | |
| OF-04 | Override dengan alasan < 5 karakter | Negative | override_reason: "ok" | 1. Isi alasan < 5 karakter 2. Submit | Tampil error "Alasan override wajib diisi (minimal 5 karakter)" | |
| OF-05 | Override transaksi yang bukan auto-reject | Negative | Transaksi status: pending | 1. Coba override transaksi pending | Tampil error "Override hanya bisa dilakukan pada nota yang berstatus Auto-Reject" | |
| OF-06 | Force approve transaksi yang bukan flagged | Negative | Transaksi status: pending | 1. Coba force approve transaksi pending | Tampil error "Force Approve hanya bisa dilakukan pada transaksi yang di-flag" | |
| OF-07 | Force approve tanpa alasan | Negative | force_approve_reason: (kosong) | 1. Klik Force Approve tanpa alasan | Tampil error "Alasan Force Approve wajib diisi (minimal 5 karakter)" | |

---

## 25. Modul Telegram Bot

### 25.1 Registrasi & Perintah Bot

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| TG-01 | Perintah /start (belum terdaftar) | Positive | Chat baru dengan bot | 1. Kirim /start ke bot | Bot menampilkan pesan selamat datang & instruksi /daftar | |
| TG-02 | Perintah /start (sudah terdaftar) | Positive | User sudah terdaftar | 1. Kirim /start | Bot menampilkan info akun yang sudah terhubung | |
| TG-03 | Perintah /daftar dengan email valid | Positive | /daftar admin@whusnet.com | 1. Kirim /daftar email@valid.com | Bot konfirmasi pendaftaran berhasil, chat_id tersimpan | |
| TG-04 | Perintah /status (sudah terdaftar) | Positive | User terdaftar | 1. Kirim /status | Bot menampilkan nama, email, role, status aktif | |
| TG-05 | Perintah /cabut (hapus notifikasi) | Positive | User terdaftar | 1. Kirim /cabut | chat_id dihapus, bot konfirmasi notifikasi dinonaktifkan | |
| TG-06 | Perintah /daftar tanpa email | Negative | /daftar (tanpa parameter) | 1. Kirim /daftar saja | Bot menampilkan error format & contoh penggunaan | |
| TG-07 | Perintah /daftar dengan email tidak valid | Negative | /daftar bukan-email | 1. Kirim /daftar bukan-email | Bot menampilkan "Format email tidak valid" | |
| TG-08 | Perintah /daftar dengan email tidak terdaftar | Negative | /daftar tidakada@test.com | 1. Kirim /daftar email-tidak-ada | Bot menampilkan "Email tidak ditemukan" | |
| TG-09 | Perintah /daftar email sudah terhubung ke Telegram lain | Negative | Email sudah punya chat_id lain | 1. Kirim /daftar email-sudah-terhubung | Bot menampilkan "Akun sudah terhubung ke akun Telegram lain" | |
| TG-10 | Perintah tidak dikenal | Negative | /perintah_random | 1. Kirim perintah acak | Bot menampilkan daftar perintah yang tersedia | |

### 25.2 Callback Button Telegram

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| TG-11 | Klik tombol "Terima" (konfirmasi cash) | Positive | Callback: confirm_cash:{id} | 1. Terima notifikasi cash 2. Klik "Terima" | Transaksi completed, pesan diupdate "SELESAI ✅" | |
| TG-12 | Klik tombol "Laporkan Masalah" (tolak) | Positive | Callback: report_issue:{id} | 1. Terima notifikasi 2. Klik "Laporkan Masalah" | Transaksi rejected, pesan diupdate "DITOLAK ❌" | |
| TG-13 | Klik tombol pada transaksi yang sudah diproses | Negative | Transaksi sudah completed | 1. Klik tombol pada transaksi lama | Popup "Transaksi ini sudah diproses sebelumnya" | |
| TG-14 | User tidak terdaftar klik tombol | Negative | chat_id tidak ada di database | 1. User tanpa registrasi klik tombol | Popup "Akun Telegram Anda belum terdaftar di sistem" | |

---

## 26. Modul Item Autocomplete (Master Item)

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| IA-01 | Search autocomplete dengan keyword valid | Positive | q: "kabel", category: "Elektrikal" | 1. Ketik "kabel" di form pengajuan | Suggestions muncul dari master item | |
| IA-02 | Search autocomplete tanpa filter kategori | Positive | q: "baut" | 1. Ketik "baut" tanpa pilih kategori | Semua item mengandung "baut" tampil | |
| IA-03 | Lihat detail master item | Positive | id: valid master_item_id | 1. Pilih item dari dropdown | Detail item (nama, kategori, SKU) tampil | |
| IA-04 | Tambah barang baru (pending approval) | Positive | item_name: "Barang Baru XYZ", category: "Material" | 1. Klik "Tambah Barang Baru" 2. Isi nama 3. Submit | Item tersimpan dengan status pending_approval | |
| IA-05 | Search dengan keyword < 2 karakter | Negative | q: "a" | 1. Ketik 1 karakter saja | Response: suggestions kosong (tidak query) | |
| IA-06 | Tambah barang baru tanpa nama | Negative | item_name: (kosong) | 1. Submit tanpa nama barang | Tampil error validasi min:2 | |

---

## 27. Modul OCR Status Polling & Monitoring

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| OCR-01 | Poll status OCR yang sedang processing | Positive | upload_id: valid, OCR sedang berjalan | 1. Upload nota 2. Poll /api/ai/auto-fill/status/{uploadId} | Response: status=processing, message="Sedang memproses dengan AI..." | |
| OCR-02 | Poll status OCR yang sudah selesai (success) | Positive | OCR selesai dengan hasil | 1. Poll setelah OCR selesai | Response: status=completed, data hasil OCR tersedia | |
| OCR-03 | Poll status OCR yang gagal (error) | Positive | OCR gagal | 1. Poll setelah OCR error | Response: status=error, message error | |
| OCR-04 | Poll status OCR auto-reject | Positive | OCR mendeteksi nota tidak valid | 1. Poll setelah auto-reject | Response: status=auto-reject, alasan penolakan | |
| OCR-05 | Admin monitoring OCR queue | Positive | Login sebagai admin/owner | 1. Akses /api/admin/ocr-status | Response: rate_limiter status, queue stats (high/normal/low) | |
| OCR-06 | Poll dengan upload_id tidak ada | Negative | upload_id: "TIDAK-ADA" | 1. Poll dengan ID palsu | Response: status=error, "Data tidak ditemukan" | |
| OCR-07 | Admin monitoring oleh non-admin | Negative | Login sebagai teknisi | 1. Akses /api/admin/ocr-status | Response 403 Forbidden | |
| OCR-08 | OCR timeout (stuck > 3 menit) | Positive | OCR tidak selesai dalam 3 menit | 1. Upload nota 2. Tunggu > 3 menit 3. Poll status | Response menunjukkan timeout/error, user bisa upload ulang | |

---

## 28. Modul Pencarian & Filter Transaksi

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| SF-01 | Search berdasarkan invoice number | Positive | search: "INV-20260529" | 1. Ketik nomor invoice di search 2. Submit | Transaksi dengan invoice tersebut tampil | |
| SF-02 | Search berdasarkan nama submitter | Positive | search: "John" | 1. Ketik nama teknisi | Transaksi yang disubmit oleh "John" tampil | |
| SF-03 | Search berdasarkan nama cabang | Positive | search: "Cabang A" | 1. Ketik nama cabang | Transaksi terkait cabang tersebut tampil | |
| SF-04 | Filter berdasarkan multiple branch | Positive | branch_id: [1, 2] | 1. Pilih 2 cabang di filter | Transaksi dari kedua cabang tampil | |
| SF-05 | Filter berdasarkan date range | Positive | start_date: 2026-05-01, end_date: 2026-05-31 | 1. Set tanggal awal & akhir | Hanya transaksi dalam range tersebut tampil | |
| SF-06 | Filter berdasarkan kategori | Positive | category: "Material" | 1. Pilih kategori "Material" | Hanya transaksi kategori tersebut tampil | |
| SF-07 | Lihat statistik transaksi | Positive | Login sebagai admin | 1. Request /transactions/stats | Response: count per status (pending, approved, completed, dll) | |
| SF-08 | Teknisi hanya melihat transaksi sendiri | Positive | Login sebagai teknisi | 1. Buka daftar transaksi | Hanya transaksi milik teknisi tersebut yang tampil | |
| SF-09 | Search dengan keyword kosong | Negative | search: "" | 1. Submit search kosong | Semua transaksi tampil (tanpa filter search) | |

---

## 29. Modul Rekening Bank Cabang (Detail Lengkap)

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| RBC-07 | Tambah rekening cabang sebagai owner | Positive | bank_name: "BCA", account_number: "1234567890", account_name: "PT ABC" | 1. Login owner 2. Tambah rekening cabang 3. Submit | Rekening tersimpan, activity log tercatat | |
| RBC-08 | Hapus rekening cabang dengan alasan | Positive | reason: "Rekening sudah tidak aktif" | 1. Login owner 2. Hapus rekening 3. Isi alasan 4. Submit | Rekening terhapus, alasan tercatat di activity log | |
| RBC-09 | Tambah rekening cabang sebagai admin (bukan owner) | Negative | Login: admin | 1. Login admin 2. Coba tambah rekening cabang | Tampil error 403 "Hanya Owner yang dapat menambah rekening cabang" | |
| RBC-10 | Edit rekening cabang sebagai admin | Negative | Login: admin | 1. Login admin 2. Coba edit rekening cabang | Tampil error 403 | |
| RBC-11 | Hapus rekening cabang tanpa alasan | Negative | reason: (kosong) | 1. Login owner 2. Hapus tanpa isi alasan | Tampil error validasi "reason wajib diisi" | |
| RBC-12 | Tambah rekening dengan nomor < 5 digit | Negative | account_number: "123" | 1. Isi nomor rekening 3 digit 2. Submit | Tampil error validasi digits_between:5,30 | |
| RBC-13 | Tambah rekening dengan nomor > 30 digit | Negative | account_number: (31+ digit) | 1. Isi nomor rekening > 30 digit 2. Submit | Tampil error validasi | |

---

## 30. Modul Rekening Bank User (Detail Lengkap)

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| RBU-06 | User menambah rekening sendiri | Positive | Login: teknisi, data lengkap | 1. Login teknisi 2. Tambah rekening sendiri 3. Submit | Rekening tersimpan | |
| RBU-07 | Admin menambah rekening untuk user lain | Positive | Login: admin, user_id: teknisi lain | 1. Login admin 2. Tambah rekening untuk teknisi 3. Submit | Rekening tersimpan, activity log tercatat | |
| RBU-08 | Admin hapus rekening user lain dengan alasan | Positive | Login: admin, reason: "Rekening salah" | 1. Login admin 2. Hapus rekening user lain 3. Isi alasan 4. Submit | Rekening terhapus, alasan tercatat | |
| RBU-09 | Teknisi mencoba lihat rekening user lain | Negative | Login: teknisi, akses user_id lain | 1. Login teknisi 2. Akses /user-bank-accounts/{user_lain} | Tampil error 403 Unauthorized | |
| RBU-10 | Admin hapus rekening user lain tanpa alasan | Negative | Login: admin, reason: (kosong) | 1. Login admin 2. Hapus rekening tanpa alasan | Tampil error validasi "reason wajib diisi" | |
| RBU-11 | Tambah rekening dengan nomor non-numerik | Negative | account_number: "ABC123" | 1. Isi nomor rekening non-numerik 2. Submit | Tampil error validasi numeric | |

---

## 31. Modul Hutang Antar Cabang (Branch Debt Settlement)

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| BD-01 | Settle hutang antar cabang | Positive | debt_id: valid, Login: admin/atasan/owner | 1. Buka daftar hutang cabang 2. Klik Settle 3. Konfirmasi | Status hutang berubah settled | |
| BD-02 | Lihat history hutang antar cabang | Positive | debt_id: valid | 1. Klik History pada record hutang | Daftar history settlement tampil | |
| BD-03 | Settle hutang yang sudah settled | Negative | Hutang status: settled | 1. Coba settle hutang yang sudah lunas | Tampil error / tidak ada aksi | |
| BD-04 | Akses settle sebagai teknisi | Negative | Login: teknisi | 1. Login teknisi 2. Akses endpoint settle | Tampil error 403 | |

---

## 32. Modul API Docs & Log Viewer Access

| No | Skenario | Tipe | Input | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|----------|------|-------|-------------------|----------------------|--------|
| AD-01 | Akses API docs sebagai owner | Positive | Login: owner | 1. Login owner 2. Akses halaman API docs | Halaman dokumentasi API tampil | |
| AD-02 | Akses Log Viewer sebagai owner | Positive | Login: owner | 1. Login owner 2. Akses Log Viewer | Log viewer tampil | |
| AD-03 | Akses API docs sebagai admin | Negative | Login: admin | 1. Login admin 2. Akses API docs | Tampil error 403 "Hanya owner yang dapat mengakses" | |
| AD-04 | Akses API docs tanpa login | Negative | Belum login | 1. Akses API docs tanpa login | Redirect ke halaman login | |
| AD-05 | Akses Log Viewer sebagai admin | Negative | Login: admin | 1. Login admin 2. Akses Log Viewer | Akses ditolak | |

---

## Ringkasan Jumlah Test Case (REVISI FINAL)

| No | Modul | Positive | Negative | Total |
|----|-------|----------|----------|-------|
| 1 | Autentikasi (Login/Logout) | 5 | 8 | 13 |
| 2 | Dashboard | 5 | 1 | 6 |
| 3 | Transaksi Rembush (Upload + Form) | 6 | 10 | 16 |
| 4 | Transaksi Pengajuan | 8 | 6 | 14 |
| 5 | Transaksi Pembelian (Gudang) | 3 | 7 | 10 |
| 6 | Manajemen Status Transaksi | 4 | 4 | 8 |
| 7 | Export Transaksi | 6 | 3 | 9 |
| 8 | Pengeluaran Lain - Bayar Hutang | 5 | 3 | 8 |
| 9 | Pengeluaran Lain - Piutang Usaha | 3 | 1 | 4 |
| 10 | Pengeluaran Lain - Prive | 4 | 4 | 8 |
| 11 | Pengeluaran Lain - Gaji | 10 | 9 | 19 |
| 12 | Manajemen User | 8 | 10 | 18 |
| 13 | Manajemen Cabang | 4 | 5 | 9 |
| 14 | Rekening Bank Cabang | 4 | 2 | 6 |
| 15 | Rekening Bank User | 4 | 1 | 5 |
| 16 | Kategori Transaksi | 5 | 2 | 7 |
| 17 | Price Index | 16 | 6 | 22 |
| 18 | Anomali Harga | 6 | 3 | 9 |
| 19 | Notifikasi | 7 | 1 | 8 |
| 20 | Activity Log | 3 | 1 | 4 |
| 21 | Pembayaran - Upload Bukti Cash | 3 | 4 | 7 |
| 22 | Pembayaran - Upload Bukti Transfer | 4 | 4 | 8 |
| 23 | Upload Invoice Pengajuan | 3 | 2 | 5 |
| 24 | Override & Force Approve | 2 | 5 | 7 |
| 25 | Telegram Bot | 9 | 5 | 14 |
| 26 | Item Autocomplete (Master Item) | 4 | 2 | 6 |
| 27 | OCR Status Polling & Monitoring | 5 | 3 | 8 |
| 28 | Pencarian & Filter Transaksi | 8 | 1 | 9 |
| 29 | Rekening Bank Cabang (Detail) | 2 | 5 | 7 |
| 30 | Rekening Bank User (Detail) | 3 | 3 | 6 |
| 31 | Hutang Antar Cabang | 2 | 2 | 4 |
| 32 | API Docs & Log Viewer Access | 2 | 3 | 5 |
| | **TOTAL** | **163** | **126** | **289** |

---

## Catatan Tambahan untuk Modul Baru

### Prasyarat Khusus
- **Modul 21-22 (Pembayaran):** Memerlukan transaksi dengan status `waiting_payment`
- **Modul 23 (Invoice Pengajuan):** Memerlukan transaksi pengajuan yang sudah `approved`
- **Modul 24 (Override/Force Approve):** Memerlukan transaksi `auto-reject` dan `flagged`
- **Modul 25 (Telegram):** Memerlukan bot Telegram aktif dan webhook terkonfigurasi
- **Modul 26 (Autocomplete):** Memerlukan data Master Item di database
- **Modul 27 (OCR):** Memerlukan n8n workflow aktif dan Redis berjalan
- **Modul 28 (Search):** Memerlukan data transaksi yang cukup untuk testing filter
- **Modul 31 (Branch Debt):** Memerlukan transaksi multi-cabang yang sudah menghasilkan hutang

### Integrasi Eksternal yang Perlu Disiapkan
| Layanan | Kegunaan | Konfigurasi |
|---------|----------|-------------|
| Redis | Rate limiting, caching OCR, locks | `REDIS_HOST` di .env |
| n8n | OCR processing, payment verification | `N8N_WEBHOOK` di .env |
| Telegram Bot | Notifikasi real-time | `TELEGRAM_BOT_TOKEN` di .env |
| Laravel Horizon | Queue management | Harus running |
| Laravel Reverb | WebSocket broadcasting | Harus running |

---

*Dokumen ini dibuat secara otomatis berdasarkan analisis mendalam source code project Admin Payment.*  
*Total: 289 test case (163 Positive + 126 Negative) mencakup 32 modul.*
