# 🔔 Panduan Notifikasi

**Untuk Siapa:** Semua Pengguna  
**Waktu Baca:** 8 menit  
**Level:** Pemula

---

## 📋 Daftar Isi
- [Jenis-jenis Notifikasi](#jenis-jenis-notifikasi)
- [Notifikasi In-App](#notifikasi-in-app)
- [Notifikasi Telegram](#notifikasi-telegram)
- [Cara Mengatur Notifikasi](#cara-mengatur-notifikasi)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## Jenis-jenis Notifikasi

WHUSNET Admin Payment memiliki **2 jenis notifikasi**:

```
┌─────────────────────────────────────┐
│  JENIS NOTIFIKASI                   │
│                                     │
│  1. 📱 In-App Notification          │
│     - Di dalam sistem               │
│     - Icon lonceng (🔔)             │
│     - Badge merah (jumlah unread)   │
│                                     │
│  2. 💬 Telegram Notification        │
│     - Via Telegram Bot              │
│     - Push notification HP          │
│     - Bisa reply langsung           │
└─────────────────────────────────────┘
```

---

### Kapan Anda Dapat Notifikasi?

| Event | In-App | Telegram | Untuk Siapa |
|-------|:------:|:--------:|-------------|
| **OCR Selesai** | ✅ | ❌ | Submitter |
| **Transaksi Disetujui** | ✅ | ✅ | Submitter |
| **Transaksi Ditolak** | ✅ | ✅ | Submitter |
| **Perlu Approval Owner** | ✅ | ✅ | Owner |
| **Pembayaran Selesai** | ✅ | ❌ | Submitter |
| **Konfirmasi Cash** | ❌ | ✅ | Teknisi |
| **Selisih Nominal** | ✅ | ✅ | Admin, Owner |
| **Auto-Reject** | ✅ | ✅ | Submitter, Admin |
| **Transaksi Baru (Pending)** | ✅ | ❌ | Admin, Atasan, Owner |

---

## Notifikasi In-App

### Apa itu Notifikasi In-App?

**Notifikasi In-App** adalah notifikasi yang muncul di dalam sistem (browser). Anda bisa lihat di icon lonceng (🔔) di header.

---

### Cara Melihat Notifikasi In-App

**Langkah 1: Klik Icon Lonceng**
```
┌─────────────────────────────────────┐
│ 🏢 WHUSNET Admin Payment            │
│                          [🔔 3] [👤]│
│                           ↑         │
│                      Badge merah    │
│                      (3 unread)     │
└─────────────────────────────────────┘
```

**Langkah 2: Lihat Daftar Notifikasi**
```
┌─────────────────────────────────────┐
│  🔔 NOTIFIKASI                       │
│                                     │
│  ┌─────────────────────────────┐   │
│  │ ✅ Transaksi Disetujui       │   │
│  │ REM-20260521-00123          │   │
│  │ 5 menit yang lalu           │   │
│  └─────────────────────────────┘   │
│                                     │
│  ┌─────────────────────────────┐   │
│  │ 🔄 OCR Selesai               │   │
│  │ REM-20260521-00124          │   │
│  │ 10 menit yang lalu          │   │
│  └─────────────────────────────┘   │
│                                     │
│  ┌─────────────────────────────┐   │
│  │ ❌ Transaksi Ditolak         │   │
│  │ REM-20260521-00122          │   │
│  │ 1 jam yang lalu             │   │
│  └─────────────────────────────┘   │
│                                     │
│  [Mark All as Read] [Clear All]    │
└─────────────────────────────────────┘
```

---

### Jenis Notifikasi In-App

**1. ✅ Transaksi Disetujui**
```
✅ Transaksi Disetujui

Transaksi REM-20260521-00123 telah 
disetujui oleh Admin Siti.

Status: Waiting Payment

[Lihat Detail]
```

**2. ❌ Transaksi Ditolak**
```
❌ Transaksi Ditolak

Transaksi REM-20260521-00122 ditolak 
oleh Admin Siti.

Alasan: Nota tidak jelas, mohon upload 
ulang dengan foto yang lebih terang.

[Lihat Detail]
```

**3. 🔄 OCR Selesai**
```
🔄 OCR Selesai

OCR untuk transaksi REM-20260521-00124 
telah selesai.

Confidence: High (95%)

[Lihat & Review]
```

**4. 💰 Pembayaran Selesai**
```
💰 Pembayaran Selesai

Transaksi REM-20260521-00123 telah 
dibayar.

Nominal: Rp 150.000
Metode: Transfer Bank

Cek rekening Anda dalam 1-3 hari kerja.

[Lihat Detail]
```

**5. ⚠️ Perlu Approval Owner**
```
⚠️ Perlu Approval Owner

Transaksi PEN-20260521-00045 dengan 
nominal Rp 5.000.000 memerlukan 
approval Anda.

[Review & Approve]
```

---

### Cara Menandai Notifikasi Sudah Dibaca

**Opsi 1: Klik Notifikasi**
- Klik notifikasi untuk lihat detail
- Notifikasi otomatis ditandai sebagai "read"
- Badge berkurang

**Opsi 2: Mark as Read Manual**
- Hover notifikasi
- Klik icon "✓" (mark as read)
- Notifikasi ditandai sebagai "read"

**Opsi 3: Mark All as Read**
- Klik tombol "Mark All as Read"
- Semua notifikasi ditandai sebagai "read"
- Badge jadi 0

---

### Cara Menghapus Notifikasi

**Opsi 1: Hapus Satu per Satu**
- Hover notifikasi
- Klik icon "🗑️" (delete)
- Notifikasi dihapus

**Opsi 2: Clear All**
- Klik tombol "Clear All"
- Semua notifikasi dihapus
- Badge jadi 0

---

## Notifikasi Telegram

### Apa itu Notifikasi Telegram?

**Notifikasi Telegram** adalah notifikasi yang dikirim via **Telegram Bot** ke HP Anda. Anda bisa terima notifikasi meskipun tidak buka sistem.

---

### Cara Setup Notifikasi Telegram

**Langkah 1: Cari Bot**
```
1. Buka Telegram
2. Cari bot: @WhusnetAdminBot
   (sesuaikan dengan nama bot Anda)
3. Klik "Start"
```

**Langkah 2: Verifikasi**
```
Bot akan kirim pesan:

👋 Selamat datang di WHUSNET Admin Payment Bot!

Untuk menghubungkan akun Anda, masukkan 
kode verifikasi dari sistem.

Cara mendapatkan kode:
1. Login ke sistem
2. Klik nama Anda di header
3. Pilih "Telegram Setup"
4. Copy kode verifikasi
5. Paste di sini

Ketik kode verifikasi:
```

**Langkah 3: Masukkan Kode**
```
1. Login ke sistem
2. Klik nama Anda di header (👤)
3. Pilih "Telegram Setup"
4. Copy kode verifikasi (contoh: ABC123XYZ)
5. Paste di Telegram Bot
6. Kirim
```

**Langkah 4: Selesai!**
```
✅ Akun Berhasil Terhubung!

Nama: Teknisi Budi
Email: budi@whusnet.com
Role: Teknisi

Anda akan menerima notifikasi untuk:
- Transaksi disetujui/ditolak
- Konfirmasi penerimaan cash
- Alert penting lainnya

Ketik /help untuk melihat perintah.
```

---

### Jenis Notifikasi Telegram

**1. ✅ Transaksi Disetujui**
```
✅ Transaksi Disetujui

ID: REM-20260521-00123
Nominal: Rp 150.000
Disetujui oleh: Admin Siti
Status: Waiting Payment

Transaksi Anda akan segera diproses 
untuk pembayaran.

[Lihat Detail]
```

**2. ❌ Transaksi Ditolak**
```
❌ Transaksi Ditolak

ID: REM-20260521-00122
Nominal: Rp 150.000
Ditolak oleh: Admin Siti

Alasan:
Nota tidak jelas, mohon upload ulang 
dengan foto yang lebih terang.

[Lihat Detail]
```

**3. 💰 Konfirmasi Penerimaan Cash**
```
💰 Konfirmasi Penerimaan Uang Cash

Transaksi: REM-20260521-00123
Nominal: Rp 150.000
Dari: Admin Siti
Tanggal: 21 Mei 2026

Apakah Anda sudah menerima uang ini?

[✅ Ya, Sudah Terima]  [❌ Belum Terima]
```

**4. ⚠️ Perlu Approval Owner**
```
⚠️ Perlu Approval Owner

ID: PEN-20260521-00045
Nominal: Rp 5.000.000
Diajukan oleh: Teknisi Budi
Tanggal: 21 Mei 2026

Transaksi dengan nominal ≥ 1 Jt 
memerlukan approval Anda.

[Review & Approve]
```

**5. 🚨 Selisih Nominal**
```
🚨 Selisih Nominal Terdeteksi

ID: REM-20260521-00123
Nominal Transaksi: Rp 150.000
Nominal Struk: Rp 145.000
Selisih: Rp 5.000

Status: Flagged

Mohon review dan Force Approve jika 
selisih wajar.

[Review]
```

---

### Perintah Telegram Bot

**Perintah Dasar:**

| Perintah | Fungsi |
|----------|--------|
| `/start` | Mulai bot & verifikasi akun |
| `/help` | Lihat daftar perintah |
| `/status` | Cek status koneksi |
| `/stats` | Lihat statistik transaksi |
| `/pending` | Lihat transaksi pending |
| `/disconnect` | Putuskan koneksi akun |

---

**Contoh Penggunaan:**

**`/status`**
```
✅ Status Koneksi

Akun: Teknisi Budi (budi@whusnet.com)
Role: Teknisi
Status: Connected ✅
Terakhir sync: 21 Mei 2026 15:30

Notifikasi: Aktif
```

**`/stats`**
```
📊 Statistik Transaksi Anda

Total Transaksi: 45
- Pending: 2
- Approved: 5
- Waiting Payment: 3
- Completed: 35

Total Nominal: Rp 12.500.000
Bulan ini: Rp 2.300.000
```

**`/pending`**
```
📋 Transaksi Pending (2)

1. REM-20260521-00123
   Rp 150.000 - Instalasi
   Diajukan: 21 Mei 2026 10:00
   [Lihat Detail]

2. REM-20260521-00124
   Rp 85.000 - Pembelian Alat
   Diajukan: 21 Mei 2026 14:00
   [Lihat Detail]
```

---

## Cara Mengatur Notifikasi

### Notifikasi In-App

**Aktifkan Notifikasi Browser:**

**Chrome:**
```
1. Klik icon gembok di address bar
2. Pilih "Site settings"
3. Cari "Notifications"
4. Pilih "Allow"
5. Refresh halaman
```

**Firefox:**
```
1. Klik icon gembok di address bar
2. Pilih "Connection secure"
3. Klik "More information"
4. Tab "Permissions"
5. Cari "Notifications"
6. Centang "Allow"
7. Refresh halaman
```

---

### Notifikasi Telegram

**Aktifkan Notifikasi Telegram:**
```
1. Buka Telegram
2. Buka chat dengan bot
3. Tap nama bot di atas
4. Tap "Notifications"
5. Pastikan "On"
```

**Atur Suara Notifikasi:**
```
1. Buka Telegram
2. Buka chat dengan bot
3. Tap nama bot di atas
4. Tap "Notifications"
5. Pilih "Sound"
6. Pilih suara yang diinginkan
```

**Mute Notifikasi (Sementara):**
```
1. Buka Telegram
2. Buka chat dengan bot
3. Tap nama bot di atas
4. Tap "Mute for..."
5. Pilih durasi (1 jam, 8 jam, 1 hari, dll.)
```

---

## Troubleshooting

### Masalah 1: "Notifikasi In-App Tidak Muncul"

**Penyebab:**
- Notifikasi browser diblokir
- WebSocket terputus
- Browser tidak support

**Solusi:**
1. Cek permission browser (Allow notifications)
2. Refresh halaman (F5)
3. Logout dan login lagi
4. Coba browser lain (Chrome recommended)

---

### Masalah 2: "Notifikasi Telegram Tidak Masuk"

**Penyebab:**
- Bot belum disetup
- Kode verifikasi salah
- Koneksi terputus

**Solusi:**
1. Cek apakah sudah setup bot (`/status`)
2. Jika belum, setup ulang (`/start`)
3. Jika sudah, coba disconnect dan connect ulang
4. Hubungi Admin jika masih gagal

---

### Masalah 3: "Badge Notifikasi Tidak Update"

**Penyebab:**
- Cache browser
- WebSocket tidak sync

**Solusi:**
1. Refresh halaman (F5)
2. Atau logout dan login lagi
3. Badge akan update otomatis

---

### Masalah 4: "Notifikasi Terlalu Banyak (Spam)"

**Penyebab:**
- Banyak transaksi pending
- Banyak update transaksi

**Solusi:**
1. Mark all as read (in-app)
2. Mute Telegram sementara
3. Proses transaksi pending segera
4. Tidak bisa disable notifikasi penting

---

## FAQ

**Q: Apakah bisa disable notifikasi?**  
A: Notifikasi penting tidak bisa disable (untuk memastikan Anda tidak miss update). Tapi Anda bisa mute Telegram sementara.

**Q: Apakah notifikasi akan hilang setelah logout?**  
A: Tidak, notifikasi tetap tersimpan dan akan muncul saat login kembali.

**Q: Berapa lama notifikasi tersimpan?**  
A: Notifikasi tersimpan selama 30 hari. Setelah itu otomatis dihapus.

**Q: Apakah bisa lihat notifikasi lama?**  
A: Ya, scroll ke bawah di daftar notifikasi untuk lihat notifikasi lama.

**Q: Apakah notifikasi Telegram bisa di-reply?**  
A: Tidak, notifikasi hanya satu arah. Untuk komunikasi, gunakan channel support.

**Q: Bagaimana jika lupa kode verifikasi Telegram?**  
A: Generate kode baru di sistem (Profil → Telegram Setup → Generate New Code).

**Q: Apakah bisa connect beberapa akun Telegram?**  
A: Tidak, satu akun sistem = satu akun Telegram.

**Q: Bagaimana jika ganti nomor HP (Telegram)?**  
A: Disconnect akun lama, lalu connect dengan akun Telegram baru.

---

## 📚 Dokumentasi Terkait

- 📖 [Pengenalan Sistem](00_PENGENALAN_SISTEM.md)
- 🚀 [Cara Memulai](01_MEMULAI.md)
- 💰 [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md)
- 📝 [Panduan Pengajuan](04_PENGAJUAN_PEMBELIAN.md)
- 🔧 [Troubleshooting](16_TROUBLESHOOTING_USER.md)

---

## 📞 Butuh Bantuan?

Jika ada pertanyaan atau kendala:

- 💬 **Telegram:** @WhusnetSupport
- 📧 **Email:** support@whusnet.com
- 📱 **WhatsApp:** +62 xxx-xxxx-xxxx
- 🕐 **Jam Operasional:** Senin-Jumat, 08:00-17:00 WIB

---

**Aktifkan notifikasi untuk tidak miss update penting!** 🔔✨

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
