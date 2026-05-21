# 🔧 Troubleshooting - Solusi Masalah Umum

**Untuk Siapa:** Semua Pengguna  
**Waktu Baca:** 15 menit  
**Level:** Pemula - Menengah

---

## 📋 Daftar Isi
- [Masalah Login & Akses](#masalah-login--akses)
- [Masalah Upload & OCR](#masalah-upload--ocr)
- [Masalah Transaksi](#masalah-transaksi)
- [Masalah Notifikasi](#masalah-notifikasi)
- [Masalah Dashboard & Tampilan](#masalah-dashboard--tampilan)
- [Masalah Pembayaran](#masalah-pembayaran)
- [Masalah Performa](#masalah-performa)
- [Kontak Support](#kontak-support)

---

## Masalah Login & Akses

### 🔴 Masalah 1: "Email atau Password Salah"

**Gejala:**
- Tidak bisa login
- Muncul pesan error "Email atau password salah"

**Penyebab Umum:**
1. Email salah ketik
2. Password salah ketik
3. Caps Lock aktif
4. Copy-paste password dengan spasi tersembunyi

**Solusi:**

**Step 1: Cek Email**
```
✅ Pastikan format email benar
   Contoh: teknisi@whusnet.com (bukan teknisi @whusnet.com)
   
❌ Salah: teknisi @whusnet.com (ada spasi)
❌ Salah: teknisi@whusnet (kurang .com)
✅ Benar: teknisi@whusnet.com
```

**Step 2: Cek Password**
```
✅ Password bersifat case-sensitive
   "Password123" ≠ "password123" ≠ "PASSWORD123"
   
✅ Cek Caps Lock (lampu keyboard)
✅ Ketik manual (jangan copy-paste)
✅ Lihat password dengan klik icon mata (👁️)
```

**Step 3: Reset Password**
```
Jika masih gagal:
1. Hubungi Admin via Telegram/WhatsApp
2. Berikan informasi:
   - Nama lengkap
   - Email akun
   - Role Anda
3. Admin akan reset password
4. Login dengan password baru
5. Ganti password segera
```

---

### 🔴 Masalah 2: "Role Tidak Sesuai"

**Gejala:**
- Login gagal dengan pesan "Role tidak sesuai"
- Sudah yakin email & password benar

**Penyebab:**
- Memilih role yang salah saat login
- Akun Anda memiliki role berbeda dari yang dipilih

**Solusi:**

**Step 1: Coba Semua Role**
```
Coba login dengan role berbeda:
1. Teknisi
2. Admin
3. Atasan
4. Owner

Salah satu pasti berhasil!
```

**Step 2: Konfirmasi dengan Admin**
```
Jika masih bingung:
1. Hubungi Admin
2. Tanyakan: "Role saya apa?"
3. Login dengan role yang benar
```

---

### 🔴 Masalah 3: "Akun Tidak Aktif"

**Gejala:**
- Login gagal dengan pesan "Akun tidak aktif"

**Penyebab:**
- Akun dinonaktifkan oleh Admin/Owner
- Akun belum diaktifkan (user baru)

**Solusi:**
```
1. Hubungi Admin/Owner
2. Tanyakan alasan nonaktif
3. Minta aktivasi akun
4. Tunggu konfirmasi
5. Coba login lagi
```

---

### 🔴 Masalah 4: "Akun Terblokir (Too Many Attempts)"

**Gejala:**
- Login gagal dengan pesan "Terlalu banyak percobaan"
- Tidak bisa login meskipun password benar

**Penyebab:**
- Salah password 5x dalam 15 menit
- Sistem otomatis blokir sementara

**Solusi:**

**Opsi 1: Tunggu (Recommended)**
```
1. Tunggu 15 menit
2. Jangan coba login lagi (akan reset timer)
3. Setelah 15 menit, coba login lagi
```

**Opsi 2: Hubungi Admin (Urgent)**
```
Jika urgent:
1. Hubungi Admin via Telegram/WhatsApp
2. Minta unblock akun
3. Admin akan unblock manual
4. Coba login lagi
```

---

### 🔴 Masalah 5: Halaman Login Tidak Bisa Dibuka

**Gejala:**
- Browser tidak bisa buka halaman login
- Muncul "This site can't be reached" atau "Connection timed out"

**Penyebab:**
1. Koneksi internet bermasalah
2. Server sedang maintenance
3. URL salah
4. Firewall/antivirus blokir

**Solusi:**

**Step 1: Cek Koneksi Internet**
```
1. Buka website lain (google.com, youtube.com)
2. Jika tidak bisa, masalah di internet Anda
3. Restart router/modem
4. Coba pakai data seluler
```

**Step 2: Cek URL**
```
✅ URL yang benar: https://admin-payment.whusnet.com
❌ Jangan: http://... (tanpa 's')
❌ Jangan: admin-payment.whusnet.com/login (tambahan /login)

Copy-paste URL dari dokumentasi atau bookmark
```

**Step 3: Clear Cache & Cookies**
```
Chrome:
1. Tekan Ctrl + Shift + Delete
2. Pilih "All time"
3. Centang "Cookies" dan "Cached images"
4. Klik "Clear data"
5. Restart browser
6. Coba lagi

Firefox:
1. Tekan Ctrl + Shift + Delete
2. Pilih "Everything"
3. Centang "Cookies" dan "Cache"
4. Klik "Clear Now"
5. Restart browser
6. Coba lagi
```

**Step 4: Coba Browser Lain**
```
Jika masih gagal:
1. Coba browser berbeda (Chrome, Firefox, Edge)
2. Jika berhasil, masalah di browser lama
3. Update browser lama atau pakai yang baru
```

**Step 5: Hubungi IT Support**
```
Jika semua gagal:
1. Hubungi IT Support
2. Berikan informasi:
   - Browser yang digunakan
   - Pesan error yang muncul
   - Screenshot error (jika bisa)
3. IT akan cek server
```

---

## Masalah Upload & OCR

### 🔴 Masalah 6: "Upload Gagal"

**Gejala:**
- File tidak bisa diupload
- Muncul error saat upload
- Progress bar stuck di tengah

**Penyebab:**
1. File terlalu besar (> 5 MB)
2. Format file tidak didukung
3. Koneksi internet lambat/putus
4. Browser issue

**Solusi:**

**Step 1: Cek Ukuran File**
```
Windows:
1. Klik kanan file
2. Pilih "Properties"
3. Lihat "Size"
4. Jika > 5 MB, compress dulu

Android:
1. Buka Gallery
2. Pilih foto
3. Tap "Details" atau "Info"
4. Lihat ukuran file
```

**Cara Compress Foto:**
```
Online (Recommended):
1. Buka tinypng.com atau compressor.io
2. Upload foto
3. Download hasil compress
4. Upload ke sistem

Android:
1. Install app "Photo Compress"
2. Pilih foto
3. Compress
4. Upload hasil compress

iPhone:
1. Install app "Image Size"
2. Pilih foto
3. Resize/compress
4. Upload hasil
```

**Step 2: Cek Format File**
```
✅ Format yang didukung:
   - JPG / JPEG
   - PNG

❌ Format yang TIDAK didukung:
   - PDF
   - HEIC (iPhone default)
   - BMP
   - GIF
   - WEBP

Jika format salah:
1. Convert ke JPG/PNG
2. Gunakan online converter (cloudconvert.com)
3. Upload hasil convert
```

**Step 3: Cek Koneksi Internet**
```
1. Pastikan koneksi stabil
2. Jangan pindah-pindah WiFi saat upload
3. Jika pakai data seluler, pastikan sinyal kuat
4. Jangan minimize browser saat upload
5. Tunggu sampai selesai (jangan refresh)
```

**Step 4: Coba Lagi**
```
Jika masih gagal:
1. Refresh halaman (F5)
2. Login ulang
3. Coba upload lagi
4. Jika masih gagal, hubungi support
```

---

### 🔴 Masalah 7: "OCR Gagal / Low Confidence"

**Gejala:**
- OCR tidak bisa baca nota
- Muncul status "Low Confidence"
- Data hasil OCR tidak akurat

**Penyebab:**
1. Foto nota tidak jelas (blur, gelap, silau)
2. Nota rusak/kusut
3. Format nota tidak standar
4. Tulisan tangan (tidak bisa dibaca OCR)

**Solusi:**

**Step 1: Foto Ulang dengan Benar**
```
✅ Tips Foto Nota yang Baik:

1. Cahaya Cukup
   - Foto di tempat terang
   - Hindari bayangan
   - Jangan pakai flash langsung (bisa silau)

2. Fokus Tajam
   - Tap layar untuk fokus (HP)
   - Pastikan teks terbaca jelas
   - Jangan goyang saat foto

3. Nota Rata
   - Ratakan nota di meja
   - Jangan terlipat atau kusut
   - Foto dari atas (90 derajat)

4. Frame Penuh
   - Seluruh nota masuk frame
   - Tidak terpotong
   - Tidak terlalu banyak background

5. Resolusi Cukup
   - Gunakan kamera belakang (bukan depan)
   - Jangan zoom digital (dekat saja)
   - Minimal 1080p
```

**Step 2: Edit Foto (Jika Perlu)**
```
Jika foto gelap/silau:
1. Buka foto di Gallery/Photos
2. Edit → Brightness/Contrast
3. Naikkan brightness jika gelap
4. Turunkan exposure jika silau
5. Save dan upload ulang
```

**Step 3: Input Manual**
```
Jika OCR tetap gagal:
1. Sistem akan tetap simpan foto nota
2. Anda input data manual:
   - Vendor
   - Item
   - Nominal
   - Tanggal
3. Admin akan verifikasi manual
4. Submit transaksi
```

---

### 🔴 Masalah 8: "Duplikasi Terdeteksi"

**Gejala:**
- Upload ditolak dengan pesan "Duplikasi terdeteksi"
- Padahal yakin belum pernah upload nota ini

**Penyebab:**
- Nota ini sudah pernah diupload sebelumnya
- Sistem deteksi berdasarkan hash file
- Mungkin upload oleh user lain atau upload lama

**Solusi:**

**Step 1: Cek Riwayat Transaksi**
```
1. Buka halaman "Transaksi"
2. Cari dengan:
   - Tanggal nota
   - Vendor/toko
   - Nominal
3. Jika ketemu, berarti memang sudah pernah diupload
4. Jangan submit lagi (duplikasi)
```

**Step 2: Foto Ulang (Jika Yakin Belum Upload)**
```
Jika yakin belum pernah upload:
1. Foto ulang nota (jangan screenshot)
2. Gunakan kamera berbeda (HP lain)
3. Crop sedikit (ubah hash file)
4. Upload foto baru
```

**Step 3: Hubungi Admin**
```
Jika masih ditolak:
1. Hubungi Admin
2. Jelaskan situasi
3. Kirim foto nota via Telegram/WhatsApp
4. Admin akan cek manual
5. Admin bisa override jika memang valid
```

---

### 🔴 Masalah 9: "Nota Kadaluarsa (Auto-Reject)"

**Gejala:**
- Upload ditolak dengan status "Auto-Reject"
- Alasan: "Nota lebih dari 2 hari"

**Penyebab:**
- Nota berumur > 2 hari kalender
- Sistem otomatis reject untuk mencegah fraud

**Solusi:**

**Jika Nota Memang Valid:**
```
1. Hubungi Admin/Owner
2. Jelaskan alasan keterlambatan:
   - Contoh: "Lupa upload karena sakit"
   - Contoh: "Baru dapat nota dari vendor"
   - Contoh: "Internet mati 3 hari"
3. Admin/Owner bisa **Override** status
4. Transaksi akan diproses normal
```

**Jika Nota Memang Kadaluarsa:**
```
Nota > 2 hari tanpa alasan valid:
1. Tidak bisa diproses
2. Konsultasi dengan Management
3. Mungkin perlu approval khusus
4. Atau tidak bisa di-reimburse
```

**Tips Mencegah:**
```
✅ Upload nota segera setelah belanja
✅ Jangan tunda-tunda
✅ Set reminder di HP
✅ Foto nota langsung setelah bayar
```

---

## Masalah Transaksi

### 🔴 Masalah 10: "Transaksi Tidak Muncul"

**Gejala:**
- Transaksi yang baru disubmit tidak muncul di list
- Sudah refresh tapi tetap tidak ada

**Penyebab:**
1. Submit belum berhasil
2. Filter pencarian aktif
3. Bug sistem

**Solusi:**

**Step 1: Cek Notifikasi**
```
Apakah ada notifikasi "Transaksi Berhasil Disubmit"?

✅ Jika ADA:
   - Transaksi berhasil tersimpan
   - Lanjut ke Step 2

❌ Jika TIDAK ADA:
   - Submit belum berhasil
   - Coba submit ulang
```

**Step 2: Cek Filter**
```
1. Buka halaman "Transaksi"
2. Cek filter yang aktif:
   - Status: Pastikan "Semua Status" atau "Pending"
   - Tanggal: Pastikan range tanggal benar
   - Cabang: Pastikan "Semua Cabang"
3. Reset semua filter
4. Refresh halaman (F5)
```

**Step 3: Cari Manual**
```
1. Gunakan search box
2. Cari dengan:
   - ID Transaksi (jika tahu)
   - Vendor/toko
   - Nominal
   - Tanggal
3. Jika ketemu, berarti ada (cuma kefilter)
```

**Step 4: Hubungi Support**
```
Jika masih tidak muncul:
1. Hubungi Admin
2. Berikan informasi:
   - Waktu submit
   - Vendor/toko
   - Nominal
   - Screenshot (jika ada)
3. Admin akan cek database
```

---

### 🔴 Masalah 11: "Tidak Bisa Edit Transaksi"

**Gejala:**
- Tombol "Edit" tidak muncul
- Atau muncul tapi disabled (abu-abu)

**Penyebab:**
1. Status transaksi sudah `Completed`
2. Anda tidak punya hak edit (Teknisi)
3. Transaksi dalam fase pelunasan

**Solusi:**

**Cek Status Transaksi:**
```
Status "Completed":
- Transaksi sudah selesai
- Tidak bisa diedit oleh siapapun (termasuk Owner)
- Untuk audit trail
- Jika ada kesalahan, hubungi Owner

Status "Pending/Approved/Waiting Payment":
- Bisa diedit oleh Admin/Atasan/Owner
- Teknisi tidak bisa edit
- Jika perlu edit, hubungi Admin
```

**Cek Role Anda:**
```
Teknisi:
- Tidak bisa edit transaksi setelah submit
- Jika ada kesalahan, hubungi Admin untuk cancel
- Submit ulang dengan data yang benar

Admin/Atasan/Owner:
- Bisa edit transaksi (tergantung status)
- Klik tombol "Edit" di detail transaksi
```

---

### 🔴 Masalah 12: "Transaksi Di-flag (Selisih Nominal)"

**Gejala:**
- Status transaksi jadi "Flagged"
- Alasan: "Selisih nominal transfer vs nota"

**Penyebab:**
- AI deteksi nominal di struk transfer ≠ nominal di nota
- Contoh: Nota Rp 150.000, tapi transfer Rp 145.000

**Solusi:**

**Untuk Teknisi:**
```
1. Cek detail transaksi
2. Lihat alasan flag
3. Jika memang ada selisih:
   - Tunggu Admin/Owner follow up
   - Mungkin ada potongan/biaya admin
   - Mungkin salah transfer
4. Jika tidak ada selisih (AI salah):
   - Hubungi Admin
   - Admin akan review manual
```

**Untuk Admin/Owner:**
```
1. Review transaksi yang di-flag
2. Cek:
   - Nota asli
   - Struk transfer
   - Nominal yang benar
3. Jika memang ada selisih:
   - Investigasi penyebab
   - Koordinasi dengan Teknisi
4. Jika tidak ada selisih (AI salah):
   - Klik "Force Approve"
   - Tulis alasan: "AI salah deteksi, nominal sudah benar"
   - Submit
```

---

## Masalah Notifikasi

### 🔴 Masalah 13: "Notifikasi Tidak Masuk"

**Gejala:**
- Tidak menerima notifikasi in-app
- Tidak menerima notifikasi Telegram

**Penyebab:**
1. Notifikasi browser diblokir
2. Telegram bot belum disetup
3. Koneksi WebSocket terputus

**Solusi:**

**Notifikasi In-App:**
```
Step 1: Cek Permission Browser

Chrome:
1. Klik icon gembok di address bar
2. Pilih "Site settings"
3. Cari "Notifications"
4. Pastikan "Allow"

Firefox:
1. Klik icon gembok di address bar
2. Pilih "Connection secure"
3. Klik "More information"
4. Tab "Permissions"
5. Cari "Notifications"
6. Pastikan "Allow"

Step 2: Refresh Halaman
1. Tekan F5
2. Atau logout dan login lagi
3. Coba trigger notifikasi (submit transaksi)
```

**Notifikasi Telegram:**
```
Step 1: Setup Telegram Bot
1. Buka Telegram
2. Cari bot: @WhusnetAdminBot (sesuaikan nama)
3. Klik "Start"
4. Masukkan kode verifikasi dari sistem
5. Selesai!

Step 2: Cek Status Bot
1. Kirim pesan "/status" ke bot
2. Bot akan reply status koneksi
3. Jika tidak reply, hubungi Admin

Step 3: Test Notifikasi
1. Submit transaksi test
2. Cek apakah dapat notifikasi
3. Jika tidak, hubungi Admin
```

---

### 🔴 Masalah 14: "Badge Notifikasi Tidak Update"

**Gejala:**
- Badge notifikasi (angka merah) tidak update
- Padahal sudah baca semua notifikasi

**Penyebab:**
- Cache browser
- WebSocket tidak sync

**Solusi:**
```
1. Refresh halaman (F5)
2. Atau logout dan login lagi
3. Badge akan update otomatis
4. Jika masih tidak update, clear cache browser
```

---

## Masalah Dashboard & Tampilan

### 🔴 Masalah 15: "Dashboard Tidak Muncul / Blank"

**Gejala:**
- Dashboard kosong/blank
- Atau loading terus

**Penyebab:**
1. JavaScript error
2. Browser tidak support
3. Extension browser mengganggu

**Solusi:**

**Step 1: Refresh Halaman**
```
1. Tekan F5
2. Atau Ctrl + Shift + R (hard refresh)
3. Tunggu beberapa detik
```

**Step 2: Clear Cache**
```
1. Tekan Ctrl + Shift + Delete
2. Clear cache & cookies
3. Restart browser
4. Login lagi
```

**Step 3: Disable Extension**
```
1. Disable semua extension browser
   - AdBlock
   - Privacy Badger
   - dll.
2. Refresh halaman
3. Jika berhasil, enable extension satu per satu
4. Cari extension yang bermasalah
```

**Step 4: Coba Browser Lain**
```
1. Coba Chrome/Firefox/Edge
2. Jika berhasil, masalah di browser lama
3. Update browser lama
```

**Step 5: Cek Console Error**
```
1. Tekan F12 (Developer Tools)
2. Tab "Console"
3. Lihat error yang muncul (warna merah)
4. Screenshot error
5. Kirim ke IT Support
```

---

### 🔴 Masalah 16: "Tampilan Berantakan / Tidak Rapi"

**Gejala:**
- Layout berantakan
- Tombol overlap
- Teks terpotong

**Penyebab:**
1. Zoom browser tidak 100%
2. Cache CSS lama
3. Browser tidak support

**Solusi:**

**Step 1: Reset Zoom**
```
1. Tekan Ctrl + 0 (angka nol)
2. Zoom akan reset ke 100%
3. Refresh halaman
```

**Step 2: Clear Cache**
```
1. Tekan Ctrl + Shift + Delete
2. Clear "Cached images and files"
3. Restart browser
4. Login lagi
```

**Step 3: Update Browser**
```
1. Cek versi browser
2. Update ke versi terbaru
3. Restart browser
4. Coba lagi
```

---

## Masalah Pembayaran

### 🔴 Masalah 17: "Uang Belum Masuk Padahal Status Completed"

**Gejala:**
- Status transaksi sudah `Completed`
- Tapi uang belum masuk rekening

**Penyebab:**
1. Transfer belum diproses bank (1-3 hari kerja)
2. Rekening tujuan salah
3. Admin belum transfer (status salah)

**Solusi:**

**Step 1: Cek Waktu**
```
Berapa lama sejak status Completed?

< 1 hari kerja:
- Tunggu dulu
- Transfer butuh waktu 1-3 hari kerja

1-3 hari kerja:
- Masih normal
- Cek rekening secara berkala

> 3 hari kerja:
- Lanjut ke Step 2
```

**Step 2: Cek Rekening Tujuan**
```
1. Buka detail transaksi
2. Cek rekening tujuan yang tercatat
3. Apakah sesuai dengan rekening Anda?
4. Jika salah, hubungi Admin segera
```

**Step 3: Hubungi Admin**
```
1. Hubungi Admin via Telegram/WhatsApp
2. Berikan informasi:
   - ID Transaksi
   - Tanggal Completed
   - Nominal
   - Rekening tujuan
3. Admin akan cek status transfer
4. Jika belum transfer, Admin akan proses
5. Jika sudah transfer, Admin akan cek bukti
```

---

### 🔴 Masalah 18: "Nominal Transfer Kurang dari Nota"

**Gejala:**
- Terima transfer tapi nominalnya kurang
- Contoh: Nota Rp 150.000, terima Rp 145.000

**Penyebab:**
1. Biaya admin bank
2. Salah transfer
3. Potongan lain-lain

**Solusi:**
```
1. Cek detail transaksi
2. Lihat nominal yang disetujui
3. Jika ada potongan, biasanya ada catatan
4. Jika tidak ada catatan:
   - Hubungi Admin
   - Tanyakan alasan selisih
   - Minta klarifikasi
5. Jika memang salah transfer:
   - Admin akan transfer kekurangannya
```

---

## Masalah Performa

### 🔴 Masalah 19: "Sistem Lambat / Loading Lama"

**Gejala:**
- Halaman loading lama
- Klik tombol tidak responsif
- Sistem terasa berat

**Penyebab:**
1. Koneksi internet lambat
2. Server sedang sibuk
3. Browser terlalu banyak tab
4. Cache browser penuh

**Solusi:**

**Step 1: Cek Koneksi Internet**
```
1. Test speed internet (speedtest.net)
2. Jika < 1 Mbps, koneksi lambat
3. Restart router/modem
4. Atau pakai data seluler
```

**Step 2: Tutup Tab Lain**
```
1. Tutup tab browser yang tidak perlu
2. Tutup aplikasi lain yang berat
3. Restart browser
4. Buka sistem lagi
```

**Step 3: Clear Cache**
```
1. Tekan Ctrl + Shift + Delete
2. Clear cache & cookies
3. Restart browser
4. Login lagi
```

**Step 4: Restart Device**
```
1. Restart laptop/HP
2. Buka browser lagi
3. Login ke sistem
4. Cek apakah lebih cepat
```

---

## Kontak Support

### 📞 Kapan Harus Hubungi Support?

**Hubungi Support Jika:**
- ✅ Sudah coba semua solusi di atas tapi masih gagal
- ✅ Masalah urgent (tidak bisa login, transaksi hilang, dll.)
- ✅ Masalah yang tidak ada di troubleshooting ini
- ✅ Butuh bantuan teknis (reset password, unblock akun, dll.)

---

### 📱 Channel Support

| Channel | Untuk Apa | Response Time |
|---------|-----------|---------------|
| 💬 **Telegram** @WhusnetSupport | Pertanyaan urgent, masalah teknis | < 1 jam (jam kerja) |
| 📧 **Email** support@whusnet.com | Pertanyaan detail, feedback, laporan bug | < 24 jam |
| 📱 **WhatsApp** +62 xxx-xxxx-xxxx | Koordinasi pembayaran, konfirmasi | < 2 jam (jam kerja) |

**Jam Operasional Support:**  
Senin - Jumat, 08:00 - 17:00 WIB

---

### 📝 Informasi yang Perlu Disiapkan

Saat hubungi support, siapkan informasi berikut:

**Untuk Masalah Login:**
- Email akun
- Role Anda
- Pesan error yang muncul
- Screenshot (jika bisa)

**Untuk Masalah Transaksi:**
- ID Transaksi (jika ada)
- Tanggal transaksi
- Vendor/toko
- Nominal
- Deskripsi masalah
- Screenshot (jika bisa)

**Untuk Masalah Teknis:**
- Browser yang digunakan (Chrome, Firefox, dll.)
- Versi browser
- Operating System (Windows, Mac, Android, iOS)
- Pesan error yang muncul
- Screenshot error
- Langkah yang sudah dicoba

---

## 📚 Dokumentasi Terkait

- 📖 [Pengenalan Sistem](00_PENGENALAN_SISTEM.md)
- 🚀 [Cara Memulai](01_MEMULAI.md)
- 💰 [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md)
- 📝 [Panduan Pengajuan](04_PENGAJUAN_PEMBELIAN.md)
- ❓ [FAQ Umum](17_FAQ_UMUM.md) *(Coming Soon)*

---

**Jangan ragu untuk hubungi support jika butuh bantuan!** 🤝

Kami siap membantu Anda menyelesaikan masalah.

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
