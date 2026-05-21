# 📜 Activity Log & Audit Trail - WHUSNET Admin Payment

**Untuk Siapa:** Admin, Atasan, Owner  
**Waktu Baca:** ~8 menit  
**Level:** Menengah

---

## 📋 Daftar Isi

- [Apa itu Activity Log?](#apa-itu-activity-log)
- [Mengapa Penting?](#mengapa-penting)
- [Jenis Aktivitas yang Dicatat](#jenis-aktivitas-yang-dicatat)
- [Cara Melihat Activity Log](#cara-melihat-activity-log)
- [Filter dan Pencarian](#filter-dan-pencarian)
- [Audit Trail untuk Compliance](#audit-trail-untuk-compliance)
- [Tips & Best Practices](#tips--best-practices)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## Apa itu Activity Log?

**Activity Log** adalah sistem pencatatan otomatis untuk semua aktivitas user di sistem, menciptakan **audit trail** lengkap untuk transparansi dan keamanan.

### Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| **Auto-Logging** | Semua aktivitas tercatat otomatis |
| **Detailed Info** | User, waktu, aksi, data before/after |
| **Immutable** | Log tidak bisa diedit atau dihapus |
| **Searchable** | Filter dan pencarian powerful |
| **Export** | Export ke Excel/CSV untuk audit |

### Manfaat

✅ **Transparansi:** Semua aktivitas tercatat jelas  
✅ **Akuntabilitas:** Tahu siapa melakukan apa dan kapan  
✅ **Deteksi Fraud:** Identifikasi aktivitas mencurigakan  
✅ **Compliance:** Memenuhi requirement audit keuangan  

---

## Mengapa Penting?

### Skenario Bisnis

```
Kasus 1: Transaksi Hilang
Teknisi: "Transaksi saya hilang!"

Cek Activity Log:
21 Mei 2026, 15:30
👤 Admin Budi
🗑️ Deleted Transaction
ID: RMB-2026-05-0012
Alasan: Duplikat dengan RMB-2026-05-0011

Kesimpulan: Transaksi dihapus oleh Admin Budi
karena duplikat (bukan hilang).
```

```
Kasus 2: Harga Berubah
Owner: "Kenapa harga pengajuan ini berubah?"

Cek Activity Log:
20 Mei 2026, 14:20
👤 Atasan Siti
✏️ Updated Transaction
ID: PGJ-2026-05-0008
Field: amount
Before: Rp 1.000.000
After: Rp 1.200.000
Alasan: Harga supplier naik

Kesimpulan: Harga diubah oleh Atasan Siti
dengan alasan valid.
```

```
Kasus 3: Fraud Detection
Owner: "Transaksi ini mencurigakan..."

Cek Activity Log:
19 Mei 2026, 23:45 ← Jam tidak wajar
👤 Teknisi X
📝 Created Transaction
Amount: Rp 5.000.000 ← Nominal besar
Status: pending

19 Mei 2026, 23:50
👤 Admin Y
✅ Approved Transaction
ID: RMB-2026-05-0010

Kesimpulan: Transaksi dibuat dan diapprove
di luar jam kerja. Perlu investigasi.
```

---

## Jenis Aktivitas yang Dicatat

### 1. Transaksi

| Aktivitas | Deskripsi | Icon |
|-----------|-----------|------|
| **Created** | Transaksi baru dibuat | 📝 |
| **Updated** | Transaksi diedit | ✏️ |
| **Approved** | Transaksi disetujui | ✅ |
| **Rejected** | Transaksi ditolak | ❌ |
| **Deleted** | Transaksi dihapus | 🗑️ |
| **Status Changed** | Status berubah | 🔄 |
| **Payment Uploaded** | Bukti bayar diupload | 💸 |
| **Force Approved** | Force approve oleh Owner | ⚡ |
| **Overridden** | Override auto-reject | 🔓 |

### 2. Manajemen Data

| Aktivitas | Deskripsi | Icon |
|-----------|-----------|------|
| **Branch Created** | Cabang baru ditambah | 🏢 |
| **Branch Updated** | Cabang diedit | ✏️ |
| **Branch Deleted** | Cabang dihapus | 🗑️ |
| **Category Created** | Kategori baru ditambah | 📂 |
| **Category Updated** | Kategori diedit | ✏️ |
| **Category Toggled** | Kategori aktif/nonaktif | 🔄 |
| **Account Created** | Rekening baru ditambah | 🏦 |
| **Account Updated** | Rekening diedit | ✏️ |
| **Account Deleted** | Rekening dihapus | 🗑️ |

### 3. User Management

| Aktivitas | Deskripsi | Icon |
|-----------|-----------|------|
| **User Created** | User baru ditambah | 👤 |
| **User Updated** | User diedit | ✏️ |
| **User Deleted** | User dihapus | 🗑️ |
| **Login** | User login | 🔐 |
| **Logout** | User logout | 🚪 |
| **Password Changed** | Password diubah | 🔑 |

### 4. Price Index

| Aktivitas | Deskripsi | Icon |
|-----------|-----------|------|
| **Price Locked** | Harga di-lock manual | 🔒 |
| **Price Unlocked** | Harga di-unlock | 🔓 |
| **Price Updated** | Harga referensi diupdate | 💡 |
| **Anomaly Detected** | Anomali harga terdeteksi | 🚨 |
| **Anomaly Approved** | Anomali diapprove | ✅ |
| **Anomaly Rejected** | Anomali direject | ❌ |

### 5. Hutang Antar Cabang

| Aktivitas | Deskripsi | Icon |
|-----------|-----------|------|
| **Debt Created** | Hutang baru tercatat | 💸 |
| **Debt Settled** | Hutang dilunasi | ✅ |
| **Settlement Uploaded** | Bukti pelunasan diupload | 📄 |

---

## Cara Melihat Activity Log

### Akses Activity Log

1. Login sebagai **Admin**, **Atasan**, atau **Owner**
2. Klik menu **"Laporan"** → **"Activity Log"**

```
┌─────────────────────────────────────────┐
│  📜 Activity Log                        │
├─────────────────────────────────────────┤
│                                         │
│  [🔍 Cari aktivitas...]                 │
│                                         │
│  Filter:                                │
│  [Semua User ▼] [Semua Aksi ▼]         │
│  [Tanggal: 21 Mei 2026 📅]              │
│                                         │
│  [Export Excel] [Export CSV]            │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
│  21 Mei 2026, 15:30                     │
│  👤 Admin Budi                          │
│  ✅ Approved Transaction                │
│                                         │
│  Transaksi: RMB-2026-05-0012            │
│  Amount: Rp 850.000                     │
│  Status: pending → waiting_payment      │
│                                         │
│  [Lihat Detail]                         │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
│  21 Mei 2026, 14:20                     │
│  👤 Atasan Siti                         │
│  ✏️ Updated Transaction                 │
│                                         │
│  Transaksi: PGJ-2026-05-0008            │
│  Field: amount                          │
│  Before: Rp 1.000.000                   │
│  After: Rp 1.200.000                    │
│  Alasan: Harga supplier naik            │
│                                         │
│  [Lihat Detail]                         │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
└─────────────────────────────────────────┘
```

### Detail Activity

Klik **"Lihat Detail"** untuk informasi lengkap:

```
┌─────────────────────────────────────────┐
│  📋 Detail Activity                     │
├─────────────────────────────────────────┤
│                                         │
│  Waktu: 21 Mei 2026, 15:30:45           │
│  User: Admin Budi (admin@whusnet.com)   │
│  Role: Admin                            │
│  IP Address: 192.168.1.100              │
│  Browser: Chrome 125.0                  │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
│  Aktivitas: ✅ Approved Transaction     │
│                                         │
│  Transaksi:                             │
│  - ID: RMB-2026-05-0012                 │
│  - Invoice: RMB-2026-05-0012            │
│  - Type: Rembush                        │
│  - Amount: Rp 850.000                   │
│  - Submitted By: Teknisi Andi           │
│                                         │
│  Perubahan:                             │
│  - Status: pending → waiting_payment    │
│  - Reviewed By: null → Admin Budi       │
│  - Reviewed At: null → 21 Mei 15:30     │
│                                         │
│  Catatan:                               │
│  "Transaksi valid, nota jelas"          │
│                                         │
│  [Lihat Transaksi] [Tutup]              │
│                                         │
└─────────────────────────────────────────┘
```

---

## Filter dan Pencarian

### Filter by User

```
[Filter User ▼]
- Semua User
- Admin Budi
- Atasan Siti
- Owner Joko
- Teknisi Andi
- ...
```

### Filter by Action

```
[Filter Aksi ▼]
- Semua Aksi
- Created
- Updated
- Approved
- Rejected
- Deleted
- Status Changed
- ...
```

### Filter by Date Range

```
[Tanggal Mulai 📅] [Tanggal Akhir 📅]

Preset:
- Hari Ini
- 7 Hari Terakhir
- 30 Hari Terakhir
- Bulan Ini
- Custom Range
```

### Pencarian

```
[🔍 Cari aktivitas...]

Bisa cari berdasarkan:
- Nama user
- Invoice number
- Kata kunci (misal: "approved", "deleted")
- IP address
```

### Contoh Penggunaan

```
Kasus: Cari semua transaksi yang diapprove
       oleh Admin Budi di bulan Mei 2026

Filter:
- User: Admin Budi
- Aksi: Approved
- Tanggal: 1 Mei - 31 Mei 2026

Hasil: 45 aktivitas
```

---

## Audit Trail untuk Compliance

### Informasi yang Tercatat

Setiap log mencatat:

1. **Who:** Siapa yang melakukan (user ID, nama, email, role)
2. **What:** Apa yang dilakukan (aksi, data before/after)
3. **When:** Kapan dilakukan (timestamp presisi detik)
4. **Where:** Dari mana (IP address, browser, device)
5. **Why:** Mengapa dilakukan (alasan/catatan jika ada)

### Immutability

```
⚠️ PENTING:
Activity Log bersifat IMMUTABLE (tidak bisa diubah/dihapus)

Alasan:
- Menjaga integritas audit trail
- Mencegah manipulasi data
- Memenuhi requirement compliance

Hanya database administrator yang bisa
akses langsung (untuk backup/restore).
```

### Export untuk Audit

```
┌─────────────────────────────────────────┐
│  📊 Export Activity Log                 │
├─────────────────────────────────────────┤
│                                         │
│  Format:                                │
│  ○ Excel (.xlsx)                        │
│  ● CSV (.csv)                           │
│                                         │
│  Periode:                               │
│  [1 Mei 2026 📅] - [31 Mei 2026 📅]     │
│                                         │
│  Filter:                                │
│  ☑ Include User Info                   │
│  ☑ Include IP Address                  │
│  ☑ Include Before/After Data           │
│  ☐ Include System Logs                 │
│                                         │
│  [Batal]  [📥 Download]                │
│                                         │
└─────────────────────────────────────────┘
```

**Hasil Export:**

| Timestamp | User | Role | Action | Transaction | Before | After | IP | Notes |
|-----------|------|------|--------|-------------|--------|-------|----|----|
| 2026-05-21 15:30:45 | Admin Budi | Admin | Approved | RMB-2026-05-0012 | pending | waiting_payment | 192.168.1.100 | Transaksi valid |
| 2026-05-21 14:20:30 | Atasan Siti | Atasan | Updated | PGJ-2026-05-0008 | Rp 1.000.000 | Rp 1.200.000 | 192.168.1.105 | Harga naik |

---

## Tips & Best Practices

### 💡 Tip 1: Review Log Secara Berkala

```
✅ BAIK:
Owner review Activity Log setiap minggu
- Cek aktivitas di luar jam kerja
- Cek transaksi besar (> Rp 1 Jt)
- Cek aktivitas delete/force approve

❌ KURANG BAIK:
Review hanya saat ada masalah
```

### 💡 Tip 2: Gunakan Filter untuk Investigasi

```
✅ BAIK:
Curiga ada fraud → Filter by user + date range
→ Lihat pola aktivitas

❌ KURANG BAIK:
Scroll manual tanpa filter
```

### 💡 Tip 3: Export untuk Audit Eksternal

```
✅ BAIK:
Audit tahunan → Export log 1 tahun
→ Berikan ke auditor

❌ KURANG BAIK:
Screenshot manual satu-satu
```

### 💡 Tip 4: Tambahkan Catatan Saat Aksi Penting

```
✅ BAIK:
Force Approve → Tambahkan alasan jelas
"Biaya admin bank Rp 50.000"

❌ KURANG BAIK:
Force Approve → Tanpa alasan
```

### 💡 Tip 5: Monitor Aktivitas Mencurigakan

```
🚨 Red Flags:
- Aktivitas di luar jam kerja (22:00 - 06:00)
- Banyak delete dalam waktu singkat
- Force approve tanpa alasan
- Login dari IP tidak biasa
```

---

## Troubleshooting

### Masalah 1: Activity Log Tidak Muncul

**Penyebab:**
- Role bukan Admin/Atasan/Owner
- Filter terlalu ketat

**Solusi:**
1. Pastikan role Anda Admin/Atasan/Owner
2. Reset filter ke "Semua"
3. Refresh halaman

### Masalah 2: Export Gagal

**Penyebab:**
- Data terlalu besar (> 100k rows)
- Browser timeout

**Solusi:**
1. Kurangi range tanggal
2. Export per bulan, bukan per tahun
3. Gunakan CSV (lebih ringan dari Excel)

### Masalah 3: Tidak Bisa Cari Transaksi Tertentu

**Penyebab:**
- Salah ketik invoice number
- Transaksi di luar range tanggal

**Solusi:**
1. Cek kembali invoice number
2. Expand range tanggal ke "Semua Waktu"
3. Gunakan filter by user jika tahu siapa yang buat

### Masalah 4: Log Tidak Lengkap

**Penyebab:**
- Aktivitas dilakukan sebelum sistem Activity Log aktif
- Bug sistem (jarang terjadi)

**Solusi:**
1. Cek tanggal aktivitas vs tanggal implementasi Activity Log
2. Hubungi IT jika log seharusnya ada tapi tidak muncul

### Masalah 5: IP Address Tidak Muncul

**Penyebab:**
- User menggunakan VPN
- Proxy server

**Solusi:**
- IP yang tercatat adalah IP yang dilihat server
- Jika menggunakan VPN, IP akan berbeda
- Ini normal dan tidak mempengaruhi audit trail

---

## FAQ

### Q: Apakah Teknisi bisa melihat Activity Log?

**A:** Tidak. Hanya Admin, Atasan, dan Owner yang bisa akses Activity Log.

---

### Q: Apakah Activity Log bisa diedit atau dihapus?

**A:** Tidak. Activity Log bersifat immutable untuk menjaga integritas audit trail.

---

### Q: Berapa lama Activity Log disimpan?

**A:** Permanent. Semua log disimpan tanpa batas waktu untuk keperluan audit.

---

### Q: Apakah login/logout tercatat?

**A:** Ya, semua aktivitas login dan logout tercatat dengan timestamp dan IP address.

---

### Q: Apakah bisa melihat siapa yang melihat transaksi tertentu?

**A:** Saat ini hanya aktivitas create/update/delete yang tercatat. View activity tidak dicatat untuk performa.

---

### Q: Apakah Activity Log mempengaruhi performa sistem?

**A:** Tidak. Logging dilakukan secara asynchronous (background) sehingga tidak mempengaruhi performa user.

---

### Q: Apakah bisa restore data dari Activity Log?

**A:** Activity Log hanya untuk audit, bukan backup. Untuk restore data, hubungi IT untuk backup database.

---

### Q: Bagaimana jika ada aktivitas mencurigakan?

**A:** Hubungi Owner segera. Owner bisa investigasi lebih lanjut dan mengambil tindakan (suspend user, dll).

---

### Q: Apakah Activity Log comply dengan regulasi?

**A:** Ya, Activity Log dirancang untuk memenuhi requirement audit keuangan dan compliance (SOX, ISO 27001, dll).

---

### Q: Apakah bisa filter by IP address?

**A:** Saat ini belum ada filter by IP, tapi bisa search by IP di kolom pencarian.

---

## 📚 Dokumentasi Terkait

- **Sebelumnya:** [Price Index](13_PRICE_INDEX.md) - Deteksi anomali harga
- **Terkait:** [Peran Pengguna](02_PERAN_PENGGUNA.md) - Hak akses per role
- **Terkait:** [Dashboard Analytics](09_DASHBOARD_ANALYTICS.md) - Monitoring transaksi
- **Terkait:** [Troubleshooting](16_TROUBLESHOOTING_USER.md) - Solusi masalah umum

---

**Butuh Bantuan?**  
📧 Email: support@whusnet.com  
💬 Telegram: @WhusnetSupport  
📱 WhatsApp: +62 xxx-xxxx-xxxx

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
