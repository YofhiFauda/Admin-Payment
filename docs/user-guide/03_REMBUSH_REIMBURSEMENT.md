# 💰 Panduan Rembush (Reimbursement)

**Untuk Siapa:** Teknisi, Admin, Atasan, Owner  
**Waktu Baca:** 10 menit  
**Level:** Pemula

---

## 📋 Daftar Isi
- [Apa itu Rembush?](#apa-itu-rembush)
- [Kapan Menggunakan Rembush?](#kapan-menggunakan-rembush)
- [Cara Mengajukan Rembush](#cara-mengajukan-rembush)
- [Memahami Status Transaksi](#memahami-status-transaksi)
- [Tips Foto Nota yang Baik](#tips-foto-nota-yang-baik)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## Apa itu Rembush?

**Rembush** (Reimbursement) adalah **penggantian biaya** yang sudah Anda keluarkan untuk keperluan perusahaan.

### Contoh Kasus 📝

**Skenario:**
- Anda beli kabel LAN seharga Rp 150.000 untuk instalasi di rumah klien
- Anda bayar pakai uang pribadi
- Anda foto nota pembelian
- Anda upload ke sistem
- Sistem baca nota otomatis (OCR AI)
- Admin approve
- Uang Rp 150.000 ditransfer ke rekening Anda

**Hasil:** Anda dapat penggantian uang yang sudah dikeluarkan! 💰

---

## Kapan Menggunakan Rembush?

### ✅ Gunakan Rembush Untuk:

1. **Pembelian Mendadak** yang tidak bisa ditunda
   - Contoh: Kabel putus saat instalasi, harus beli segera

2. **Pembelian Kecil** yang tidak perlu approval dulu
   - Contoh: Konektor, kabel ties, isolasi

3. **Biaya Operasional** yang sudah dikeluarkan
   - Contoh: Bensin, tol, parkir, makan saat lembur

4. **Pembelian Darurat** di luar jam kerja
   - Contoh: Beli spare part saat weekend untuk perbaikan urgent

### ❌ JANGAN Gunakan Rembush Untuk:

1. **Pembelian Besar** yang bisa direncanakan
   - ❌ Beli router Rp 5 juta → Gunakan **Pengajuan** dulu!

2. **Pembelian Rutin** yang bisa dijadwalkan
   - ❌ Stok bulanan kabel → Gunakan **Pengajuan** dulu!

3. **Pembelian Pribadi** yang tidak ada hubungannya dengan pekerjaan
   - ❌ Beli pulsa pribadi, makan pribadi, dll.

---

## Cara Mengajukan Rembush

### 📸 Langkah 1: Upload Foto Nota

1. **Login** ke sistem
2. Klik tombol **"Buat Transaksi"** (tombol biru di kanan atas)
3. Pilih **"Rembush"**
4. Klik **"Pilih File"** atau **"Ambil Foto"** (jika dari HP)
5. Pilih foto nota dari galeri atau ambil foto langsung
6. Klik **"Upload"**

**Tips Foto Nota:**
- ✅ Pastikan semua teks terbaca jelas
- ✅ Cahaya cukup (tidak gelap atau silau)
- ✅ Nota tidak terlipat atau rusak
- ✅ Fokus kamera tajam (tidak blur)

**Contoh Foto yang Baik:**
```
┌─────────────────────────┐
│  TOKO ELEKTRONIK JAYA   │
│  Jl. Sudirman No. 123   │
│  ─────────────────────  │
│  Kabel LAN 10m  150.000 │
│  ─────────────────────  │
│  TOTAL:         150.000 │
│  Tanggal: 21/05/2026    │
└─────────────────────────┘
```

---

### ⏳ Langkah 2: Tunggu OCR Processing (30 detik)

Setelah upload, sistem akan:
1. **Mengecek duplikasi** - Apakah nota ini pernah diupload sebelumnya?
2. **Mengecek tanggal** - Apakah nota masih valid (maksimal 2 hari)?
3. **Membaca nota dengan AI** - Ekstraksi vendor, item, dan nominal

**Anda akan melihat:**
```
🔄 Memproses nota Anda...
⏱️ Estimasi: 30 detik

[Progress Bar: ████████░░ 80%]

✅ Duplikasi: OK
✅ Tanggal: OK
🔄 AI Extraction: Processing...
```

**Jika ada masalah:**
- ❌ **Duplikasi Terdeteksi:** Nota ini sudah pernah diupload. Sistem akan tolak otomatis.
- ❌ **Nota Kadaluarsa:** Nota lebih dari 2 hari. Status akan `auto-reject` (bisa di-override oleh Admin/Owner).
- ⚠️ **Low Confidence:** AI tidak yakin dengan hasil baca. Anda perlu review manual lebih teliti.

---

### ✍️ Langkah 3: Review & Lengkapi Data

Setelah OCR selesai, sistem akan menampilkan form yang **sudah terisi otomatis**:

**Form yang Terisi Otomatis:**
- ✅ **Vendor/Toko:** "TOKO ELEKTRONIK JAYA"
- ✅ **Item:** "Kabel LAN 10m"
- ✅ **Nominal:** "Rp 150.000"
- ✅ **Tanggal:** "21/05/2026"

**Yang Perlu Anda Lengkapi:**
- 📝 **Kategori:** Pilih dari dropdown (contoh: "Instalasi", "Pembelian Alat", dll.)
- 🏢 **Cabang:** Pilih cabang mana yang menanggung biaya ini
- 📄 **Keterangan:** (Opsional) Tambahkan catatan jika perlu

**Contoh Pengisian:**
```
┌─────────────────────────────────────┐
│ Vendor: TOKO ELEKTRONIK JAYA        │ ← Auto-filled
│ Item: Kabel LAN 10m                 │ ← Auto-filled
│ Nominal: Rp 150.000                 │ ← Auto-filled
│ Tanggal: 21/05/2026                 │ ← Auto-filled
│                                     │
│ Kategori: [Instalasi ▼]            │ ← Pilih manual
│ Cabang: [Cabang Sudirman ▼]        │ ← Pilih manual
│ Keterangan: Untuk instalasi di     │ ← Opsional
│             rumah Pak Budi          │
│                                     │
│ [Batal]  [Submit Transaksi]        │
└─────────────────────────────────────┘
```

**Penting:**
- ⚠️ **Cek ulang data** yang terisi otomatis. Jika ada yang salah, edit manual.
- ⚠️ **Pastikan nominal benar** sesuai nota. Jika salah, sistem akan flag saat verifikasi pembayaran.

---

### ✅ Langkah 4: Submit Transaksi

1. Pastikan semua data sudah benar
2. Klik tombol **"Submit Transaksi"**
3. Sistem akan menampilkan konfirmasi:

```
┌─────────────────────────────────────┐
│  ✅ Transaksi Berhasil Disubmit!    │
│                                     │
│  ID Transaksi: REM-20260521-00123   │
│  Status: Pending                    │
│                                     │
│  Transaksi Anda sedang menunggu     │
│  approval dari Admin/Atasan.        │
│                                     │
│  Anda akan menerima notifikasi      │
│  saat transaksi disetujui.          │
│                                     │
│  [Lihat Detail]  [Buat Lagi]       │
└─────────────────────────────────────┘
```

**Setelah Submit:**
- ✅ Transaksi masuk ke daftar **Pending**
- ✅ Admin/Atasan akan menerima notifikasi
- ✅ Anda akan menerima notifikasi saat ada update

---

### ⏰ Langkah 5: Tunggu Approval

**Timeline Normal:**
1. **Submit** → Status: `Pending` (Anda)
2. **Review** → Admin/Atasan cek transaksi (1-2 hari kerja)
3. **Approve** → Status berubah:
   - Jika **< Rp 1.000.000** → Status: `Waiting Payment` (langsung)
   - Jika **≥ Rp 1.000.000** → Status: `Approved` (tunggu Owner approve)
4. **Owner Approve** (jika ≥ 1 Jt) → Status: `Waiting Payment`
5. **Upload Bukti Bayar** → Admin upload bukti transfer
6. **Verifikasi** → Sistem cek nominal transfer vs nota
7. **Selesai** → Status: `Completed` (uang sudah ditransfer!)

**Notifikasi yang Akan Anda Terima:**
- 🔔 **"Transaksi Anda disetujui"** (saat Admin approve)
- 🔔 **"Menunggu approval Owner"** (jika ≥ 1 Jt)
- 🔔 **"Pembayaran sedang diproses"** (saat Admin upload bukti)
- 🔔 **"Pembayaran selesai"** (saat status `Completed`)

---

### 💸 Langkah 6: Konfirmasi Penerimaan (Jika Cash)

**Jika metode pembayaran adalah Cash:**

1. Admin akan upload bukti bayar dengan metode **"Cash"**
2. Anda akan menerima notifikasi **Telegram**:

```
💰 Konfirmasi Penerimaan Uang Cash

Transaksi: REM-20260521-00123
Nominal: Rp 150.000
Dari: Admin Budi

Apakah Anda sudah menerima uang ini?

[✅ Ya, Sudah Terima]  [❌ Belum Terima]
```

3. Klik **"✅ Ya, Sudah Terima"** jika sudah terima uang
4. Status transaksi akan berubah menjadi `Completed`

**Jika Belum Terima:**
- Klik **"❌ Belum Terima"**
- Hubungi Admin untuk koordinasi

---

## Memahami Status Transaksi

### Status Lifecycle

```
┌─────────┐
│ Pending │ ← Baru disubmit, tunggu review
└────┬────┘
     │
     ├─→ [Rejected] ← Ditolak (ada alasan penolakan)
     │
     ├─→ [Approved] ← Disetujui Admin (jika ≥ 1 Jt, tunggu Owner)
     │       │
     │       └─→ [Waiting Payment] ← Owner sudah approve
     │
     └─→ [Waiting Payment] ← Disetujui Admin (jika < 1 Jt)
             │
             └─→ [Completed] ← Pembayaran selesai ✅
```

### Detail Setiap Status

| Status | Arti | Apa yang Terjadi | Apa yang Harus Dilakukan |
|--------|------|------------------|--------------------------|
| **Pending** | Menunggu review | Admin/Atasan sedang review transaksi Anda | Tunggu notifikasi (1-2 hari kerja) |
| **Approved** | Disetujui Admin | Transaksi ≥ 1 Jt, tunggu Owner approve | Tunggu notifikasi Owner approval |
| **Waiting Payment** | Menunggu pembayaran | Admin sedang proses transfer | Tunggu notifikasi pembayaran selesai |
| **Completed** | Selesai | Uang sudah ditransfer ke rekening Anda | Cek rekening Anda! 💰 |
| **Rejected** | Ditolak | Transaksi tidak disetujui (ada alasan) | Baca alasan penolakan, perbaiki jika perlu |
| **Flagged** | Di-flag | Ada selisih nominal transfer vs nota | Admin akan follow up |
| **Auto-Reject** | Ditolak otomatis | Nota kadaluarsa (> 2 hari) | Hubungi Admin untuk override (jika valid) |

---

## Tips Foto Nota yang Baik

### ✅ DO (Lakukan)

1. **Cahaya Cukup**
   - Foto di tempat terang
   - Hindari bayangan
   - Jangan foto di tempat gelap

2. **Fokus Tajam**
   - Pastikan teks terbaca jelas
   - Jangan blur atau goyang
   - Gunakan mode fokus otomatis

3. **Nota Rata**
   - Ratakan nota di permukaan datar
   - Jangan terlipat atau kusut
   - Foto dari atas (90 derajat)

4. **Frame Penuh**
   - Pastikan seluruh nota masuk frame
   - Jangan terpotong
   - Tidak perlu terlalu banyak background

5. **Resolusi Cukup**
   - Minimal 1080p
   - Jangan terlalu kecil
   - Jangan terlalu besar (max 5 MB)

### ❌ DON'T (Jangan)

1. **Foto Blur**
   - AI tidak bisa baca teks blur
   - Akan gagal OCR

2. **Cahaya Silau**
   - Teks tidak terbaca karena silau
   - Hindari flash langsung

3. **Nota Kusut**
   - Teks terpotong atau tidak terbaca
   - Ratakan dulu sebelum foto

4. **Foto Miring**
   - AI sulit baca teks miring
   - Foto dari atas (90 derajat)

5. **Background Ramai**
   - Bisa mengganggu OCR
   - Gunakan background polos

---

## Troubleshooting

### Masalah 1: "Duplikasi Terdeteksi"

**Penyebab:**
- Nota ini sudah pernah diupload sebelumnya
- Sistem deteksi berdasarkan hash file

**Solusi:**
1. Cek riwayat transaksi Anda
2. Jika memang belum pernah upload, coba:
   - Foto ulang nota (jangan screenshot)
   - Gunakan kamera berbeda
   - Crop foto sedikit
3. Jika masih gagal, hubungi Admin

---

### Masalah 2: "Nota Kadaluarsa (Auto-Reject)"

**Penyebab:**
- Nota lebih dari 2 hari kalender
- Sistem otomatis reject untuk mencegah fraud

**Solusi:**
1. Jika nota memang valid (ada alasan keterlambatan):
   - Hubungi Admin/Owner
   - Jelaskan alasan keterlambatan
   - Admin/Owner bisa **Override** status
2. Jika nota memang kadaluarsa:
   - Tidak bisa diproses
   - Konsultasi dengan Admin

---

### Masalah 3: "OCR Gagal / Low Confidence"

**Penyebab:**
- Foto nota tidak jelas
- Teks tidak terbaca
- Format nota tidak standar

**Solusi:**
1. **Foto ulang** dengan tips di atas
2. **Input manual** jika OCR tetap gagal:
   - Sistem akan tetap simpan foto nota
   - Anda input data manual
   - Admin akan verifikasi manual
3. **Hubungi Admin** jika kesulitan

---

### Masalah 4: "Upload Gagal"

**Penyebab:**
- File terlalu besar (> 5 MB)
- Format file tidak didukung
- Koneksi internet bermasalah

**Solusi:**
1. **Compress foto** jika terlalu besar:
   - Gunakan app compress (TinyPNG, dll.)
   - Atau foto dengan resolusi lebih rendah
2. **Cek format file:**
   - Gunakan JPG atau PNG
   - Jangan gunakan PDF atau format lain
3. **Cek koneksi internet:**
   - Pastikan koneksi stabil
   - Coba lagi setelah koneksi baik

---

### Masalah 5: "Transaksi Tidak Muncul"

**Penyebab:**
- Submit belum berhasil
- Filter pencarian aktif
- Bug sistem

**Solusi:**
1. **Cek notifikasi:**
   - Apakah ada notifikasi "Transaksi Berhasil"?
   - Jika tidak, submit ulang
2. **Cek filter:**
   - Pastikan filter "Semua Status"
   - Pastikan filter tanggal benar
3. **Refresh halaman:**
   - Tekan F5 atau refresh browser
4. **Hubungi Admin** jika masih tidak muncul

---

## FAQ

**Q: Berapa lama proses approval?**  
A: Biasanya 1-2 hari kerja. Jika urgent, hubungi Admin via Telegram.

**Q: Apakah bisa edit transaksi yang sudah disubmit?**  
A: Tidak bisa. Jika ada kesalahan, hubungi Admin untuk dibatalkan, lalu submit ulang.

**Q: Apakah bisa upload nota yang sudah lama (> 2 hari)?**  
A: Sistem akan auto-reject, tapi Admin/Owner bisa override jika ada alasan valid.

**Q: Apakah bisa upload beberapa nota sekaligus?**  
A: Tidak, satu transaksi = satu nota. Jika ada beberapa nota, submit satu per satu.

**Q: Bagaimana jika nota hilang/rusak?**  
A: Jika nota fisik hilang tapi sudah difoto, tidak masalah. Sistem sudah simpan foto nota.

**Q: Apakah bisa claim reimbursement untuk transaksi bulan lalu?**  
A: Tergantung kebijakan perusahaan. Konsultasi dengan Admin/Owner.

**Q: Berapa lama uang cair setelah approved?**  
A: Biasanya 1-3 hari kerja setelah status `Completed`. Cek rekening Anda.

**Q: Apakah ada batas maksimal nominal Rembush?**  
A: Tidak ada batas, tapi transaksi ≥ Rp 1.000.000 perlu approval Owner.

**Q: Apakah bisa Rembush untuk makan pribadi?**  
A: Tidak, hanya untuk keperluan perusahaan. Makan pribadi tidak bisa di-reimburse.

**Q: Bagaimana jika Admin reject transaksi saya?**  
A: Baca alasan penolakan di detail transaksi. Jika tidak setuju, diskusikan dengan Admin.

---

## 📚 Dokumentasi Terkait

- 📖 [Pengenalan Sistem](00_PENGENALAN_SISTEM.md)
- 📝 [Panduan Pengajuan Pembelian](04_PENGAJUAN_PEMBELIAN.md)
- ✅ [Panduan Approval](06_APPROVAL_TRANSAKSI.md)
- 💸 [Panduan Pembayaran](07_PEMBAYARAN.md)
- 🔔 [Panduan Notifikasi](08_NOTIFIKASI.md)

---

## 🎬 Video Tutorial

📺 **[Tonton: Cara Mengajukan Rembush (5 menit)](https://youtube.com/...)** *(Coming Soon)*

Video ini menjelaskan:
- Step-by-step upload nota
- Tips foto nota yang baik
- Cara melengkapi form
- Tracking status transaksi

---

## 📞 Butuh Bantuan?

Jika ada pertanyaan atau kendala:

- 💬 **Telegram:** @WhusnetSupport
- 📧 **Email:** support@whusnet.com
- 📱 **WhatsApp:** +62 xxx-xxxx-xxxx
- 🕐 **Jam Operasional:** Senin-Jumat, 08:00-17:00 WIB

---

**Selamat menggunakan fitur Rembush!** 💰

Ingat: Foto nota yang baik = OCR sukses = Proses cepat! 📸✨

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
