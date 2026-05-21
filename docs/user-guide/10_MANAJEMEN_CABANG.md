# 🏢 Manajemen Cabang - WHUSNET Admin Payment

**Untuk Siapa:** Admin, Atasan, Owner  
**Waktu Baca:** ~10 menit  
**Level:** Menengah

---

## 📋 Daftar Isi

- [Apa itu Manajemen Cabang?](#apa-itu-manajemen-cabang)
- [Mengapa Penting?](#mengapa-penting)
- [Cara Mengelola Cabang](#cara-mengelola-cabang)
- [Alokasi Biaya ke Cabang](#alokasi-biaya-ke-cabang)
- [Hutang Antar Cabang](#hutang-antar-cabang)
- [Pelunasan Hutang](#pelunasan-hutang)
- [Tips & Best Practices](#tips--best-practices)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## Apa itu Manajemen Cabang?

**Manajemen Cabang** adalah fitur untuk mengelola data cabang/unit bisnis dan melacak alokasi biaya transaksi ke setiap cabang.

### Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| **CRUD Cabang** | Tambah, edit, hapus cabang |
| **Alokasi Biaya** | Distribusi biaya transaksi ke beberapa cabang |
| **Hutang Antar Cabang** | Tracking hutang-piutang antar unit |
| **Pelunasan** | Pencatatan pelunasan hutang dengan bukti transfer |
| **Dashboard Monitoring** | Visualisasi biaya per cabang real-time |

### Manfaat

✅ **Transparansi Biaya:** Setiap cabang tahu berapa pengeluarannya  
✅ **Akuntabilitas:** Tracking hutang antar cabang yang jelas  
✅ **Laporan Akurat:** Data biaya per cabang untuk analisis  
✅ **Audit Trail:** Semua perubahan tercatat lengkap  

---

## Mengapa Penting?

### Skenario Bisnis

```
Contoh: WHUSNET memiliki 3 cabang
- Cabang A (Jakarta)
- Cabang B (Bandung)
- Cabang C (Surabaya)

Teknisi dari Cabang A membeli kabel untuk instalasi di Cabang B.

Pertanyaan:
- Siapa yang menanggung biaya?
- Bagaimana tracking-nya?
- Bagaimana pelunasannya?

Jawaban: Manajemen Cabang!
```

### Alur Tracking Biaya

```
┌─────────────────────────────────────────┐
│                                         │
│  Transaksi Rp 1.000.000                 │
│           ↓                             │
│  Alokasi ke Cabang:                     │
│  - Cabang A: Rp 400.000 (40%)           │
│  - Cabang B: Rp 600.000 (60%)           │
│           ↓                             │
│  Cabang B berhutang Rp 600.000          │
│  ke Cabang A (yang bayar)               │
│           ↓                             │
│  Cabang B transfer Rp 600.000           │
│  ke rekening Cabang A                   │
│           ↓                             │
│  Hutang lunas ✅                        │
│                                         │
└─────────────────────────────────────────┘
```

---

## Cara Mengelola Cabang

### Melihat Daftar Cabang

1. Login sebagai **Admin**, **Atasan**, atau **Owner**
2. Klik menu **"Manajemen"** → **"Cabang"**

```
┌─────────────────────────────────────────┐
│  🏢 Daftar Cabang                       │
├─────────────────────────────────────────┤
│                                         │
│  [🔍 Cari cabang...]  [➕ Tambah]      │
│                                         │
│  ┌───────────────────────────────────┐ │
│  │ No │ Nama Cabang │ Aksi          │ │
│  ├───────────────────────────────────┤ │
│  │ 1  │ Cabang A    │ [✏️] [🗑️]    │ │
│  │ 2  │ Cabang B    │ [✏️] [🗑️]    │ │
│  │ 3  │ Cabang C    │ [✏️] [🗑️]    │ │
│  └───────────────────────────────────┘ │
│                                         │
│  Total: 3 cabang                        │
│                                         │
└─────────────────────────────────────────┘
```

### Menambah Cabang Baru

**Langkah 1:** Klik tombol **"➕ Tambah"**

**Langkah 2:** Isi form

```
┌─────────────────────────────────────────┐
│  ➕ Tambah Cabang Baru                  │
├─────────────────────────────────────────┤
│                                         │
│  Nama Cabang *                          │
│  [Cabang D - Medan______________]       │
│                                         │
│  Keterangan (Opsional)                  │
│  [Cabang baru di Medan__________]       │
│  [____________________________]         │
│                                         │
│  [Batal]  [💾 Simpan]                  │
│                                         │
└─────────────────────────────────────────┘
```

**Langkah 3:** Klik **"💾 Simpan"**

**Hasil:**
```
✅ Cabang berhasil ditambahkan!
Cabang "Cabang D - Medan" sekarang tersedia untuk alokasi transaksi.
```

### Mengedit Cabang

**Langkah 1:** Klik icon **✏️** pada cabang yang ingin diedit

**Langkah 2:** Ubah data

```
┌─────────────────────────────────────────┐
│  ✏️ Edit Cabang                         │
├─────────────────────────────────────────┤
│                                         │
│  Nama Cabang *                          │
│  [Cabang A - Jakarta Pusat______]       │
│                                         │
│  Keterangan                             │
│  [Kantor pusat di Jakarta_______]       │
│                                         │
│  [Batal]  [💾 Update]                  │
│                                         │
└─────────────────────────────────────────┘
```

**Langkah 3:** Klik **"💾 Update"**

### Menghapus Cabang

**Langkah 1:** Klik icon **🗑️** pada cabang yang ingin dihapus

**Langkah 2:** Konfirmasi

```
┌─────────────────────────────────────────┐
│  ⚠️ Konfirmasi Hapus                    │
├─────────────────────────────────────────┤
│                                         │
│  Apakah Anda yakin ingin menghapus      │
│  cabang "Cabang D - Medan"?             │
│                                         │
│  ⚠️ Cabang yang masih memiliki          │
│  transaksi TIDAK DAPAT dihapus!         │
│                                         │
│  [Batal]  [🗑️ Ya, Hapus]              │
│                                         │
└─────────────────────────────────────────┘
```

**Catatan Penting:**
- ❌ Cabang dengan transaksi aktif **tidak bisa dihapus**
- ✅ Hanya cabang tanpa transaksi yang bisa dihapus
- 💡 Jika perlu "menonaktifkan" cabang, ubah namanya menjadi "Cabang X (Nonaktif)"

---

## Alokasi Biaya ke Cabang

### 3 Metode Distribusi

Saat membuat transaksi (Rembush/Pengajuan/Gudang), Anda bisa memilih metode alokasi:

#### 1. Equal (Rata)

Biaya dibagi rata ke semua cabang yang dipilih.

```
Contoh:
Total: Rp 1.200.000
Cabang: A, B, C (3 cabang)

Hasil:
- Cabang A: Rp 400.000 (33.33%)
- Cabang B: Rp 400.000 (33.33%)
- Cabang C: Rp 400.000 (33.33%)
```

**Kapan Digunakan:**
- Biaya bersama (listrik, internet kantor pusat)
- Pembelian alat yang digunakan bersama

#### 2. Percentage (Persentase)

Anda tentukan persentase untuk setiap cabang.

```
Contoh:
Total: Rp 1.200.000

Input:
- Cabang A: 50%
- Cabang B: 30%
- Cabang C: 20%

Hasil:
- Cabang A: Rp 600.000 (50%)
- Cabang B: Rp 360.000 (30%)
- Cabang C: Rp 240.000 (20%)
```

**Kapan Digunakan:**
- Biaya berdasarkan proporsi penggunaan
- Biaya berdasarkan jumlah karyawan per cabang

#### 3. Manual (Nominal)

Anda tentukan nominal untuk setiap cabang.

```
Contoh:
Total: Rp 1.200.000

Input:
- Cabang A: Rp 500.000
- Cabang B: Rp 400.000
- Cabang C: Rp 300.000

Hasil:
- Cabang A: Rp 500.000 (41.67%)
- Cabang B: Rp 400.000 (33.33%)
- Cabang C: Rp 300.000 (25.00%)
```

**Kapan Digunakan:**
- Biaya spesifik per cabang
- Biaya yang sudah dihitung sebelumnya

### Validasi Alokasi

Sistem akan memvalidasi bahwa **total alokasi = total transaksi**:

```
✅ VALID:
Total Transaksi: Rp 1.200.000
Total Alokasi:   Rp 1.200.000
Status: Bisa submit

❌ INVALID:
Total Transaksi: Rp 1.200.000
Total Alokasi:   Rp 1.150.000
Status: Error - "Total alokasi tidak sesuai"
```

---

## Hutang Antar Cabang

### Konsep Hutang

Ketika transaksi dialokasikan ke beberapa cabang, cabang yang **tidak membayar** akan berhutang ke cabang yang **membayar**.

### Contoh Kasus

```
Skenario:
Teknisi dari Cabang A membeli kabel Rp 1.000.000
untuk instalasi di Cabang B.

Alokasi:
- Cabang A: Rp 400.000 (40%)
- Cabang B: Rp 600.000 (60%)

Yang Bayar: Cabang A (via reimbursement teknisi)

Hasil:
Cabang B berhutang Rp 600.000 ke Cabang A
```

### Melihat Hutang Cabang

**Di Dashboard:**

```
┌─────────────────────────────────────────┐
│  💰 Rincian Biaya per Cabang            │
│  [Filter: Mei 2026 ▼]                   │
├─────────────────────────────────────────┤
│                                         │
│  Cabang A                               │
│  Total Biaya: Rp 8.500.000              │
│  [📊 Detail] [💸 Hutang Rembush]       │
│                                         │
│  Cabang B                               │
│  Total Biaya: Rp 12.200.000             │
│  [📊 Detail] [💸 Hutang Rembush]       │
│                                         │
└─────────────────────────────────────────┘
```

**Klik "💸 Hutang Rembush":**

```
┌─────────────────────────────────────────┐
│  💸 Hutang Rembush - Cabang B           │
├─────────────────────────────────────────┤
│                                         │
│  Transaksi Pending/Waiting Payment:     │
│                                         │
│  1. RMB-2026-05-0012                    │
│     Hutang: Rp 600.000                  │
│     Ke: Cabang A                        │
│     Status: waiting_payment             │
│     [Lihat Detail] [Lunasi]             │
│                                         │
│  2. RMB-2026-05-0015                    │
│     Hutang: Rp 450.000                  │
│     Ke: Cabang A                        │
│     Status: waiting_payment             │
│     [Lihat Detail] [Lunasi]             │
│                                         │
│  ─────────────────────────────────────  │
│  Total Hutang: Rp 1.050.000             │
│                                         │
└─────────────────────────────────────────┘
```

### Status Transaksi dengan Hutang

```
┌─────────────────────────────────────────┐
│                                         │
│  Status: waiting_payment                │
│                                         │
│  ⚠️ Transaksi ini memiliki hutang       │
│  antar cabang yang belum dilunasi:      │
│                                         │
│  - Cabang B → Cabang A: Rp 600.000      │
│                                         │
│  Status akan otomatis menjadi           │
│  "completed" setelah:                   │
│  1. Invoice diupload ✅                 │
│  2. Hutang dilunasi ❌                  │
│                                         │
└─────────────────────────────────────────┘
```

---

## Pelunasan Hutang

### Cara Melunasi Hutang

**Langkah 1:** Buka detail transaksi yang memiliki hutang

**Langkah 2:** Klik tombol **"Lunasi Hutang"**

```
┌─────────────────────────────────────────┐
│  💸 Pelunasan Hutang Antar Cabang       │
├─────────────────────────────────────────┤
│                                         │
│  Transaksi: RMB-2026-05-0012            │
│  Dari: Cabang B                         │
│  Ke: Cabang A                           │
│  Jumlah Hutang: Rp 600.000              │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
│  Upload Bukti Transfer *                │
│  ┌───────────────────────────────────┐ │
│  │  📷 Klik untuk Upload             │ │
│  │  atau Drag & Drop                 │ │
│  │  Format: JPG, PNG, PDF            │ │
│  │  Maksimal: 5 MB                   │ │
│  └───────────────────────────────────┘ │
│                                         │
│  Catatan Pelunasan (Opsional)           │
│  [Transfer via BCA tanggal 21/05]       │
│                                         │
│  [Batal]  [💾 Simpan Pelunasan]        │
│                                         │
└─────────────────────────────────────────┘
```

**Langkah 3:** Upload bukti transfer

**Langkah 4:** Tambahkan catatan (opsional)

**Langkah 5:** Klik **"💾 Simpan Pelunasan"**

### Hasil Pelunasan

```
✅ Pelunasan Berhasil!

Hutang Cabang B ke Cabang A sebesar Rp 600.000
telah dilunasi.

Status transaksi RMB-2026-05-0012:
waiting_payment → completed ✅

Bukti pelunasan telah disimpan untuk audit.
```

### Tracking Pelunasan

Semua pelunasan tercatat di **Activity Log**:

```
┌─────────────────────────────────────────┐
│  📜 Activity Log                        │
├─────────────────────────────────────────┤
│                                         │
│  21 Mei 2026, 14:30                     │
│  👤 Admin Budi                          │
│  💸 Pelunasan Hutang                    │
│                                         │
│  Cabang B melunasi hutang Rp 600.000    │
│  ke Cabang A untuk transaksi            │
│  RMB-2026-05-0012                       │
│                                         │
│  Bukti: [Lihat File]                    │
│  Catatan: Transfer via BCA              │
│                                         │
└─────────────────────────────────────────┘
```

---

## Tips & Best Practices

### 💡 Tip 1: Nama Cabang yang Jelas

```
✅ BAIK:
- Cabang A - Jakarta Pusat
- Cabang B - Bandung Dago
- Cabang C - Surabaya Gubeng

❌ KURANG BAIK:
- Cabang A
- Cabang B
- Cabang C
```

### 💡 Tip 2: Alokasi yang Akurat

Pastikan alokasi sesuai dengan penggunaan sebenarnya:

```
✅ BAIK:
Kabel untuk instalasi di Cabang B
→ 100% ke Cabang B

❌ KURANG BAIK:
Kabel untuk instalasi di Cabang B
→ Dibagi rata ke semua cabang
```

### 💡 Tip 3: Lunasi Hutang Segera

Jangan biarkan hutang menumpuk:

```
✅ BAIK:
Hutang Rp 600.000 → Lunasi dalam 1-2 hari

❌ KURANG BAIK:
Hutang Rp 600.000 → Dibiarkan berbulan-bulan
```

### 💡 Tip 4: Upload Bukti Transfer

Selalu upload bukti transfer untuk audit:

```
✅ BAIK: Upload screenshot transfer
❌ KURANG BAIK: Tidak upload bukti
```

### 💡 Tip 5: Monitoring Rutin

Cek dashboard hutang cabang secara rutin:

```
✅ BAIK: Cek setiap hari/minggu
❌ KURANG BAIK: Cek hanya saat ada masalah
```

---

## Troubleshooting

### Masalah 1: Tidak Bisa Hapus Cabang

**Penyebab:**
- Cabang masih memiliki transaksi aktif

**Solusi:**
1. Cek transaksi yang terkait dengan cabang tersebut
2. Jika perlu "menonaktifkan", ubah nama menjadi "Cabang X (Nonaktif)"
3. Atau tunggu sampai semua transaksi selesai

### Masalah 2: Total Alokasi Tidak Sesuai

**Penyebab:**
- Salah hitung manual
- Pembulatan desimal

**Solusi:**
1. Gunakan kalkulator untuk memastikan
2. Pastikan total alokasi = total transaksi (sampai rupiah)
3. Jika menggunakan persentase, pastikan total = 100%

### Masalah 3: Hutang Tidak Muncul di Dashboard

**Penyebab:**
- Filter bulan/tahun tidak sesuai
- Transaksi sudah completed

**Solusi:**
1. Ubah filter bulan/tahun
2. Cek status transaksi (hanya pending/waiting_payment yang muncul)
3. Refresh halaman

### Masalah 4: Upload Bukti Pelunasan Gagal

**Penyebab:**
- File terlalu besar (> 5 MB)
- Format tidak didukung

**Solusi:**
1. Kompres file jika > 5 MB
2. Gunakan format JPG, PNG, atau PDF
3. Coba upload ulang

### Masalah 5: Status Tidak Berubah Setelah Pelunasan

**Penyebab:**
- Invoice belum diupload
- Ada hutang lain yang belum dilunasi

**Solusi:**
1. Pastikan invoice sudah diupload
2. Cek apakah ada hutang lain di transaksi yang sama
3. Lunasi semua hutang terlebih dahulu

---

## FAQ

### Q: Berapa banyak cabang yang bisa dibuat?

**A:** Tidak ada batasan jumlah cabang. Anda bisa membuat sebanyak yang diperlukan.

---

### Q: Apakah bisa mengubah alokasi setelah transaksi di-submit?

**A:** Ya, Admin bisa edit alokasi cabang pada transaksi `waiting_payment` (Limited Edit). Atasan dan Owner bisa Full Edit.

---

### Q: Apakah hutang antar cabang otomatis terhitung?

**A:** Ya, sistem otomatis menghitung hutang berdasarkan alokasi cabang dan cabang yang membayar.

---

### Q: Bagaimana jika lupa upload bukti pelunasan?

**A:** Hubungi Admin untuk upload bukti pelunasan. Bukti sangat penting untuk audit.

---

### Q: Apakah bisa melihat riwayat pelunasan?

**A:** Ya, semua pelunasan tercatat di Activity Log dengan detail lengkap.

---

### Q: Apakah transaksi bisa dialokasikan ke 1 cabang saja?

**A:** Ya, Anda bisa alokasikan 100% ke 1 cabang. Tidak ada hutang antar cabang dalam kasus ini.

---

### Q: Bagaimana jika cabang salah input?

**A:** Hubungi Admin/Atasan untuk edit alokasi cabang sebelum transaksi completed.

---

### Q: Apakah Owner bisa melihat hutang semua cabang?

**A:** Ya, Owner bisa melihat hutang semua cabang di dashboard analytics.

---

### Q: Apakah ada laporan hutang per cabang?

**A:** Ya, dashboard menampilkan total hutang per cabang dengan detail transaksi.

---

### Q: Bagaimana jika cabang tidak punya rekening untuk transfer?

**A:** Hubungi Owner untuk menambahkan rekening cabang di menu Rekening Bank.

---

## 📚 Dokumentasi Terkait

- **Sebelumnya:** [Dashboard Analytics](09_DASHBOARD_ANALYTICS.md) - Monitoring biaya per cabang
- **Selanjutnya:** [Rekening Bank](11_REKENING_BANK.md) - Kelola rekening untuk pelunasan
- **Terkait:** [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md) - Alokasi cabang di Rembush
- **Terkait:** [Panduan Pengajuan](04_PENGAJUAN_PEMBELIAN.md) - Alokasi cabang di Pengajuan

---

**Butuh Bantuan?**  
📧 Email: support@whusnet.com  
💬 Telegram: @WhusnetSupport  
📱 WhatsApp: +62 xxx-xxxx-xxxx

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
