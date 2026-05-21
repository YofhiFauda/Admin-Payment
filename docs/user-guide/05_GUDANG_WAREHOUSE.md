# 🏪 Gudang (Warehouse) - WHUSNET Admin Payment

**Untuk Siapa:** Admin, Atasan, Owner  
**Waktu Baca:** ~8 menit  
**Level:** Menengah

---

## 📋 Daftar Isi

- [Apa itu Modul Gudang?](#apa-itu-modul-gudang)
- [Kapan Menggunakan Gudang?](#kapan-menggunakan-gudang)
- [Perbedaan Gudang vs Rembush vs Pengajuan](#perbedaan-gudang-vs-rembush-vs-pengajuan)
- [Cara Mencatat Belanja Gudang](#cara-mencatat-belanja-gudang)
- [Alur Approval Gudang](#alur-approval-gudang)
- [Tips & Best Practices](#tips--best-practices)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## Apa itu Modul Gudang?

**Modul Gudang** adalah fitur khusus untuk mencatat **belanja gudang internal** yang dilakukan oleh staff Admin atau Owner.

### Karakteristik Gudang

| Aspek | Deskripsi |
|-------|-----------|
| **Tipe Transaksi** | Internal (bukan reimbursement) |
| **OCR** | ❌ Tidak menggunakan OCR AI |
| **Telegram** | ❌ Tidak ada notifikasi Telegram |
| **Approval** | ✅ Perlu approval Management |
| **Pembayaran** | ✅ Upload bukti bayar (tanpa verifikasi AI) |
| **Status Final** | `completed` (langsung setelah upload invoice) |

### Manfaat Modul Gudang

✅ **Alur Cepat:** Tanpa OCR, langsung input manual  
✅ **Tanpa Telegram:** Tidak perlu konfirmasi Teknisi  
✅ **Audit Trail:** Tetap tercatat di sistem untuk transparansi  
✅ **Alokasi Cabang:** Mendukung distribusi biaya ke beberapa cabang  

---

## Kapan Menggunakan Gudang?

### ✅ Gunakan Gudang Jika:

1. **Belanja Gudang Internal**
   - Pembelian stok barang untuk gudang
   - Pembelian alat-alat operasional
   - Pembelian perlengkapan kantor

2. **Transaksi Langsung (Bukan Reimbursement)**
   - Dibayar langsung oleh perusahaan
   - Bukan uang pribadi yang direimbursement

3. **Input oleh Staff Internal**
   - Admin atau Owner yang mencatat
   - Bukan Teknisi lapangan

### ❌ Jangan Gunakan Gudang Jika:

1. **Reimbursement Teknisi** → Gunakan [Rembush](03_REMBUSH_REIMBURSEMENT.md)
2. **Pengajuan Sebelum Beli** → Gunakan [Pengajuan](04_PENGAJUAN_PEMBELIAN.md)
3. **Transaksi Pribadi** → Gunakan Rembush

---

## Perbedaan Gudang vs Rembush vs Pengajuan

| Aspek | Rembush | Pengajuan | Gudang |
|-------|---------|-----------|--------|
| **Tujuan** | Reimbursement | Approval sebelum beli | Pencatatan gudang |
| **Timing** | Setelah beli | Sebelum beli | Setelah beli |
| **OCR AI** | ✅ Ya | ❌ Tidak | ❌ Tidak |
| **Telegram** | ✅ Ya | ✅ Ya | ❌ Tidak |
| **Input By** | Teknisi | Teknisi | Admin/Owner |
| **Approval** | Admin/Owner | Admin/Owner | Admin/Owner |
| **Payment Verification** | ✅ AI | ✅ AI | ❌ Manual |
| **Status Final** | `completed` | `completed` | `completed` |

### Contoh Kasus

#### Kasus 1: Belanja Kabel untuk Stok Gudang

```
Skenario: Admin membeli 100 meter kabel LAN untuk stok gudang

✅ Gunakan: Gudang
Alasan: Belanja internal, bukan reimbursement
```

#### Kasus 2: Teknisi Beli Kabel untuk Instalasi

```
Skenario: Teknisi beli kabel pakai uang sendiri untuk instalasi client

✅ Gunakan: Rembush
Alasan: Reimbursement teknisi
```

#### Kasus 3: Perlu Beli Router Baru

```
Skenario: Teknisi perlu router baru, belum beli

✅ Gunakan: Pengajuan
Alasan: Approval sebelum beli
```

---

## Cara Mencatat Belanja Gudang

### Langkah 1: Buka Form Gudang

1. Login sebagai **Admin** atau **Owner**
2. Klik menu **"Transaksi"** di sidebar
3. Klik tombol **"➕ Buat Gudang"**

```
┌─────────────────────────────────────────┐
│  📋 Transaksi                           │
│  ┌───────────────────────────────────┐ │
│  │ [➕ Buat Rembush]                 │ │
│  │ [➕ Buat Pengajuan]               │ │
│  │ [➕ Buat Gudang]  ← Klik ini      │ │
│  └───────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

### Langkah 2: Isi Informasi Dasar

```
┌─────────────────────────────────────────┐
│  🏪 Form Belanja Gudang                 │
├─────────────────────────────────────────┤
│                                         │
│  Nama Vendor *                          │
│  [_____________________________]        │
│                                         │
│  Tanggal Belanja *                      │
│  [📅 21/05/2026]                        │
│                                         │
│  Kategori *                             │
│  [▼ Pilih Kategori]                    │
│  - Alat Kerja                           │
│  - Perlengkapan Kantor                  │
│  - Stok Barang                          │
│                                         │
│  Deskripsi                              │
│  [_____________________________]        │
│  [_____________________________]        │
│                                         │
└─────────────────────────────────────────┘
```

**Field yang Wajib Diisi:**
- ✅ Nama Vendor
- ✅ Tanggal Belanja
- ✅ Kategori

**Field Opsional:**
- Deskripsi (untuk catatan tambahan)

### Langkah 3: Input Item Belanja

```
┌─────────────────────────────────────────┐
│  📦 Daftar Item                         │
├─────────────────────────────────────────┤
│                                         │
│  Item 1:                                │
│  Nama Item *                            │
│  [Kabel LAN Cat6 100m___________]       │
│                                         │
│  Harga Satuan *                         │
│  [Rp 500.000___________________]        │
│                                         │
│  Jumlah *                               │
│  [2_________________________]           │
│                                         │
│  Subtotal: Rp 1.000.000                 │
│                                         │
│  [➕ Tambah Item]  [🗑️ Hapus]          │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
│  DPP (Dasar Pengenaan Pajak)            │
│  [Rp 1.000.000__________________]       │
│                                         │
│  PPN 11%                                │
│  [Rp 110.000____________________]       │
│                                         │
│  ═══════════════════════════════════    │
│  TOTAL: Rp 1.110.000                    │
│  ═══════════════════════════════════    │
│                                         │
└─────────────────────────────────────────┘
```

**Tips Input Item:**
- Klik **"➕ Tambah Item"** untuk menambah item baru
- Klik **"🗑️ Hapus"** untuk menghapus item
- Subtotal dihitung otomatis: `Harga Satuan × Jumlah`
- Total dihitung otomatis: `DPP + PPN`

### Langkah 4: Alokasi Cabang

```
┌─────────────────────────────────────────┐
│  🏢 Alokasi Cabang                      │
├─────────────────────────────────────────┤
│                                         │
│  Metode Distribusi *                    │
│  ○ Equal (Rata)                         │
│  ○ Percentage (Persentase)              │
│  ● Manual (Nominal)  ← Dipilih          │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
│  Cabang A                               │
│  [Rp 500.000____________________]       │
│  45.05%                                 │
│                                         │
│  Cabang B                               │
│  [Rp 400.000____________________]       │
│  36.04%                                 │
│                                         │
│  Cabang C                               │
│  [Rp 210.000____________________]       │
│  18.92%                                 │
│                                         │
│  ═══════════════════════════════════    │
│  Total Alokasi: Rp 1.110.000 ✅         │
│  ═══════════════════════════════════    │
│                                         │
└─────────────────────────────────────────┘
```

**Metode Distribusi:**

1. **Equal (Rata):** Dibagi rata ke semua cabang
2. **Percentage (Persentase):** Input persentase per cabang
3. **Manual (Nominal):** Input nominal per cabang

**Validasi:**
- Total alokasi harus sama dengan total transaksi
- Jika tidak sama, akan muncul error: ❌ "Total alokasi tidak sesuai"

### Langkah 5: Upload Foto Nota (Opsional)

```
┌─────────────────────────────────────────┐
│  📸 Upload Foto Nota (Opsional)         │
├─────────────────────────────────────────┤
│                                         │
│  ┌───────────────────────────────────┐ │
│  │                                   │ │
│  │     📷 Klik untuk Upload          │ │
│  │                                   │ │
│  │  atau Drag & Drop di sini         │ │
│  │                                   │ │
│  │  Format: JPG, PNG, PDF            │ │
│  │  Maksimal: 5 MB                   │ │
│  │                                   │ │
│  └───────────────────────────────────┘ │
│                                         │
└─────────────────────────────────────────┘
```

**Catatan:**
- Upload foto nota **tidak wajib** untuk Gudang
- Foto hanya untuk dokumentasi, tidak diproses OCR
- Jika ada, akan memudahkan audit di kemudian hari

### Langkah 6: Review & Submit

```
┌─────────────────────────────────────────┐
│  📋 Ringkasan Transaksi                 │
├─────────────────────────────────────────┤
│                                         │
│  Vendor: Toko Elektronik ABC            │
│  Tanggal: 21 Mei 2026                   │
│  Kategori: Stok Barang                  │
│                                         │
│  Item:                                  │
│  - Kabel LAN Cat6 100m (2x) Rp 1.000.000│
│                                         │
│  DPP: Rp 1.000.000                      │
│  PPN: Rp 110.000                        │
│  Total: Rp 1.110.000                    │
│                                         │
│  Alokasi:                               │
│  - Cabang A: Rp 500.000 (45.05%)        │
│  - Cabang B: Rp 400.000 (36.04%)        │
│  - Cabang C: Rp 210.000 (18.92%)        │
│                                         │
│  [Batal]  [✅ Submit Transaksi]        │
│                                         │
└─────────────────────────────────────────┘
```

Klik **"✅ Submit Transaksi"** untuk menyimpan.

### Langkah 7: Konfirmasi Berhasil

```
┌─────────────────────────────────────────┐
│                                         │
│         ✅ Transaksi Berhasil!          │
│                                         │
│  Invoice: GDG-2026-05-0001              │
│  Status: pending                        │
│                                         │
│  Transaksi Anda telah disimpan dan      │
│  menunggu approval dari Management.     │
│                                         │
│  [Lihat Detail]  [Buat Lagi]           │
│                                         │
└─────────────────────────────────────────┘
```

---

## Alur Approval Gudang

### Status Lifecycle

```
┌─────────────────────────────────────────┐
│                                         │
│  1. pending                             │
│     ↓                                   │
│     Admin/Owner input transaksi         │
│     ↓                                   │
│  2. pending (menunggu approval)         │
│     ↓                                   │
│     Management approve                  │
│     ↓                                   │
│  3. waiting_payment                     │
│     ↓                                   │
│     Upload bukti bayar                  │
│     ↓                                   │
│  4. completed ✅                        │
│                                         │
└─────────────────────────────────────────┘
```

### Perbedaan dengan Rembush

| Aspek | Rembush | Gudang |
|-------|---------|--------|
| **Setelah Upload Invoice** | Perlu verifikasi AI | Langsung `completed` |
| **Konfirmasi Telegram** | ✅ Perlu (jika cash) | ❌ Tidak perlu |
| **Payment Verification** | ✅ AI check nominal | ❌ Manual |
| **Status Flagged** | ✅ Bisa terjadi | ❌ Tidak ada |

### Contoh Alur

```
Senin, 09:00
Admin input transaksi Gudang
Status: pending

Senin, 10:00
Atasan review & approve
Status: waiting_payment

Senin, 14:00
Admin upload bukti transfer
Status: completed ✅

Tidak ada konfirmasi Telegram
Tidak ada verifikasi AI
Langsung selesai!
```

---

## Tips & Best Practices

### 💡 Tip 1: Lengkapi Deskripsi

Meskipun opsional, deskripsi membantu untuk audit:

```
✅ BAIK:
"Pembelian kabel untuk stok gudang bulan Mei 2026"

❌ KURANG BAIK:
(kosong)
```

### 💡 Tip 2: Upload Foto Nota

Meskipun tidak wajib, upload foto nota untuk dokumentasi:

```
✅ BAIK: Upload foto nota asli
❌ KURANG BAIK: Tidak upload foto
```

### 💡 Tip 3: Alokasi Cabang yang Tepat

Pastikan alokasi cabang sesuai dengan penggunaan barang:

```
✅ BAIK:
Kabel untuk Cabang A → 100% ke Cabang A

❌ KURANG BAIK:
Kabel untuk Cabang A → Dibagi rata ke semua cabang
```

### 💡 Tip 4: Cek Total Sebelum Submit

Pastikan total alokasi sama dengan total transaksi:

```
Total Transaksi: Rp 1.110.000
Total Alokasi: Rp 1.110.000 ✅

Jika tidak sama, akan error!
```

### 💡 Tip 5: Gunakan Kategori yang Tepat

Pilih kategori yang sesuai untuk memudahkan laporan:

```
✅ BAIK:
Kabel → Kategori: Stok Barang

❌ KURANG BAIK:
Kabel → Kategori: Perlengkapan Kantor
```

---

## Troubleshooting

### Masalah 1: Tombol "Buat Gudang" Tidak Muncul

**Penyebab:**
- Role Anda bukan Admin atau Owner

**Solusi:**
- Hanya Admin dan Owner yang bisa membuat transaksi Gudang
- Jika Anda Teknisi, gunakan Rembush atau Pengajuan
- Hubungi Admin jika perlu akses

### Masalah 2: Total Alokasi Tidak Sesuai

**Penyebab:**
- Total alokasi cabang tidak sama dengan total transaksi

**Solusi:**
1. Cek total transaksi: `DPP + PPN`
2. Cek total alokasi: `Cabang A + Cabang B + ...`
3. Pastikan keduanya sama persis
4. Gunakan kalkulator jika perlu

### Masalah 3: Upload Foto Gagal

**Penyebab:**
- File terlalu besar (> 5 MB)
- Format tidak didukung

**Solusi:**
1. Kompres foto jika > 5 MB
2. Gunakan format JPG, PNG, atau PDF
3. Coba upload ulang

### Masalah 4: Kategori Tidak Muncul

**Penyebab:**
- Kategori dinonaktifkan oleh Admin

**Solusi:**
- Hubungi Admin untuk mengaktifkan kategori
- Atau gunakan kategori lain yang aktif

### Masalah 5: Tidak Bisa Edit Setelah Submit

**Penyebab:**
- Transaksi sudah di-submit, tidak bisa diedit

**Solusi:**
- Hubungi Admin/Atasan untuk edit
- Atau buat transaksi baru jika perlu

---

## FAQ

### Q: Apakah Teknisi bisa membuat transaksi Gudang?

**A:** Tidak. Hanya Admin dan Owner yang bisa membuat transaksi Gudang. Teknisi gunakan Rembush atau Pengajuan.

---

### Q: Apakah Gudang perlu approval?

**A:** Ya, Gudang tetap perlu approval dari Management (Admin/Atasan/Owner) sebelum masuk ke `waiting_payment`.

---

### Q: Apakah Gudang menggunakan OCR?

**A:** Tidak. Gudang tidak menggunakan OCR AI. Semua data diinput manual.

---

### Q: Apakah Gudang perlu konfirmasi Telegram?

**A:** Tidak. Gudang tidak memerlukan konfirmasi Telegram karena bukan reimbursement.

---

### Q: Apakah Gudang bisa di-flag AI?

**A:** Tidak. Gudang tidak menggunakan payment verification AI, jadi tidak ada status `flagged`.

---

### Q: Kapan status Gudang menjadi completed?

**A:** Langsung setelah upload bukti bayar (invoice). Tidak perlu konfirmasi Telegram atau verifikasi AI.

---

### Q: Apakah Gudang mendukung alokasi cabang?

**A:** Ya, Gudang mendukung alokasi cabang dengan 3 metode: Equal, Percentage, dan Manual.

---

### Q: Apakah wajib upload foto nota untuk Gudang?

**A:** Tidak wajib, tapi sangat direkomendasikan untuk dokumentasi dan audit.

---

### Q: Apakah Gudang bisa diedit setelah completed?

**A:** Tidak. Edit Protection berlaku untuk semua transaksi `completed`, termasuk Gudang.

---

### Q: Apa bedanya Gudang dengan Rembush?

**A:** 
- **Gudang:** Belanja internal, tanpa OCR, tanpa Telegram, langsung completed
- **Rembush:** Reimbursement, pakai OCR, pakai Telegram, ada verifikasi AI

---

## 📚 Dokumentasi Terkait

- **Sebelumnya:** [Panduan Pengajuan](04_PENGAJUAN_PEMBELIAN.md) - Cara membuat pengajuan pembelian
- **Selanjutnya:** [Panduan Approval](06_APPROVAL_TRANSAKSI.md) - Cara menyetujui transaksi
- **Terkait:** [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md) - Perbedaan dengan Rembush
- **Troubleshooting:** [Panduan Troubleshooting](16_TROUBLESHOOTING_USER.md) - Solusi masalah umum

---

**Butuh Bantuan?**  
📧 Email: support@whusnet.com  
💬 Telegram: @WhusnetSupport  
📱 WhatsApp: +62 xxx-xxxx-xxxx

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
