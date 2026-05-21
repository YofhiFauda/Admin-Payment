# 💡 Price Index & Deteksi Anomali - WHUSNET Admin Payment

**Untuk Siapa:** Owner (Khusus)  
**Waktu Baca:** ~12 menit  
**Level:** Lanjut

---

## 📋 Daftar Isi

- [Apa itu Price Index?](#apa-itu-price-index)
- [Mengapa Penting?](#mengapa-penting)
- [Cara Kerja Sistem](#cara-kerja-sistem)
- [Deteksi Anomali Harga](#deteksi-anomali-harga)
- [Dashboard Price Index](#dashboard-price-index)
- [Manual Lock Harga](#manual-lock-harga)
- [Cold Start Problem](#cold-start-problem)
- [Tips & Best Practices](#tips--best-practices)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## Apa itu Price Index?

**Price Index** adalah sistem referensi harga otomatis yang menghitung harga Min/Max/Avg untuk setiap item berdasarkan riwayat transaksi, dan mendeteksi anomali harga secara real-time.

### Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| **Auto-Calculated** | Hitung harga Min/Max/Avg otomatis |
| **Outlier Filtering** | Algoritma IQR untuk buang data tidak wajar |
| **Real-time Detection** | Alert saat input harga melebihi referensi |
| **Anomaly Hub** | Dashboard khusus Owner untuk review anomali |
| **Manual Lock** | Owner bisa lock harga referensi manual |
| **Cold Start Handling** | Sistem khusus untuk item baru |

### Manfaat

✅ **Efisiensi Anggaran:** Cegah pembelian dengan harga terlalu tinggi  
✅ **Transparansi:** Harga referensi jelas untuk semua  
✅ **Deteksi Fraud:** Alert otomatis untuk harga mencurigakan  
✅ **Data-Driven:** Keputusan berdasarkan data historis  

---

## Mengapa Penting?

### Skenario Bisnis

```
Contoh Kasus:
Item: Kabel LAN Cat6 100m

Riwayat Harga (5 transaksi terakhir):
1. Rp 80.000
2. Rp 85.000
3. Rp 82.000
4. Rp 500.000 ← Outlier (typo atau fraud)
5. Rp 83.000

Tanpa Price Index:
Harga Avg: Rp 166.000 (tidak akurat!)

Dengan Price Index (IQR Filtering):
Outlier Rp 500.000 dibuang
Harga Avg: Rp 82.500 (akurat!)
Harga Max: Rp 85.000

Teknisi input Rp 150.000:
🚨 Alert: Harga melebihi referensi +76%!
```

### ROI (Return on Investment)

```
Sebelum Price Index:
- Pembelian kabel Rp 150.000 (seharusnya Rp 85.000)
- Kerugian: Rp 65.000 per transaksi
- 10 transaksi/bulan = Rp 650.000/bulan
- Rp 7.800.000/tahun

Setelah Price Index:
- Alert otomatis untuk harga tinggi
- Owner review sebelum approve
- Hemat ~70% dari kerugian
- ROI: Rp 5.460.000/tahun
```

---

## Cara Kerja Sistem

### 1. Pengumpulan Data

Sistem mengumpulkan data harga dari transaksi yang **disetujui**:

```
┌─────────────────────────────────────────┐
│                                         │
│  Transaksi Approved/Completed           │
│           ↓                             │
│  Ekstrak Item & Harga                   │
│           ↓                             │
│  Simpan ke Database Price Index         │
│                                         │
└─────────────────────────────────────────┘
```

**Kriteria Data:**
- ✅ Status: `approved` atau `completed`
- ✅ Harga > 0
- ✅ Nama item tidak kosong
- ❌ Status: `pending`, `rejected`, `auto-reject`

### 2. Outlier Filtering (IQR)

Sistem menggunakan **Interquartile Range (IQR)** untuk membuang data outlier:

```
Contoh Data Harga:
[80, 82, 83, 85, 87, 90, 500]

Langkah IQR:
1. Urutkan: [80, 82, 83, 85, 87, 90, 500]
2. Q1 (25%): 82
3. Q3 (75%): 90
4. IQR = Q3 - Q1 = 8
5. Lower Bound = Q1 - (1.5 × IQR) = 70
6. Upper Bound = Q3 + (1.5 × IQR) = 102
7. Buang data di luar range: 500 ❌

Data Valid: [80, 82, 83, 85, 87, 90]
```

### 3. Kalkulasi Referensi

Setelah outlier dibuang, sistem menghitung:

```
Min Price: Rp 80.000
Max Price: Rp 90.000
Avg Price: Rp 84.500
Std Dev: Rp 3.800
Sample Count: 6 transaksi
```

### 4. Update Otomatis

Price Index diupdate secara otomatis:

```
Incremental Update (Daily):
- Recalculate item dengan transaksi baru
- Cron job: 02:00 WIB setiap hari

Full Recalculation (Weekly):
- Recalculate semua item non-manual
- Cron job: Minggu 03:00 WIB
```

---

## Deteksi Anomali Harga

### Level Anomali

Sistem mengklasifikasikan anomali berdasarkan selisih dari harga referensi:

| Level | Selisih | Warna | Aksi |
|-------|---------|-------|------|
| **Critical** | > +50% | 🔴 Merah | Wajib review Owner |
| **Medium** | +25% - +50% | 🟡 Kuning | Rekomendasi review |
| **Low** | +10% - +25% | 🟢 Hijau | Informasi saja |

### Contoh Deteksi

```
Item: Kabel LAN Cat6 100m
Referensi Max: Rp 90.000

Input Teknisi: Rp 150.000
Selisih: +66.67%
Level: 🔴 Critical

Alert:
┌─────────────────────────────────────────┐
│  🚨 Anomali Harga Terdeteksi!           │
├─────────────────────────────────────────┤
│                                         │
│  Item: Kabel LAN Cat6 100m              │
│  Harga Input: Rp 150.000                │
│  Referensi Max: Rp 90.000               │
│  Selisih: +66.67% (Critical)            │
│                                         │
│  ⚠️ Harga ini jauh melebihi referensi!  │
│                                         │
│  Apakah Anda yakin harga ini benar?     │
│  - Cek kembali nota                     │
│  - Bandingkan dengan toko lain          │
│  - Hubungi Admin jika ragu              │
│                                         │
│  [Batal]  [Lanjutkan Tetap]            │
│                                         │
└─────────────────────────────────────────┘
```

### Real-time Alert

Alert muncul di 3 tempat:

1. **Form Input:** Saat teknisi input harga
2. **Dashboard Owner:** Notifikasi real-time
3. **Telegram Owner:** Alert untuk anomali Critical

---

## Dashboard Price Index

### Akses Dashboard

1. Login sebagai **Owner**
2. Klik menu **"Laporan"** → **"Price Index"**

```
┌─────────────────────────────────────────┐
│  💡 Price Index & Anomali Harga         │
├─────────────────────────────────────────┤
│                                         │
│  📊 Ringkasan                           │
│  ┌───────────────────────────────────┐ │
│  │ Total Item: 245                   │ │
│  │ Anomali Aktif: 8                  │ │
│  │ - Critical: 3  🔴                 │ │
│  │ - Medium: 3    🟡                 │ │
│  │ - Low: 2       🟢                 │ │
│  └───────────────────────────────────┘ │
│                                         │
│  [Filter: Semua ▼] [🔍 Cari item...]   │
│                                         │
│  Tab: [● Anomali] [○ Semua Item]       │
│                                         │
└─────────────────────────────────────────┘
```

### Daftar Anomali

```
┌─────────────────────────────────────────┐
│  🚨 Anomali Harga (8)                   │
├─────────────────────────────────────────┤
│                                         │
│  🔴 Critical (3)                        │
│                                         │
│  1. Kabel LAN Cat6 100m                 │
│     Input: Rp 150.000 | Max: Rp 90.000  │
│     Selisih: +66.67%                    │
│     Transaksi: RMB-2026-05-0012         │
│     [Review] [Approve] [Reject]         │
│                                         │
│  2. Router Mikrotik RB750               │
│     Input: Rp 1.200.000 | Max: Rp 750.000│
│     Selisih: +60%                       │
│     Transaksi: PGJ-2026-05-0008         │
│     [Review] [Approve] [Reject]         │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
│  🟡 Medium (3)                          │
│  ... (list medium anomalies)            │
│                                         │
│  🟢 Low (2)                             │
│  ... (list low anomalies)               │
│                                         │
└─────────────────────────────────────────┘
```

### Detail Item

Klik item untuk melihat detail:

```
┌─────────────────────────────────────────┐
│  📊 Detail Price Index                  │
│  Item: Kabel LAN Cat6 100m              │
├─────────────────────────────────────────┤
│                                         │
│  Statistik Harga:                       │
│  Min: Rp 80.000                         │
│  Max: Rp 90.000                         │
│  Avg: Rp 84.500                         │
│  Std Dev: Rp 3.800                      │
│                                         │
│  Data:                                  │
│  Sample: 6 transaksi                    │
│  Outliers Removed: 1                    │
│  Last Updated: 21 Mei 2026, 02:00       │
│                                         │
│  Status: 🔓 Auto-Calculated             │
│  [🔒 Lock Manual]                       │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
│  Riwayat Harga (10 terakhir):           │
│  1. Rp 90.000 - 20 Mei 2026             │
│  2. Rp 87.000 - 18 Mei 2026             │
│  3. Rp 85.000 - 15 Mei 2026             │
│  4. Rp 83.000 - 12 Mei 2026             │
│  5. Rp 82.000 - 10 Mei 2026             │
│  ... (5 more)                           │
│                                         │
│  [📈 Lihat Grafik] [📄 Export]         │
│                                         │
└─────────────────────────────────────────┘
```

---

## Manual Lock Harga

### Kapan Menggunakan Manual Lock?

✅ **Gunakan Manual Lock Jika:**
- Harga sudah stabil dan tidak perlu update otomatis
- Ada kontrak harga tetap dengan supplier
- Ingin mencegah fluktuasi harga referensi
- Harga otomatis tidak akurat untuk item tertentu

### Cara Lock Harga

**Langkah 1:** Buka detail item di dashboard Price Index

**Langkah 2:** Klik tombol **"🔒 Lock Manual"**

**Langkah 3:** Isi harga manual

```
┌─────────────────────────────────────────┐
│  🔒 Lock Harga Manual                   │
│  Item: Kabel LAN Cat6 100m              │
├─────────────────────────────────────────┤
│                                         │
│  Harga Referensi Saat Ini:              │
│  Min: Rp 80.000                         │
│  Max: Rp 90.000                         │
│  Avg: Rp 84.500                         │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
│  Harga Manual Baru:                     │
│                                         │
│  Min Price *                            │
│  [Rp 80.000___________________]         │
│                                         │
│  Max Price *                            │
│  [Rp 85.000___________________]         │
│                                         │
│  Avg Price *                            │
│  [Rp 82.500___________________]         │
│                                         │
│  Alasan Lock (Opsional)                 │
│  [Kontrak dengan supplier______]        │
│  [harga tetap Rp 85.000________]        │
│                                         │
│  ⚠️ Harga manual tidak akan diupdate    │
│  otomatis sampai di-unlock.             │
│                                         │
│  [Batal]  [🔒 Lock]                    │
│                                         │
└─────────────────────────────────────────┘
```

**Langkah 4:** Klik **"🔒 Lock"**

**Hasil:**
```
✅ Harga berhasil di-lock!

Item: Kabel LAN Cat6 100m
Status: 🔒 Manual Lock
Harga Max: Rp 85.000

Harga ini tidak akan diupdate otomatis
sampai Anda unlock.
```

### Cara Unlock Harga

**Langkah 1:** Buka detail item yang di-lock

**Langkah 2:** Klik tombol **"🔓 Unlock"**

**Langkah 3:** Konfirmasi

```
┌─────────────────────────────────────────┐
│  🔓 Unlock Harga                        │
├─────────────────────────────────────────┤
│                                         │
│  Apakah Anda yakin ingin unlock harga   │
│  untuk item "Kabel LAN Cat6 100m"?      │
│                                         │
│  Setelah unlock, harga akan kembali     │
│  dihitung otomatis berdasarkan data     │
│  transaksi terbaru.                     │
│                                         │
│  [Batal]  [🔓 Ya, Unlock]              │
│                                         │
└─────────────────────────────────────────┘
```

---

## Cold Start Problem

### Apa itu Cold Start?

**Cold Start** adalah kondisi ketika item baru belum memiliki data historis untuk kalkulasi harga referensi.

### Contoh Kasus

```
Item Baru: Router Mikrotik RB4011
Transaksi: 0
Data Harga: Tidak ada

Masalah:
- Tidak ada harga Min/Max/Avg
- Tidak bisa deteksi anomali
- Teknisi bebas input harga apapun
```

### Solusi Sistem

Sistem menangani Cold Start dengan 3 cara:

#### 1. Threshold Minimum

```
Minimum Sample: 3 transaksi

Jika sample < 3:
- Tidak ada deteksi anomali
- Warning: "Data belum cukup untuk referensi"
- Owner bisa set harga manual
```

#### 2. Manual Seed

Owner bisa set harga awal untuk item baru:

```
┌─────────────────────────────────────────┐
│  🌱 Seed Harga Awal                     │
│  Item: Router Mikrotik RB4011           │
├─────────────────────────────────────────┤
│                                         │
│  ⚠️ Item ini belum memiliki data harga. │
│                                         │
│  Set harga referensi awal:              │
│                                         │
│  Min Price *                            │
│  [Rp 1.800.000________________]         │
│                                         │
│  Max Price *                            │
│  [Rp 2.200.000________________]         │
│                                         │
│  Avg Price *                            │
│  [Rp 2.000.000________________]         │
│                                         │
│  Sumber Referensi                       │
│  [Harga dari Tokopedia_________]        │
│                                         │
│  [Batal]  [💾 Simpan]                  │
│                                         │
└─────────────────────────────────────────┘
```

#### 3. Gradual Learning

Sistem belajar dari transaksi pertama:

```
Transaksi 1: Rp 2.000.000
→ Set sebagai referensi sementara
→ Warning: "Referensi berdasarkan 1 transaksi"

Transaksi 2: Rp 2.100.000
→ Update referensi (Min: 2.000.000, Max: 2.100.000)
→ Warning: "Referensi berdasarkan 2 transaksi"

Transaksi 3: Rp 1.950.000
→ Update referensi (Min: 1.950.000, Max: 2.100.000)
→ ✅ Deteksi anomali aktif (sample ≥ 3)
```

---

## Tips & Best Practices

### 💡 Tip 1: Review Anomali Secara Rutin

```
✅ BAIK:
Review dashboard Price Index setiap hari
- Cek anomali Critical terlebih dahulu
- Approve/Reject dengan alasan jelas

❌ KURANG BAIK:
Review hanya saat ada komplain
```

### 💡 Tip 2: Gunakan Manual Lock dengan Bijak

```
✅ BAIK:
Lock harga untuk item dengan kontrak tetap

❌ KURANG BAIK:
Lock semua harga (sistem tidak bisa belajar)
```

### 💡 Tip 3: Seed Harga untuk Item Baru

```
✅ BAIK:
Item baru → Riset harga pasar → Seed harga awal

❌ KURANG BAIK:
Item baru → Biarkan tanpa referensi
```

### 💡 Tip 4: Komunikasi dengan Tim

```
✅ BAIK:
Anomali terdeteksi → Tanyakan ke Teknisi/Admin
→ Cari tahu alasan harga tinggi
→ Approve/Reject dengan informasi lengkap

❌ KURANG BAIK:
Anomali terdeteksi → Langsung reject tanpa tanya
```

### 💡 Tip 5: Monitor Tren Harga

```
✅ BAIK:
Gunakan grafik untuk lihat tren harga
- Harga naik terus? Mungkin inflasi
- Harga turun terus? Mungkin promo
- Adjust referensi jika perlu

❌ KURANG BAIK:
Hanya lihat angka, tidak lihat tren
```

---

## Troubleshooting

### Masalah 1: Harga Referensi Tidak Akurat

**Penyebab:**
- Data outlier tidak terfilter dengan baik
- Sample terlalu sedikit

**Solusi:**
1. Cek jumlah sample (minimal 3)
2. Review data historis, ada outlier?
3. Gunakan Manual Lock jika perlu
4. Tunggu lebih banyak transaksi untuk data lebih akurat

### Masalah 2: Anomali Tidak Terdeteksi

**Penyebab:**
- Item belum ada di Price Index (Cold Start)
- Sample < 3 transaksi

**Solusi:**
1. Cek apakah item ada di dashboard Price Index
2. Jika belum ada, seed harga awal
3. Tunggu minimal 3 transaksi untuk deteksi otomatis

### Masalah 3: Terlalu Banyak False Positive

**Penyebab:**
- Threshold terlalu ketat
- Harga pasar memang naik

**Solusi:**
1. Review threshold (saat ini +50% untuk Critical)
2. Update harga referensi manual jika harga pasar naik
3. Atau lock harga baru yang lebih tinggi

### Masalah 4: Dashboard Price Index Tidak Muncul

**Penyebab:**
- Role bukan Owner

**Solusi:**
- Hanya Owner yang bisa akses Price Index
- Hubungi Owner jika perlu informasi harga referensi

### Masalah 5: Harga Tidak Terupdate Otomatis

**Penyebab:**
- Harga di-lock manual
- Cron job tidak berjalan

**Solusi:**
1. Cek status item (Lock/Unlock)
2. Jika lock, unlock untuk update otomatis
3. Hubungi IT jika cron job bermasalah

---

## FAQ

### Q: Apakah semua user bisa melihat Price Index?

**A:** Tidak. Hanya Owner yang bisa akses dashboard Price Index. Namun, alert anomali muncul untuk semua user saat input harga.

---

### Q: Berapa lama data historis yang digunakan?

**A:** Sistem menggunakan semua data transaksi approved/completed tanpa batasan waktu. Namun, outlier akan difilter otomatis.

---

### Q: Apakah bisa disable deteksi anomali?

**A:** Tidak bisa disable sepenuhnya. Namun, Owner bisa approve anomali dengan alasan jika harga memang benar.

---

### Q: Bagaimana jika harga pasar naik signifikan?

**A:** Owner bisa update harga referensi manual (Manual Lock) atau tunggu sistem belajar dari transaksi baru.

---

### Q: Apakah Price Index berlaku untuk semua tipe transaksi?

**A:** Ya, Price Index berlaku untuk Rembush, Pengajuan, dan Gudang.

---

### Q: Berapa threshold untuk anomali Critical?

**A:** Saat ini +50% dari harga referensi Max. Threshold ini bisa disesuaikan oleh IT jika perlu.

---

### Q: Apakah bisa export data Price Index?

**A:** Ya, Owner bisa export data Price Index ke Excel/CSV untuk analisis lebih lanjut.

---

### Q: Bagaimana jika item memiliki variasi harga (misal: kabel 50m vs 100m)?

**A:** Sistem menganggap nama item yang berbeda sebagai item terpisah. Pastikan nama item konsisten (misal: "Kabel LAN Cat6 100m" vs "Kabel LAN Cat6 50m").

---

### Q: Apakah ada notifikasi untuk anomali baru?

**A:** Ya, Owner menerima notifikasi real-time di dashboard dan Telegram untuk anomali Critical.

---

### Q: Bagaimana cara melihat riwayat perubahan harga referensi?

**A:** Klik item di dashboard Price Index, lalu klik "Lihat Grafik" untuk melihat tren harga historis.

---

## 📚 Dokumentasi Terkait

- **Sebelumnya:** [Kategori Transaksi](12_KATEGORI_TRANSAKSI.md) - Kelola kategori
- **Selanjutnya:** [Activity Log](14_ACTIVITY_LOG.md) - Audit trail lengkap
- **Terkait:** [Dashboard Analytics](09_DASHBOARD_ANALYTICS.md) - Monitoring pengeluaran
- **Terkait:** [Panduan Pengajuan](04_PENGAJUAN_PEMBELIAN.md) - Deteksi anomali di Pengajuan

---

**Butuh Bantuan?**  
📧 Email: support@whusnet.com  
💬 Telegram: @WhusnetSupport  
📱 WhatsApp: +62 xxx-xxxx-xxxx

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
