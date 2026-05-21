# 🏦 Rekening Bank - WHUSNET Admin Payment

**Untuk Siapa:** Owner (Full Access), Admin & Atasan (Read-Only)  
**Waktu Baca:** ~8 menit  
**Level:** Menengah

---

## 📋 Daftar Isi

- [Apa itu Manajemen Rekening Bank?](#apa-itu-manajemen-rekening-bank)
- [Mengapa Penting?](#mengapa-penting)
- [Hak Akses Rekening](#hak-akses-rekening)
- [Cara Mengelola Rekening](#cara-mengelola-rekening)
- [Jenis Rekening](#jenis-rekening)
- [Penggunaan Rekening](#penggunaan-rekening)
- [Tips & Best Practices](#tips--best-practices)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## Apa itu Manajemen Rekening Bank?

**Manajemen Rekening Bank** adalah fitur untuk mengelola data rekening bank dan e-wallet yang dimiliki oleh setiap cabang untuk keperluan transaksi dan pelunasan hutang.

### Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| **CRUD Rekening** | Tambah, edit, hapus rekening per cabang |
| **Multi-Bank** | Mendukung berbagai bank dan e-wallet |
| **Kontrol Akses** | Owner full-access, Admin/Atasan read-only |
| **Tracking Penggunaan** | Rekening mana yang digunakan untuk transaksi |
| **Audit Trail** | Semua perubahan tercatat lengkap |

### Manfaat

✅ **Transparansi:** Semua rekening cabang tercatat jelas  
✅ **Kemudahan Pelunasan:** Teknisi tahu transfer ke rekening mana  
✅ **Keamanan:** Hanya Owner yang bisa mutasi rekening  
✅ **Audit:** Tracking penggunaan rekening untuk setiap transaksi  

---

## Mengapa Penting?

### Skenario Bisnis

```
Contoh:
Cabang A memiliki 2 rekening:
1. BCA 1234567890 (Operasional)
2. Mandiri 9876543210 (Investasi)

Teknisi dari Cabang B berhutang Rp 600.000
ke Cabang A.

Pertanyaan:
- Transfer ke rekening mana?
- Atas nama siapa?
- Bank apa?

Jawaban: Lihat di Manajemen Rekening Bank!
```

### Alur Penggunaan

```
┌─────────────────────────────────────────┐
│                                         │
│  1. Owner input rekening cabang         │
│     ↓                                   │
│  2. Teknisi lihat rekening tujuan       │
│     ↓                                   │
│  3. Teknisi transfer untuk pelunasan    │
│     ↓                                   │
│  4. Admin upload bukti transfer         │
│     ↓                                   │
│  5. Hutang lunas ✅                     │
│                                         │
└─────────────────────────────────────────┘
```

---

## Hak Akses Rekening

### Kontrol Akses Ketat

| Role | Lihat | Tambah | Edit | Hapus |
|------|:-----:|:------:|:----:|:-----:|
| **Teknisi** | ❌ | ❌ | ❌ | ❌ |
| **Admin** | ✅ | ❌ | ❌ | ❌ |
| **Atasan** | ✅ | ❌ | ❌ | ❌ |
| **Owner** | ✅ | ✅ | ✅ | ✅ |

### Alasan Kontrol Ketat

🔒 **Keamanan:** Data rekening sensitif, hanya Owner yang boleh mutasi  
🔒 **Akuntabilitas:** Perubahan rekening harus melalui Owner  
🔒 **Audit:** Semua perubahan tercatat dengan jelas  

---

## Cara Mengelola Rekening

### Melihat Daftar Rekening (Semua Role)

1. Login sebagai **Admin**, **Atasan**, atau **Owner**
2. Klik menu **"Manajemen"** → **"Rekening Bank"**

```
┌─────────────────────────────────────────┐
│  🏦 Daftar Rekening Bank                │
├─────────────────────────────────────────┤
│                                         │
│  [🔍 Cari rekening...]                  │
│  [Filter: Semua Cabang ▼]               │
│                                         │
│  ┌───────────────────────────────────┐ │
│  │ Cabang A - Jakarta                │ │
│  │                                   │ │
│  │ 🏦 BCA - 1234567890               │ │
│  │    Atas Nama: PT WHUSNET          │ │
│  │    [✏️] [🗑️]                      │ │
│  │                                   │ │
│  │ 🏦 Mandiri - 9876543210           │ │
│  │    Atas Nama: PT WHUSNET          │ │
│  │    [✏️] [🗑️]                      │ │
│  └───────────────────────────────────┘ │
│                                         │
│  ┌───────────────────────────────────┐ │
│  │ Cabang B - Bandung                │ │
│  │                                   │ │
│  │ 🏦 BRI - 5555666677778888         │ │
│  │    Atas Nama: PT WHUSNET          │ │
│  │    [✏️] [🗑️]                      │ │
│  └───────────────────────────────────┘ │
│                                         │
│  Total: 3 rekening                      │
│                                         │
│  [➕ Tambah Rekening] (Owner only)     │
│                                         │
└─────────────────────────────────────────┘
```

**Catatan:**
- Admin & Atasan: **Read-Only** (hanya lihat)
- Owner: **Full Access** (lihat, tambah, edit, hapus)

### Menambah Rekening Baru (Owner Only)

**Langkah 1:** Klik tombol **"➕ Tambah Rekening"**

**Langkah 2:** Isi form

```
┌─────────────────────────────────────────┐
│  ➕ Tambah Rekening Baru                │
├─────────────────────────────────────────┤
│                                         │
│  Cabang *                               │
│  [▼ Pilih Cabang]                      │
│  - Cabang A - Jakarta                   │
│  - Cabang B - Bandung                   │
│  - Cabang C - Surabaya                  │
│                                         │
│  Jenis Rekening *                       │
│  ○ Bank                                 │
│  ○ E-Wallet                             │
│                                         │
│  Nama Bank/E-Wallet *                   │
│  [▼ Pilih Bank]                        │
│  - BCA                                  │
│  - Mandiri                              │
│  - BRI                                  │
│  - BNI                                  │
│  - GoPay                                │
│  - OVO                                  │
│  - Dana                                 │
│  - Lainnya...                           │
│                                         │
│  Nomor Rekening *                       │
│  [1234567890__________________]         │
│                                         │
│  Atas Nama *                            │
│  [PT WHUSNET__________________]         │
│                                         │
│  Catatan (Opsional)                     │
│  [Rekening operasional________]         │
│                                         │
│  [Batal]  [💾 Simpan]                  │
│                                         │
└─────────────────────────────────────────┘
```

**Field yang Wajib Diisi:**
- ✅ Cabang
- ✅ Jenis Rekening (Bank/E-Wallet)
- ✅ Nama Bank/E-Wallet
- ✅ Nomor Rekening
- ✅ Atas Nama

**Langkah 3:** Klik **"💾 Simpan"**

**Hasil:**
```
✅ Rekening berhasil ditambahkan!

BCA - 1234567890
Atas Nama: PT WHUSNET
Cabang: Cabang A - Jakarta

Rekening ini sekarang tersedia untuk transaksi.
```

### Mengedit Rekening (Owner Only)

**Langkah 1:** Klik icon **✏️** pada rekening yang ingin diedit

**Langkah 2:** Ubah data

```
┌─────────────────────────────────────────┐
│  ✏️ Edit Rekening                       │
├─────────────────────────────────────────┤
│                                         │
│  Cabang *                               │
│  [Cabang A - Jakarta (tidak bisa ubah)] │
│                                         │
│  Jenis Rekening *                       │
│  ● Bank                                 │
│  ○ E-Wallet                             │
│                                         │
│  Nama Bank *                            │
│  [BCA_________________________]         │
│                                         │
│  Nomor Rekening *                       │
│  [1234567890__________________]         │
│                                         │
│  Atas Nama *                            │
│  [PT WHUSNET Cabang Jakarta___]         │
│                                         │
│  Catatan                                │
│  [Rekening operasional utama__]         │
│                                         │
│  [Batal]  [💾 Update]                  │
│                                         │
└─────────────────────────────────────────┘
```

**Catatan:**
- Cabang **tidak bisa diubah** setelah rekening dibuat
- Jika perlu ubah cabang, hapus dan buat rekening baru

**Langkah 3:** Klik **"💾 Update"**

### Menghapus Rekening (Owner Only)

**Langkah 1:** Klik icon **🗑️** pada rekening yang ingin dihapus

**Langkah 2:** Konfirmasi

```
┌─────────────────────────────────────────┐
│  ⚠️ Konfirmasi Hapus                    │
├─────────────────────────────────────────┤
│                                         │
│  Apakah Anda yakin ingin menghapus      │
│  rekening ini?                          │
│                                         │
│  Bank: BCA                              │
│  Nomor: 1234567890                      │
│  Atas Nama: PT WHUSNET                  │
│  Cabang: Cabang A - Jakarta             │
│                                         │
│  ⚠️ Rekening yang masih digunakan       │
│  dalam transaksi aktif TIDAK DAPAT      │
│  dihapus!                               │
│                                         │
│  [Batal]  [🗑️ Ya, Hapus]              │
│                                         │
└─────────────────────────────────────────┘
```

**Catatan Penting:**
- ❌ Rekening dengan transaksi aktif **tidak bisa dihapus**
- ✅ Hanya rekening tanpa transaksi yang bisa dihapus

---

## Jenis Rekening

### 1. Bank

Rekening bank konvensional untuk transfer antar bank.

**Bank yang Didukung:**
- BCA (Bank Central Asia)
- Mandiri
- BRI (Bank Rakyat Indonesia)
- BNI (Bank Negara Indonesia)
- CIMB Niaga
- Permata Bank
- Danamon
- BTN (Bank Tabungan Negara)
- Dan bank lainnya...

**Format Nomor:**
- Biasanya 10-16 digit
- Contoh: 1234567890

### 2. E-Wallet

Dompet digital untuk transfer instan.

**E-Wallet yang Didukung:**
- GoPay
- OVO
- Dana
- ShopeePay
- LinkAja
- Dan e-wallet lainnya...

**Format Nomor:**
- Biasanya nomor HP (10-13 digit)
- Contoh: 081234567890

---

## Penggunaan Rekening

### Untuk Pelunasan Hutang

Ketika teknisi perlu melunasi hutang antar cabang:

```
┌─────────────────────────────────────────┐
│  💸 Pelunasan Hutang                    │
├─────────────────────────────────────────┤
│                                         │
│  Dari: Cabang B                         │
│  Ke: Cabang A                           │
│  Jumlah: Rp 600.000                     │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
│  📋 Rekening Tujuan:                    │
│                                         │
│  Bank: BCA                              │
│  Nomor: 1234567890                      │
│  Atas Nama: PT WHUSNET                  │
│                                         │
│  Silakan transfer ke rekening di atas   │
│  dan upload bukti transfer.             │
│                                         │
└─────────────────────────────────────────┘
```

### Untuk Pembayaran Transaksi

Ketika Admin upload bukti pembayaran:

```
┌─────────────────────────────────────────┐
│  💸 Upload Bukti Pembayaran             │
├─────────────────────────────────────────┤
│                                         │
│  Metode Pembayaran *                    │
│  ● Transfer Bank                        │
│  ○ Cash                                 │
│                                         │
│  Rekening Sumber *                      │
│  [▼ Pilih Rekening]                    │
│  - BCA 1234567890 (Cabang A)            │
│  - Mandiri 9876543210 (Cabang A)        │
│  - BRI 5555666677778888 (Cabang B)      │
│                                         │
│  Upload Bukti Transfer *                │
│  [📷 Klik untuk Upload]                │
│                                         │
└─────────────────────────────────────────┘
```

### Tracking Penggunaan

Setiap transaksi mencatat rekening yang digunakan:

```
┌─────────────────────────────────────────┐
│  📋 Detail Transaksi                    │
├─────────────────────────────────────────┤
│                                         │
│  Invoice: RMB-2026-05-0012              │
│  Status: completed                      │
│                                         │
│  Pembayaran:                            │
│  Metode: Transfer Bank                  │
│  Dari Rekening: BCA 1234567890          │
│  Cabang: Cabang A - Jakarta             │
│  Tanggal: 21 Mei 2026, 14:30            │
│                                         │
│  [Lihat Bukti Transfer]                 │
│                                         │
└─────────────────────────────────────────┘
```

---

## Tips & Best Practices

### 💡 Tip 1: Nama Rekening yang Jelas

```
✅ BAIK:
Atas Nama: PT WHUSNET Cabang Jakarta
Catatan: Rekening operasional utama

❌ KURANG BAIK:
Atas Nama: PT WHUSNET
Catatan: (kosong)
```

### 💡 Tip 2: Verifikasi Nomor Rekening

Pastikan nomor rekening benar sebelum disimpan:

```
✅ BAIK:
1. Cek nomor rekening di buku tabungan
2. Verifikasi dengan pihak bank
3. Test transfer kecil (Rp 10.000)

❌ KURANG BAIK:
Input nomor tanpa verifikasi
```

### 💡 Tip 3: Update Rekening yang Berubah

Jika rekening berubah, segera update:

```
✅ BAIK:
Rekening lama ditutup → Update segera

❌ KURANG BAIK:
Rekening lama ditutup → Dibiarkan
```

### 💡 Tip 4: Minimal 1 Rekening per Cabang

Setiap cabang harus punya minimal 1 rekening:

```
✅ BAIK:
Cabang A: 2 rekening (BCA, Mandiri)
Cabang B: 1 rekening (BRI)
Cabang C: 1 rekening (BNI)

❌ KURANG BAIK:
Cabang A: 2 rekening
Cabang B: 0 rekening ← Tidak bisa terima transfer!
```

### 💡 Tip 5: Catatan yang Informatif

Tambahkan catatan untuk memudahkan identifikasi:

```
✅ BAIK:
"Rekening operasional utama untuk transaksi harian"

❌ KURANG BAIK:
(kosong)
```

---

## Troubleshooting

### Masalah 1: Tombol "Tambah Rekening" Tidak Muncul

**Penyebab:**
- Role Anda bukan Owner

**Solusi:**
- Hanya Owner yang bisa menambah rekening
- Jika Anda Admin/Atasan, hubungi Owner
- Jika perlu akses, minta Owner untuk upgrade role

### Masalah 2: Tidak Bisa Hapus Rekening

**Penyebab:**
- Rekening masih digunakan dalam transaksi aktif

**Solusi:**
1. Cek transaksi yang menggunakan rekening tersebut
2. Tunggu sampai transaksi selesai
3. Atau edit rekening (ubah catatan menjadi "Nonaktif")

### Masalah 3: Rekening Tidak Muncul di Dropdown

**Penyebab:**
- Rekening belum ditambahkan untuk cabang tersebut
- Filter cabang tidak sesuai

**Solusi:**
1. Pastikan rekening sudah ditambahkan untuk cabang yang benar
2. Hubungi Owner untuk menambahkan rekening
3. Cek filter cabang di halaman rekening

### Masalah 4: Salah Input Nomor Rekening

**Penyebab:**
- Typo saat input
- Copy-paste dengan spasi

**Solusi:**
1. Owner bisa edit nomor rekening
2. Verifikasi ulang dengan buku tabungan
3. Test transfer kecil untuk memastikan

### Masalah 5: Teknisi Tidak Tahu Transfer ke Mana

**Penyebab:**
- Rekening belum ditambahkan
- Teknisi tidak bisa akses menu rekening

**Solusi:**
1. Admin/Atasan bisa lihat rekening dan informasikan ke Teknisi
2. Atau Owner tambahkan rekening di sistem
3. Rekening tujuan akan muncul otomatis di halaman pelunasan

---

## FAQ

### Q: Berapa banyak rekening yang bisa ditambahkan per cabang?

**A:** Tidak ada batasan. Setiap cabang bisa memiliki beberapa rekening (bank dan e-wallet).

---

### Q: Apakah Admin bisa menambah rekening?

**A:** Tidak. Hanya Owner yang bisa menambah, edit, atau hapus rekening. Admin dan Atasan hanya bisa melihat (read-only).

---

### Q: Apakah bisa menggunakan rekening pribadi?

**A:** Sebaiknya tidak. Gunakan rekening perusahaan (atas nama PT WHUSNET) untuk transparansi dan audit.

---

### Q: Apakah e-wallet didukung?

**A:** Ya, sistem mendukung e-wallet seperti GoPay, OVO, Dana, dll.

---

### Q: Bagaimana jika rekening berubah?

**A:** Hubungi Owner untuk edit rekening. Owner bisa update nomor rekening, nama bank, atau atas nama.

---

### Q: Apakah rekening bisa dipindah ke cabang lain?

**A:** Tidak. Cabang tidak bisa diubah setelah rekening dibuat. Jika perlu, hapus rekening lama dan buat baru untuk cabang yang benar.

---

### Q: Apakah Teknisi bisa melihat rekening?

**A:** Tidak langsung. Tapi rekening tujuan akan muncul otomatis saat Teknisi perlu melunasi hutang.

---

### Q: Apakah ada audit trail untuk perubahan rekening?

**A:** Ya, semua perubahan rekening tercatat di Activity Log dengan detail lengkap.

---

### Q: Bagaimana jika lupa nomor rekening?

**A:** Admin/Atasan/Owner bisa cek di menu Rekening Bank. Atau hubungi Owner untuk informasi.

---

### Q: Apakah bisa menambahkan bank yang tidak ada di list?

**A:** Ya, pilih "Lainnya" dan ketik nama bank secara manual.

---

## 📚 Dokumentasi Terkait

- **Sebelumnya:** [Manajemen Cabang](10_MANAJEMEN_CABANG.md) - Kelola cabang dan alokasi
- **Selanjutnya:** [Kategori Transaksi](12_KATEGORI_TRANSAKSI.md) - Kelola kategori
- **Terkait:** [Panduan Pembayaran](07_PEMBAYARAN.md) - Upload bukti bayar
- **Terkait:** [Activity Log](14_ACTIVITY_LOG.md) - Audit trail rekening

---

**Butuh Bantuan?**  
📧 Email: support@whusnet.com  
💬 Telegram: @WhusnetSupport  
📱 WhatsApp: +62 xxx-xxxx-xxxx

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
