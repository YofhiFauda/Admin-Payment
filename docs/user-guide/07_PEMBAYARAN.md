# 💸 Panduan Pembayaran

**Untuk Siapa:** Admin, Atasan, Owner  
**Waktu Baca:** 12 menit  
**Level:** Menengah

---

## 📋 Daftar Isi
- [Overview Proses Pembayaran](#overview-proses-pembayaran)
- [Cara Upload Bukti Bayar](#cara-upload-bukti-bayar)
- [Metode Pembayaran Transfer](#metode-pembayaran-transfer)
- [Metode Pembayaran Cash](#metode-pembayaran-cash)
- [Verifikasi Pembayaran AI](#verifikasi-pembayaran-ai)
- [Menangani Selisih Nominal](#menangani-selisih-nominal)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## Overview Proses Pembayaran

### Alur Pembayaran Lengkap

```
┌─────────────────────────────────────────────┐
│  ALUR PEMBAYARAN                            │
│                                             │
│  1. Transaksi Disetujui                     │
│     Status: Waiting Payment                 │
│     ↓                                       │
│  2. Admin Upload Bukti Bayar                │
│     - Pilih metode (Transfer/Cash)          │
│     - Upload foto struk/bukti               │
│     - Submit                                │
│     ↓                                       │
│  3. Verifikasi AI (Jika Transfer)           │
│     - AI cek nominal struk vs transaksi     │
│     - Jika cocok: ✅ OK                     │
│     - Jika tidak cocok: ⚠️ Flagged          │
│     ↓                                       │
│  4. Konfirmasi (Jika Cash)                  │
│     - Teknisi terima notif Telegram         │
│     - Teknisi konfirmasi terima uang        │
│     - Klik "✅ Ya, Sudah Terima"            │
│     ↓                                       │
│  5. Completed                               │
│     Status: Completed ✅                    │
│     Transaksi selesai!                      │
└─────────────────────────────────────────────┘
```

---

### Siapa yang Bisa Upload Bukti Bayar?

| Role | Upload Bukti Bayar |
|------|:------------------:|
| **Teknisi** | ❌ |
| **Admin** | ✅ |
| **Atasan** | ✅ |
| **Owner** | ✅ |

**Catatan:**
- Hanya Admin/Atasan/Owner yang bisa upload bukti bayar
- Teknisi tidak bisa upload (mereka hanya submit transaksi)

---

## Cara Upload Bukti Bayar

### 📝 Langkah 1: Buka Transaksi

**Via Dashboard:**
```
1. Login ke sistem
2. Lihat Dashboard
3. Cari transaksi dengan status "Waiting Payment"
4. Klik "Upload Bukti Bayar"
```

**Via Halaman Transaksi:**
```
1. Login ke sistem
2. Klik menu "Transaksi"
3. Filter status: "Waiting Payment"
4. Klik transaksi yang ingin dibayar
5. Klik tombol "Upload Bukti Bayar"
```

---

### 💳 Langkah 2: Pilih Metode Pembayaran

**Form Upload:**
```
┌─────────────────────────────────────┐
│ UPLOAD BUKTI PEMBAYARAN             │
│                                     │
│ ID: REM-20260521-00123              │
│ Nominal: Rp 150.000                 │
│ Penerima: Teknisi Budi              │
│                                     │
│ Metode Pembayaran *                 │
│ ○ Transfer Bank                     │
│ ○ Cash                              │
│                                     │
│ [Lanjut]                            │
└─────────────────────────────────────┘
```

**Pilih Metode:**
- **Transfer Bank:** Jika bayar via transfer (m-banking, ATM, dll.)
- **Cash:** Jika bayar tunai langsung ke Teknisi

---

## Metode Pembayaran Transfer

### 📸 Langkah 3A: Upload Struk Transfer

**Form Upload Transfer:**
```
┌─────────────────────────────────────┐
│ UPLOAD STRUK TRANSFER               │
│                                     │
│ Metode: Transfer Bank               │
│ Nominal: Rp 150.000                 │
│                                     │
│ Upload Foto Struk Transfer *        │
│ [Pilih File] atau [Ambil Foto]     │
│                                     │
│ Preview:                            │
│ [Foto Struk]                        │
│                                     │
│ Rekening Tujuan *                   │
│ [BCA - 1234567890 - Budi]          │
│                                     │
│ Tanggal Transfer *                  │
│ [21/05/2026]                        │
│                                     │
│ Catatan (Opsional)                  │
│ [_____________________________]     │
│                                     │
│ [Batal]  [Submit]                  │
└─────────────────────────────────────┘
```

---

**Tips Foto Struk Transfer:**

**✅ DO (Lakukan):**
1. **Foto Jelas**
   - Semua teks terbaca
   - Tidak blur atau gelap
   - Fokus tajam

2. **Informasi Lengkap**
   - Nominal transfer terlihat jelas
   - Rekening tujuan terlihat
   - Tanggal transfer terlihat
   - Nama penerima terlihat

3. **Foto Asli**
   - Langsung dari m-banking/ATM
   - Bukan screenshot yang diedit
   - Bukan foto dari foto

4. **Format Benar**
   - JPG atau PNG
   - Maksimal 5 MB
   - Resolusi cukup (minimal 1080p)

**❌ DON'T (Jangan):**
1. ❌ Foto blur atau gelap
2. ❌ Nominal tidak terlihat
3. ❌ Screenshot yang sudah diedit
4. ❌ Foto terpotong (informasi tidak lengkap)

---

**Contoh Struk Transfer yang Baik:**
```
┌─────────────────────────────────┐
│  BCA Mobile                     │
│  ─────────────────────────────  │
│  Transfer Berhasil              │
│                                 │
│  Dari: 9876543210 (Anda)        │
│  Ke: 1234567890 (Budi)          │
│  Nominal: Rp 150.000            │
│  Biaya Admin: Rp 0              │
│  Total: Rp 150.000              │
│  Tanggal: 21 Mei 2026 14:30     │
│  Ref: 202605211430123456        │
│                                 │
│  ✅ Transfer Berhasil           │
└─────────────────────────────────┘
```

**Yang Penting Terlihat:**
- ✅ Nominal: Rp 150.000
- ✅ Rekening tujuan: 1234567890 (Budi)
- ✅ Tanggal: 21 Mei 2026
- ✅ Status: Berhasil

---

### 🤖 Langkah 4A: Verifikasi AI

**Setelah Submit:**
```
┌─────────────────────────────────────┐
│  🔄 Memverifikasi Pembayaran...     │
│                                     │
│  AI sedang mengecek:                │
│  ✅ Nominal di struk                │
│  ✅ Nominal di transaksi            │
│  ✅ Kesesuaian data                 │
│                                     │
│  Mohon tunggu...                    │
└─────────────────────────────────────┘
```

**Hasil Verifikasi:**

**Jika Cocok (✅):**
```
┌─────────────────────────────────────┐
│  ✅ Pembayaran Berhasil Diverifikasi │
│                                     │
│  Nominal struk: Rp 150.000          │
│  Nominal transaksi: Rp 150.000      │
│  Status: Cocok ✅                   │
│                                     │
│  Status transaksi: Completed        │
│                                     │
│  Teknisi akan menerima notifikasi.  │
│                                     │
│  [OK]                               │
└─────────────────────────────────────┘
```

**Jika Tidak Cocok (⚠️):**
```
┌─────────────────────────────────────┐
│  ⚠️ Selisih Nominal Terdeteksi       │
│                                     │
│  Nominal struk: Rp 145.000          │
│  Nominal transaksi: Rp 150.000      │
│  Selisih: Rp 5.000                  │
│                                     │
│  Status: Flagged ⚠️                 │
│                                     │
│  Transaksi perlu review Owner       │
│  untuk Force Approve.               │
│                                     │
│  [OK]                               │
└─────────────────────────────────────┘
```

---

## Metode Pembayaran Cash

### 💵 Langkah 3B: Upload Bukti Cash

**Form Upload Cash:**
```
┌─────────────────────────────────────┐
│ UPLOAD BUKTI CASH                   │
│                                     │
│ Metode: Cash                        │
│ Nominal: Rp 150.000                 │
│                                     │
│ Upload Foto Bukti (Opsional)        │
│ [Pilih File] atau [Ambil Foto]     │
│                                     │
│ Preview:                            │
│ [Foto Bukti]                        │
│                                     │
│ Tanggal Bayar *                     │
│ [21/05/2026]                        │
│                                     │
│ Catatan (Opsional)                  │
│ [_____________________________]     │
│                                     │
│ ⚠️ Teknisi akan menerima notifikasi │
│    Telegram untuk konfirmasi.       │
│                                     │
│ [Batal]  [Submit]                  │
└─────────────────────────────────────┘
```

---

**Tips Upload Bukti Cash:**

**Foto Bukti (Opsional):**
- Foto saat serah terima uang (jika ada)
- Foto kwitansi (jika ada)
- Foto apapun yang bisa jadi bukti

**Jika Tidak Ada Foto:**
- Tidak masalah, foto opsional
- Yang penting konfirmasi Telegram dari Teknisi

---

### 📱 Langkah 4B: Konfirmasi Telegram

**Setelah Submit:**
```
┌─────────────────────────────────────┐
│  ✅ Bukti Cash Berhasil Diupload     │
│                                     │
│  Status: Waiting Confirmation       │
│                                     │
│  Teknisi akan menerima notifikasi   │
│  Telegram untuk konfirmasi.         │
│                                     │
│  Transaksi akan Completed setelah   │
│  Teknisi konfirmasi terima uang.    │
│                                     │
│  [OK]                               │
└─────────────────────────────────────┘
```

---

**Notifikasi Telegram ke Teknisi:**
```
💰 Konfirmasi Penerimaan Uang Cash

Transaksi: REM-20260521-00123
Nominal: Rp 150.000
Dari: Admin Siti
Tanggal: 21 Mei 2026

Apakah Anda sudah menerima uang ini?

[✅ Ya, Sudah Terima]  [❌ Belum Terima]
```

---

**Jika Teknisi Klik "✅ Ya, Sudah Terima":**
```
┌─────────────────────────────────────┐
│  ✅ Pembayaran Dikonfirmasi          │
│                                     │
│  Teknisi: Budi                      │
│  Konfirmasi: Ya, Sudah Terima       │
│  Waktu: 21 Mei 2026 15:00           │
│                                     │
│  Status transaksi: Completed ✅     │
│                                     │
│  [OK]                               │
└─────────────────────────────────────┘
```

**Jika Teknisi Klik "❌ Belum Terima":**
```
┌─────────────────────────────────────┐
│  ⚠️ Teknisi Belum Terima Uang        │
│                                     │
│  Teknisi: Budi                      │
│  Konfirmasi: Belum Terima           │
│  Waktu: 21 Mei 2026 15:00           │
│                                     │
│  Status: Waiting Confirmation       │
│                                     │
│  Mohon koordinasi dengan Teknisi.   │
│                                     │
│  [OK]                               │
└─────────────────────────────────────┘
```

---

## Verifikasi Pembayaran AI

### Apa itu Verifikasi AI?

**Verifikasi AI** adalah fitur untuk mengecek kesesuaian nominal di struk transfer dengan nominal di transaksi. Tujuannya untuk mencegah kesalahan transfer dan fraud.

---

### Cara Kerja Verifikasi AI

```
┌─────────────────────────────────────┐
│  VERIFIKASI AI                      │
│                                     │
│  1. Admin upload struk transfer     │
│     ↓                               │
│  2. AI baca nominal di struk        │
│     (OCR Gemini)                    │
│     ↓                               │
│  3. AI bandingkan dengan nominal    │
│     transaksi                       │
│     ↓                               │
│  4. Hasil:                          │
│     - Cocok: ✅ Completed           │
│     - Tidak cocok: ⚠️ Flagged       │
└─────────────────────────────────────┘
```

---

### Contoh Verifikasi

**Kasus 1: Cocok (✅)**
```
Nominal Transaksi: Rp 150.000
Nominal Struk: Rp 150.000
Selisih: Rp 0

Hasil: ✅ Cocok
Status: Completed
```

**Kasus 2: Tidak Cocok (⚠️)**
```
Nominal Transaksi: Rp 150.000
Nominal Struk: Rp 145.000
Selisih: Rp 5.000

Hasil: ⚠️ Tidak Cocok
Status: Flagged
```

---

### Mengapa Bisa Tidak Cocok?

**Penyebab Umum:**

1. **Biaya Admin Bank**
   - Transfer Rp 150.000
   - Biaya admin Rp 5.000
   - Yang terima Rp 145.000

2. **Salah Transfer**
   - Seharusnya Rp 150.000
   - Tapi transfer Rp 145.000
   - Kurang Rp 5.000

3. **AI Salah Baca**
   - Struk tidak jelas
   - AI salah deteksi nominal
   - Sebenarnya cocok

4. **Potongan Lain**
   - Ada potongan yang disepakati
   - Contoh: Potongan hutang, dll.

---

## Menangani Selisih Nominal

### Jika Transaksi Di-flag

**Langkah untuk Owner:**

1. **Buka transaksi yang di-flag**
2. **Review detail:**
   - Cek struk transfer
   - Cek nominal transaksi
   - Cek alasan selisih

3. **Investigasi:**
   - Tanya Admin: "Kenapa ada selisih?"
   - Tanya Teknisi: "Berapa yang diterima?"
   - Cek rekening: "Berapa yang masuk?"

4. **Keputusan:**

**Jika Selisih Wajar (Biaya Admin):**
```
1. Klik "Force Approve"
2. Tulis alasan: "Selisih Rp 5.000 adalah 
   biaya admin bank. Sudah dikonfirmasi."
3. Submit
4. Status jadi "Completed"
```

**Jika Salah Transfer:**
```
1. Minta Admin transfer kekurangannya
2. Upload bukti transfer tambahan
3. Setelah lengkap, Force Approve
4. Status jadi "Completed"
```

**Jika AI Salah Baca:**
```
1. Klik "Force Approve"
2. Tulis alasan: "AI salah deteksi. Nominal 
   sudah benar Rp 150.000."
3. Submit
4. Status jadi "Completed"
```

---

### Tips Mencegah Selisih

**Untuk Admin:**
- ✅ Cek biaya admin sebelum transfer
- ✅ Jika ada biaya admin, transfer lebih (150.000 + 5.000 = 155.000)
- ✅ Atau gunakan transfer gratis (sesama bank, m-banking, dll.)
- ✅ Foto struk dengan jelas
- ✅ Pastikan nominal di struk sesuai

**Untuk Owner:**
- ✅ Review transaksi yang di-flag segera
- ✅ Koordinasi dengan Admin & Teknisi
- ✅ Force Approve jika memang wajar
- ✅ Jangan biarkan pending terlalu lama

---

## Troubleshooting

### Masalah 1: "Upload Gagal"

**Penyebab:**
- File terlalu besar (> 5 MB)
- Format tidak didukung
- Koneksi internet bermasalah

**Solusi:**
1. Compress foto (gunakan TinyPNG, dll.)
2. Pastikan format JPG/PNG
3. Cek koneksi internet
4. Coba lagi

---

### Masalah 2: "AI Tidak Bisa Baca Struk"

**Penyebab:**
- Foto struk tidak jelas
- Format struk tidak standar
- Nominal tidak terlihat

**Solusi:**
1. Foto ulang dengan lebih jelas
2. Pastikan nominal terlihat jelas
3. Jika tetap gagal, hubungi Owner untuk Force Approve manual

---

### Masalah 3: "Teknisi Tidak Konfirmasi (Cash)"

**Penyebab:**
- Teknisi belum terima notifikasi Telegram
- Teknisi belum terima uang
- Teknisi lupa konfirmasi

**Solusi:**
1. Cek apakah Teknisi sudah terima notifikasi
2. Koordinasi via Telegram/WhatsApp
3. Minta Teknisi konfirmasi segera
4. Jika urgent, hubungi Owner untuk Force Complete

---

### Masalah 4: "Salah Upload Bukti Bayar"

**Penyebab:**
- Upload struk yang salah
- Upload ke transaksi yang salah

**Solusi:**
1. Hubungi Owner untuk cancel pembayaran
2. Upload ulang dengan bukti yang benar
3. Untuk mencegah: Cek ID transaksi sebelum upload

---

## FAQ

**Q: Apakah wajib upload bukti bayar?**  
A: Ya, wajib. Transaksi tidak akan completed tanpa bukti bayar.

**Q: Apakah bisa upload bukti bayar setelah beberapa hari?**  
A: Bisa, tapi sebaiknya segera setelah transfer. Untuk tracking yang lebih baik.

**Q: Bagaimana jika lupa upload bukti bayar?**  
A: Transaksi akan tetap status "Waiting Payment". Upload segera setelah ingat.

**Q: Apakah bisa edit bukti bayar yang sudah diupload?**  
A: Tidak bisa. Jika salah, hubungi Owner untuk cancel dan upload ulang.

**Q: Berapa lama AI verifikasi struk transfer?**  
A: Biasanya 10-30 detik. Tergantung kualitas foto dan beban server.

**Q: Bagaimana jika AI salah deteksi nominal?**  
A: Hubungi Owner untuk Force Approve dengan alasan "AI salah deteksi".

**Q: Apakah bisa bayar sebagian dulu?**  
A: Tidak, harus bayar full sesuai nominal transaksi.

**Q: Bagaimana jika Teknisi tidak konfirmasi cash?**  
A: Koordinasi dengan Teknisi via Telegram/WhatsApp. Jika urgent, hubungi Owner.

**Q: Apakah bisa pakai metode pembayaran lain (e-wallet, dll.)?**  
A: Saat ini hanya Transfer Bank dan Cash. Untuk e-wallet, gunakan Transfer Bank (upload struk e-wallet).

**Q: Bagaimana jika transfer ke rekening yang salah?**  
A: Segera hubungi Owner. Jangan upload bukti bayar. Koordinasi untuk transfer ulang ke rekening yang benar.

---

## 📚 Dokumentasi Terkait

- 📖 [Pengenalan Sistem](00_PENGENALAN_SISTEM.md)
- 💰 [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md)
- 📝 [Panduan Pengajuan](04_PENGAJUAN_PEMBELIAN.md)
- ✅ [Panduan Approval](06_APPROVAL_TRANSAKSI.md)
- 🔧 [Troubleshooting](16_TROUBLESHOOTING_USER.md)

---

## 📞 Butuh Bantuan?

Jika ada pertanyaan atau kendala:

- 💬 **Telegram:** @WhusnetSupport
- 📧 **Email:** support@whusnet.com
- 📱 **WhatsApp:** +62 xxx-xxxx-xxxx
- 🕐 **Jam Operasional:** Senin-Jumat, 08:00-17:00 WIB

---

**Upload bukti bayar dengan benar untuk proses yang lancar!** 💸✨

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
