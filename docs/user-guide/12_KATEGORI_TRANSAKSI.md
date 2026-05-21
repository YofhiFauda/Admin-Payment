# 📂 Kategori Transaksi - WHUSNET Admin Payment

**Untuk Siapa:** Admin, Atasan, Owner  
**Waktu Baca:** ~7 menit  
**Level:** Menengah

---

## 📋 Daftar Isi

- [Apa itu Kategori Transaksi?](#apa-itu-kategori-transaksi)
- [Jenis Kategori](#jenis-kategori)
- [Cara Mengelola Kategori](#cara-mengelola-kategori)
- [Mengaktifkan/Menonaktifkan Kategori](#mengaktifkanmenonaktifkan-kategori)
- [Penggunaan Kategori](#penggunaan-kategori)
- [Tips & Best Practices](#tips--best-practices)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## Apa itu Kategori Transaksi?

**Kategori Transaksi** adalah sistem klasifikasi untuk mengelompokkan transaksi berdasarkan jenis pengeluaran, memudahkan pelaporan dan analisis keuangan.

### Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| **Kategori Dinamis** | Tambah, edit, hapus kategori sesuai kebutuhan |
| **2 Tipe Kategori** | Rembush dan Pengajuan |
| **Toggle Status** | Aktifkan/nonaktifkan tanpa hapus data historis |
| **Color Coding** | Warna untuk identifikasi visual |
| **Statistik** | Jumlah transaksi per kategori |

### Manfaat

✅ **Laporan Terstruktur:** Analisis pengeluaran per kategori  
✅ **Fleksibilitas:** Sesuaikan kategori dengan kebutuhan bisnis  
✅ **Data Historis:** Kategori nonaktif tetap tercatat  
✅ **User-Friendly:** Interface modern dengan Glassmorphism design  

---

## Jenis Kategori

### 1. Kategori Rembush

Untuk transaksi reimbursement (belanja yang sudah dibayar).

**Contoh Kategori Rembush:**
- 🔧 Alat Kerja
- 🚗 Transportasi
- 🍽️ Konsumsi
- 📱 Pulsa & Internet
- 🏢 Perlengkapan Kantor
- 🔌 Listrik & Air
- 🛠️ Maintenance
- 📦 Bahan Baku

### 2. Kategori Pengajuan

Untuk transaksi pengajuan pembelian (sebelum beli).

**Contoh Kategori Pengajuan:**
- 💻 Perangkat IT
- 🏗️ Proyek Instalasi
- 📊 Software & Lisensi
- 🚚 Logistik
- 🎓 Training & Development
- 📢 Marketing & Promosi
- 🏢 Renovasi
- 🔧 Peralatan Berat

---

## Cara Mengelola Kategori

### Melihat Daftar Kategori

1. Login sebagai **Admin**, **Atasan**, atau **Owner**
2. Klik menu **"Manajemen"** → **"Kategori Transaksi"**

```
┌─────────────────────────────────────────┐
│  📂 Kategori Transaksi                  │
├─────────────────────────────────────────┤
│                                         │
│  📊 Ringkasan                           │
│  ┌───────────────────────────────────┐ │
│  │ Total Kategori: 16                │ │
│  │ Aktif: 14  │  Nonaktif: 2         │ │
│  │ Rembush: 8  │  Pengajuan: 8       │ │
│  └───────────────────────────────────┘ │
│                                         │
│  [🔍 Cari kategori...]  [➕ Tambah]    │
│                                         │
│  Tab: [● Semua] [○ Rembush] [○ Pengajuan] │
│                                         │
│  ┌───────────────────────────────────┐ │
│  │ 🔧 Alat Kerja                     │ │
│  │ Tipe: Rembush  │  Status: ✅ Aktif│ │
│  │ Transaksi: 45  │  [✏️] [🔄] [🗑️] │ │
│  └───────────────────────────────────┘ │
│                                         │
│  ┌───────────────────────────────────┐ │
│  │ 🚗 Transportasi                   │ │
│  │ Tipe: Rembush  │  Status: ✅ Aktif│ │
│  │ Transaksi: 32  │  [✏️] [🔄] [🗑️] │ │
│  └───────────────────────────────────┘ │
│                                         │
└─────────────────────────────────────────┘
```

### Menambah Kategori Baru

**Langkah 1:** Klik tombol **"➕ Tambah"**

**Langkah 2:** Isi form

```
┌─────────────────────────────────────────┐
│  ➕ Tambah Kategori Baru                │
├─────────────────────────────────────────┤
│                                         │
│  Nama Kategori *                        │
│  [Spare Part Kendaraan__________]       │
│                                         │
│  Tipe Kategori *                        │
│  ● Rembush                              │
│  ○ Pengajuan                            │
│                                         │
│  Icon (Opsional)                        │
│  [🔧_________________________]          │
│                                         │
│  Warna (Opsional)                       │
│  [🎨 #3B82F6] ← Biru                   │
│                                         │
│  Deskripsi (Opsional)                   │
│  [Untuk pembelian spare part___]        │
│  [kendaraan operasional________]        │
│                                         │
│  Status                                 │
│  ☑ Aktif                                │
│                                         │
│  [Batal]  [💾 Simpan]                  │
│                                         │
└─────────────────────────────────────────┘
```

**Field yang Wajib Diisi:**
- ✅ Nama Kategori
- ✅ Tipe Kategori (Rembush/Pengajuan)

**Field Opsional:**
- Icon (emoji untuk visual)
- Warna (hex code untuk color coding)
- Deskripsi (penjelasan kategori)

**Langkah 3:** Klik **"💾 Simpan"**

**Hasil:**
```
✅ Kategori berhasil ditambahkan!

"Spare Part Kendaraan" sekarang tersedia
untuk transaksi Rembush.
```

### Mengedit Kategori

**Langkah 1:** Klik icon **✏️** pada kategori yang ingin diedit

**Langkah 2:** Ubah data

```
┌─────────────────────────────────────────┐
│  ✏️ Edit Kategori                       │
├─────────────────────────────────────────┤
│                                         │
│  Nama Kategori *                        │
│  [Alat Kerja & Perlengkapan_____]       │
│                                         │
│  Tipe Kategori *                        │
│  ● Rembush (tidak bisa ubah)            │
│                                         │
│  Icon                                   │
│  [🔧_________________________]          │
│                                         │
│  Warna                                  │
│  [🎨 #10B981] ← Hijau                  │
│                                         │
│  Deskripsi                              │
│  [Untuk pembelian alat kerja___]        │
│  [dan perlengkapan operasional_]        │
│                                         │
│  Status                                 │
│  ☑ Aktif                                │
│                                         │
│  [Batal]  [💾 Update]                  │
│                                         │
└─────────────────────────────────────────┘
```

**Catatan:**
- Tipe kategori **tidak bisa diubah** setelah dibuat
- Jika perlu ubah tipe, buat kategori baru

**Langkah 3:** Klik **"💾 Update"**

### Menghapus Kategori

**Langkah 1:** Klik icon **🗑️** pada kategori yang ingin dihapus

**Langkah 2:** Konfirmasi

```
┌─────────────────────────────────────────┐
│  ⚠️ Konfirmasi Hapus                    │
├─────────────────────────────────────────┤
│                                         │
│  Apakah Anda yakin ingin menghapus      │
│  kategori "Spare Part Kendaraan"?       │
│                                         │
│  ⚠️ Kategori yang masih digunakan       │
│  dalam transaksi TIDAK DAPAT dihapus!   │
│                                         │
│  Saran: Nonaktifkan kategori jika       │
│  tidak ingin digunakan lagi.            │
│                                         │
│  [Batal]  [🗑️ Ya, Hapus]              │
│                                         │
└─────────────────────────────────────────┘
```

**Catatan Penting:**
- ❌ Kategori dengan transaksi **tidak bisa dihapus**
- ✅ Gunakan fitur **Nonaktifkan** sebagai gantinya
- ✅ Hanya kategori tanpa transaksi yang bisa dihapus

---

## Mengaktifkan/Menonaktifkan Kategori

### Toggle Status

Klik icon **🔄** untuk mengubah status kategori:

```
Status: ✅ Aktif
↓ Klik 🔄
Status: ❌ Nonaktif
```

### Kategori Aktif

```
┌─────────────────────────────────────────┐
│  🔧 Alat Kerja                          │
│  Status: ✅ Aktif                       │
├─────────────────────────────────────────┤
│                                         │
│  ✅ Muncul di form Rembush              │
│  ✅ Bisa dipilih user                   │
│  ✅ Muncul di laporan                   │
│                                         │
└─────────────────────────────────────────┘
```

### Kategori Nonaktif

```
┌─────────────────────────────────────────┐
│  🔧 Alat Kerja (Nonaktif)               │
│  Status: ❌ Nonaktif                    │
├─────────────────────────────────────────┤
│                                         │
│  ❌ Tidak muncul di form Rembush        │
│  ❌ Tidak bisa dipilih user             │
│  ✅ Data historis tetap ada             │
│  ✅ Masih muncul di laporan lama        │
│                                         │
└─────────────────────────────────────────┘
```

### Kapan Menonaktifkan?

✅ **Nonaktifkan Jika:**
- Kategori tidak relevan lagi
- Ingin mencegah penggunaan di masa depan
- Perlu "arsip" kategori lama

❌ **Jangan Hapus Jika:**
- Kategori masih ada transaksinya
- Perlu data historis untuk audit

---

## Penggunaan Kategori

### Di Form Rembush

```
┌─────────────────────────────────────────┐
│  📋 Form Rembush                        │
├─────────────────────────────────────────┤
│                                         │
│  Kategori *                             │
│  [▼ Pilih Kategori]                    │
│  - 🔧 Alat Kerja                        │
│  - 🚗 Transportasi                      │
│  - 🍽️ Konsumsi                         │
│  - 📱 Pulsa & Internet                  │
│  - 🏢 Perlengkapan Kantor               │
│  - ... (hanya kategori aktif)           │
│                                         │
└─────────────────────────────────────────┘
```

### Di Form Pengajuan

```
┌─────────────────────────────────────────┐
│  📋 Form Pengajuan                      │
├─────────────────────────────────────────┤
│                                         │
│  Kategori *                             │
│  [▼ Pilih Kategori]                    │
│  - 💻 Perangkat IT                      │
│  - 🏗️ Proyek Instalasi                 │
│  - 📊 Software & Lisensi                │
│  - 🚚 Logistik                          │
│  - ... (hanya kategori aktif)           │
│                                         │
└─────────────────────────────────────────┘
```

### Di Laporan

```
┌─────────────────────────────────────────┐
│  📊 Laporan Pengeluaran per Kategori    │
│  Periode: Mei 2026                      │
├─────────────────────────────────────────┤
│                                         │
│  🔧 Alat Kerja                          │
│  45 transaksi  │  Rp 12.500.000         │
│                                         │
│  🚗 Transportasi                        │
│  32 transaksi  │  Rp 8.200.000          │
│                                         │
│  🍽️ Konsumsi                           │
│  28 transaksi  │  Rp 5.600.000          │
│                                         │
│  ... (termasuk kategori nonaktif        │
│       jika ada transaksi di periode)    │
│                                         │
└─────────────────────────────────────────┘
```

---

## Tips & Best Practices

### 💡 Tip 1: Nama Kategori yang Jelas

```
✅ BAIK:
- Alat Kerja & Perlengkapan
- Transportasi & BBM
- Konsumsi Client Meeting

❌ KURANG BAIK:
- Alat
- Transport
- Makan
```

### 💡 Tip 2: Gunakan Icon untuk Visual

```
✅ BAIK:
🔧 Alat Kerja
🚗 Transportasi
🍽️ Konsumsi

❌ KURANG BAIK:
Alat Kerja (tanpa icon)
```

### 💡 Tip 3: Jangan Terlalu Banyak Kategori

```
✅ BAIK:
8-12 kategori per tipe (mudah dipilih)

❌ KURANG BAIK:
30+ kategori per tipe (membingungkan)
```

### 💡 Tip 4: Nonaktifkan, Jangan Hapus

```
✅ BAIK:
Kategori tidak relevan → Nonaktifkan

❌ KURANG BAIK:
Kategori tidak relevan → Hapus (data historis hilang)
```

### 💡 Tip 5: Review Kategori Secara Berkala

```
✅ BAIK:
Review setiap 3-6 bulan
- Kategori mana yang jarang digunakan?
- Perlu tambah kategori baru?
- Perlu nonaktifkan kategori lama?

❌ KURANG BAIK:
Buat kategori sekali, tidak pernah review
```

---

## Troubleshooting

### Masalah 1: Kategori Tidak Muncul di Form

**Penyebab:**
- Kategori dinonaktifkan
- Tipe kategori tidak sesuai (Rembush vs Pengajuan)

**Solusi:**
1. Cek status kategori (Aktif/Nonaktif)
2. Aktifkan kategori jika perlu
3. Pastikan tipe kategori sesuai (Rembush untuk form Rembush, dst)

### Masalah 2: Tidak Bisa Hapus Kategori

**Penyebab:**
- Kategori masih digunakan dalam transaksi

**Solusi:**
1. Nonaktifkan kategori sebagai gantinya
2. Atau tunggu sampai tidak ada transaksi yang menggunakan
3. Kategori nonaktif tidak akan muncul di form baru

### Masalah 3: Warna Kategori Tidak Muncul

**Penyebab:**
- Format warna salah
- Browser tidak mendukung

**Solusi:**
1. Gunakan format hex code (#RRGGBB)
2. Contoh: #3B82F6 (biru), #10B981 (hijau)
3. Refresh halaman

### Masalah 4: Duplikat Nama Kategori

**Penyebab:**
- Nama kategori sudah ada

**Solusi:**
1. Gunakan nama yang berbeda
2. Atau tambahkan deskripsi untuk membedakan
3. Contoh: "Alat Kerja (Teknisi)" vs "Alat Kerja (Kantor)"

### Masalah 5: Kategori Hilang Setelah Dinonaktifkan

**Penyebab:**
- Filter hanya menampilkan kategori aktif

**Solusi:**
1. Ubah filter ke "Semua" atau "Nonaktif"
2. Kategori nonaktif tetap ada, hanya tidak muncul di form
3. Data historis tetap aman

---

## FAQ

### Q: Berapa banyak kategori yang bisa dibuat?

**A:** Tidak ada batasan jumlah. Namun, disarankan 8-12 kategori per tipe untuk kemudahan penggunaan.

---

### Q: Apakah bisa mengubah tipe kategori (Rembush → Pengajuan)?

**A:** Tidak. Tipe kategori tidak bisa diubah setelah dibuat. Jika perlu, buat kategori baru dengan tipe yang benar.

---

### Q: Apakah kategori nonaktif masih muncul di laporan?

**A:** Ya, kategori nonaktif tetap muncul di laporan jika ada transaksi yang menggunakan kategori tersebut di periode yang dipilih.

---

### Q: Apakah bisa menghapus kategori yang sudah digunakan?

**A:** Tidak. Kategori yang sudah digunakan dalam transaksi tidak bisa dihapus untuk menjaga integritas data. Gunakan fitur Nonaktifkan sebagai gantinya.

---

### Q: Apakah Teknisi bisa menambah kategori?

**A:** Tidak. Hanya Admin, Atasan, dan Owner yang bisa mengelola kategori.

---

### Q: Apakah warna kategori wajib diisi?

**A:** Tidak wajib. Warna hanya untuk identifikasi visual. Jika tidak diisi, sistem akan menggunakan warna default.

---

### Q: Bagaimana cara mengurutkan kategori?

**A:** Saat ini kategori diurutkan berdasarkan nama (A-Z). Fitur custom sorting akan ditambahkan di versi mendatang.

---

### Q: Apakah bisa menggunakan kategori yang sama untuk Rembush dan Pengajuan?

**A:** Tidak langsung. Anda perlu membuat 2 kategori terpisah dengan nama yang sama tapi tipe berbeda.

---

### Q: Apakah ada audit trail untuk perubahan kategori?

**A:** Ya, semua perubahan kategori tercatat di Activity Log dengan detail lengkap.

---

### Q: Bagaimana jika salah pilih kategori saat input transaksi?

**A:** Admin/Atasan/Owner bisa edit transaksi dan mengubah kategori sebelum status menjadi completed.

---

## 📚 Dokumentasi Terkait

- **Sebelumnya:** [Rekening Bank](11_REKENING_BANK.md) - Kelola rekening cabang
- **Selanjutnya:** [Price Index](13_PRICE_INDEX.md) - Sistem referensi harga
- **Terkait:** [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md) - Penggunaan kategori di Rembush
- **Terkait:** [Panduan Pengajuan](04_PENGAJUAN_PEMBELIAN.md) - Penggunaan kategori di Pengajuan

---

**Butuh Bantuan?**  
📧 Email: support@whusnet.com  
💬 Telegram: @WhusnetSupport  
📱 WhatsApp: +62 xxx-xxxx-xxxx

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
