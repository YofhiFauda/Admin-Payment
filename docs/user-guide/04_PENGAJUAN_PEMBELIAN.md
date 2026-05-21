# 📝 Panduan Pengajuan Pembelian

**Untuk Siapa:** Teknisi, Admin, Atasan, Owner  
**Waktu Baca:** 12 menit  
**Level:** Pemula - Menengah

---

## 📋 Daftar Isi
- [Apa itu Pengajuan Pembelian?](#apa-itu-pengajuan-pembelian)
- [Kapan Menggunakan Pengajuan?](#kapan-menggunakan-pengajuan)
- [Cara Membuat Pengajuan](#cara-membuat-pengajuan)
- [Dual-Version System](#dual-version-system)
- [Alokasi Cabang](#alokasi-cabang)
- [Memahami Status Pengajuan](#memahami-status-pengajuan)
- [Setelah Disetujui](#setelah-disetujui)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## Apa itu Pengajuan Pembelian?

**Pengajuan Pembelian** adalah sistem untuk **mengajukan pembelian barang/jasa SEBELUM dibeli**. Berbeda dengan Rembush yang untuk penggantian biaya yang sudah dikeluarkan, Pengajuan adalah untuk **approval dulu, beli kemudian**.

### Analogi Sederhana 🎯

**Rembush:**
```
Beli Dulu → Bayar Sendiri → Upload Nota → Dapat Penggantian
```

**Pengajuan:**
```
Ajukan Dulu → Tunggu Approval → Beli Barang → Upload Invoice → Dapat Penggantian
```

---

### Contoh Kasus 📝

**Skenario 1: Pembelian Router untuk Kantor**
- Anda perlu beli router Rp 5.000.000
- Nominal besar, tidak bisa bayar sendiri dulu
- **Solusi:** Buat Pengajuan → Tunggu approval → Beli setelah disetujui

**Skenario 2: Stok Kabel Bulanan**
- Setiap bulan perlu stok kabel untuk instalasi
- Bisa direncanakan, tidak urgent
- **Solusi:** Buat Pengajuan → Tunggu approval → Beli sesuai approval

---

## Kapan Menggunakan Pengajuan?

### ✅ Gunakan Pengajuan Untuk:

1. **Pembelian Besar** (nominal tinggi)
   - Contoh: Router Rp 5 juta, Server Rp 10 juta
   - Tidak realistis bayar sendiri dulu

2. **Pembelian Terencana** (bisa dijadwalkan)
   - Contoh: Stok bulanan, upgrade peralatan
   - Ada waktu untuk approval

3. **Pembelian yang Perlu Persetujuan Management**
   - Contoh: Investasi baru, perubahan vendor
   - Perlu diskusi & approval dulu

4. **Pembelian dengan Spesifikasi Khusus**
   - Contoh: Peralatan custom, barang import
   - Management perlu review spesifikasi

---

### ❌ JANGAN Gunakan Pengajuan Untuk:

1. **Pembelian Mendadak/Urgent**
   - ❌ Kabel putus saat instalasi → Gunakan **Rembush**!
   - Tidak ada waktu tunggu approval

2. **Pembelian Kecil yang Sudah Dibeli**
   - ❌ Sudah beli konektor Rp 50.000 → Gunakan **Rembush**!
   - Pengajuan untuk SEBELUM beli

3. **Biaya Operasional Harian**
   - ❌ Bensin, tol, parkir → Gunakan **Rembush**!
   - Tidak praktis untuk hal kecil

---

## Cara Membuat Pengajuan

### 📝 Langkah 1: Buka Form Pengajuan

1. **Login** ke sistem
2. Klik tombol **"Buat Transaksi"** (tombol biru di kanan atas)
3. Pilih **"Pengajuan Pembelian"**
4. Form pengajuan akan terbuka

---

### ✍️ Langkah 2: Isi Data Vendor

**Form Vendor:**
```
┌─────────────────────────────────────┐
│ INFORMASI VENDOR                    │
│                                     │
│ Nama Vendor/Toko *                  │
│ [_____________________________]     │
│                                     │
│ Kontak Vendor (Opsional)            │
│ [_____________________________]     │
│                                     │
│ Alamat Vendor (Opsional)            │
│ [_____________________________]     │
│ [_____________________________]     │
└─────────────────────────────────────┘
```

**Tips Pengisian:**
- ✅ **Nama Vendor:** Tulis nama lengkap toko/supplier
  - Contoh: "Toko Elektronik Jaya", "PT. Maju Bersama"
- ✅ **Kontak:** Nomor HP/WA vendor (untuk koordinasi)
- ✅ **Alamat:** Alamat lengkap (jika perlu kunjungan)

---

### 📦 Langkah 3: Isi Detail Barang/Jasa

**Form Item:**
```
┌─────────────────────────────────────┐
│ DETAIL BARANG/JASA                  │
│                                     │
│ Nama Barang/Jasa *                  │
│ [_____________________________]     │
│                                     │
│ Spesifikasi *                       │
│ [_____________________________]     │
│ [_____________________________]     │
│ [_____________________________]     │
│                                     │
│ Jumlah/Quantity *                   │
│ [_____________________________]     │
│                                     │
│ Satuan *                            │
│ [Unit ▼]                            │
│                                     │
│ Estimasi Harga per Unit *           │
│ Rp [_____________________________]  │
│                                     │
│ Total Estimasi                      │
│ Rp 0 (Auto-calculated)              │
└─────────────────────────────────────┘
```

**Contoh Pengisian:**

**Kasus: Beli Router**
- **Nama Barang:** Router Wireless
- **Spesifikasi:** 
  ```
  - Brand: TP-Link Archer AX50
  - Dual Band (2.4GHz + 5GHz)
  - WiFi 6 (802.11ax)
  - 4 Port Gigabit LAN
  - Garansi resmi 3 tahun
  ```
- **Jumlah:** 2
- **Satuan:** Unit
- **Estimasi Harga:** Rp 2.500.000
- **Total:** Rp 5.000.000 (auto-calculated)

**Tips Spesifikasi:**
- ✅ Tulis detail lengkap (brand, model, fitur)
- ✅ Sertakan garansi jika ada
- ✅ Tulis alasan pemilihan (jika perlu)
- ✅ Semakin detail, semakin mudah approval

---

### 💰 Langkah 4: Isi Informasi Keuangan

**Form Keuangan:**
```
┌─────────────────────────────────────┐
│ INFORMASI KEUANGAN                  │
│                                     │
│ DPP (Dasar Pengenaan Pajak) *       │
│ Rp [_____________________________]  │
│                                     │
│ PPN (11%) *                         │
│ Rp [_____________________________]  │
│                                     │
│ Total (DPP + PPN)                   │
│ Rp 0 (Auto-calculated)              │
│                                     │
│ Kategori *                          │
│ [Pilih Kategori ▼]                  │
└─────────────────────────────────────┘
```

**Cara Hitung DPP & PPN:**

**Jika Anda Tahu Total Harga (Include PPN):**
```
Total Harga: Rp 5.000.000

DPP = Total / 1.11 = Rp 5.000.000 / 1.11 = Rp 4.504.505
PPN = DPP × 11% = Rp 4.504.505 × 11% = Rp 495.495
Total = DPP + PPN = Rp 5.000.000
```

**Jika Anda Tahu Harga Exclude PPN:**
```
Harga Exclude PPN: Rp 4.500.000

DPP = Rp 4.500.000
PPN = DPP × 11% = Rp 4.500.000 × 11% = Rp 495.000
Total = DPP + PPN = Rp 4.995.000
```

**Tips:**
- ✅ Tanya vendor: "Harga include PPN atau exclude PPN?"
- ✅ Jika tidak ada PPN, isi DPP = Total, PPN = 0
- ✅ Sistem akan auto-calculate Total

---

### 🏢 Langkah 5: Pilih Alokasi Cabang

**Form Alokasi:**
```
┌─────────────────────────────────────┐
│ ALOKASI CABANG                      │
│                                     │
│ Metode Distribusi *                 │
│ [Equal Distribution ▼]              │
│                                     │
│ Pilih Cabang *                      │
│ ☑ Cabang Sudirman                   │
│ ☑ Cabang Thamrin                    │
│ ☐ Cabang Kuningan                   │
│                                     │
│ Preview Alokasi:                    │
│ ┌─────────────────────────────┐    │
│ │ Cabang Sudirman: 50%        │    │
│ │ Rp 2.500.000                │    │
│ │                             │    │
│ │ Cabang Thamrin: 50%         │    │
│ │ Rp 2.500.000                │    │
│ └─────────────────────────────┘    │
└─────────────────────────────────────┘
```

**Metode Distribusi:**

1. **Equal Distribution** (Rata)
   - Biaya dibagi rata ke semua cabang
   - Contoh: 2 cabang → 50% : 50%

2. **Percentage** (Persentase Manual)
   - Anda tentukan persentase sendiri
   - Contoh: Cabang A 70%, Cabang B 30%

3. **Manual Amount** (Nominal Manual)
   - Anda tentukan nominal per cabang
   - Contoh: Cabang A Rp 3 juta, Cabang B Rp 2 juta

**Tips Alokasi:**
- ✅ Pilih cabang yang akan menggunakan barang
- ✅ Jika untuk 1 cabang saja, pilih 1 cabang (100%)
- ✅ Jika untuk beberapa cabang, pilih metode yang sesuai

---

### 📄 Langkah 6: Tambahkan Keterangan & Foto

**Form Tambahan:**
```
┌─────────────────────────────────────┐
│ INFORMASI TAMBAHAN                  │
│                                     │
│ Alasan Pembelian *                  │
│ [_____________________________]     │
│ [_____________________________]     │
│ [_____________________________]     │
│                                     │
│ Upload Foto Pendukung (Opsional)    │
│ [Pilih File] atau [Ambil Foto]     │
│                                     │
│ Preview:                            │
│ [Foto 1] [Foto 2] [Foto 3]         │
└─────────────────────────────────────┘
```

**Alasan Pembelian:**
- Jelaskan **mengapa** perlu beli barang ini
- Jelaskan **untuk apa** barang ini digunakan
- Jelaskan **urgency** (seberapa urgent)

**Contoh Alasan yang Baik:**
```
Router kantor Cabang Sudirman sering putus koneksi, 
mengganggu operasional. Perlu upgrade ke WiFi 6 untuk 
menampung 50+ device. Urgent karena banyak komplain 
dari karyawan. Estimasi harga sudah survey 3 toko.
```

**Foto Pendukung:**
- Screenshot spesifikasi dari website vendor
- Foto barang yang rusak (jika replacement)
- Screenshot harga dari marketplace
- Foto lokasi instalasi (jika perlu)

---

### ✅ Langkah 7: Review & Submit

**Preview Pengajuan:**
```
┌─────────────────────────────────────┐
│ REVIEW PENGAJUAN                    │
│                                     │
│ Vendor: Toko Elektronik Jaya        │
│ Item: Router Wireless (2 unit)      │
│ Total: Rp 5.000.000                 │
│ Kategori: Peralatan Kantor          │
│ Cabang: Sudirman (50%), Thamrin (50%)│
│                                     │
│ ⚠️ Pastikan semua data benar!       │
│    Pengajuan tidak bisa diedit      │
│    setelah disubmit.                │
│                                     │
│ [Batal]  [Submit Pengajuan]        │
└─────────────────────────────────────┘
```

**Checklist Sebelum Submit:**
- [ ] Nama vendor benar
- [ ] Spesifikasi lengkap & detail
- [ ] Harga sudah survey (tidak asal tebak)
- [ ] DPP & PPN benar
- [ ] Kategori sesuai
- [ ] Alokasi cabang benar
- [ ] Alasan pembelian jelas

**Klik "Submit Pengajuan"**
- Sistem akan simpan pengajuan
- Status: `Pending`
- Anda akan dapat notifikasi saat ada update

---

## Dual-Version System

### Apa itu Dual-Version?

**Dual-Version System** adalah fitur untuk **tracking perubahan** yang dilakukan Management pada pengajuan Anda. Sistem menyimpan **2 versi**:

1. **Versi Pengaju** (Original) - Data yang Anda submit
2. **Versi Management** (Edited) - Data setelah diedit Management

---

### Mengapa Perlu Dual-Version?

**Transparansi & Audit Trail:**
- ✅ Anda bisa lihat **apa yang berubah**
- ✅ Management bisa **revisi** tanpa kehilangan data original
- ✅ Audit trail lengkap untuk **accountability**

---

### Kapan Management Edit Pengajuan?

**Skenario Umum:**

1. **Harga Terlalu Tinggi**
   - Anda ajukan Rp 5 juta
   - Management nego vendor, dapat Rp 4.5 juta
   - Management edit harga di sistem

2. **Spesifikasi Berubah**
   - Anda ajukan Router Brand A
   - Management putuskan pakai Brand B (lebih murah/bagus)
   - Management edit spesifikasi

3. **Quantity Berubah**
   - Anda ajukan 2 unit
   - Management putuskan cukup 1 unit
   - Management edit quantity

4. **Alokasi Cabang Berubah**
   - Anda ajukan untuk Cabang A & B
   - Management putuskan hanya untuk Cabang A
   - Management edit alokasi

---

### Cara Melihat Perbandingan Versi

**Di Detail Transaksi:**
```
┌─────────────────────────────────────────────────────┐
│ DETAIL PENGAJUAN                                    │
│                                                     │
│ Status: Approved                                    │
│ ⚠️ Pengajuan ini telah diedit oleh Management       │
│                                                     │
│ [Toggle: Lihat Perbandingan Versi]                 │
│                                                     │
│ ┌─────────────────┬─────────────────┐              │
│ │ Versi Pengaju   │ Versi Management│              │
│ ├─────────────────┼─────────────────┤              │
│ │ Router Brand A  │ Router Brand B  │ ← Berubah   │
│ │ 2 unit          │ 1 unit          │ ← Berubah   │
│ │ Rp 5.000.000    │ Rp 4.500.000    │ ← Berubah   │
│ │ Cabang A & B    │ Cabang A saja   │ ← Berubah   │
│ └─────────────────┴─────────────────┘              │
│                                                     │
│ Catatan Management:                                 │
│ "Harga berhasil dinegosiasi. Brand B lebih         │
│  reliable dan 1 unit sudah cukup untuk saat ini."  │
└─────────────────────────────────────────────────────┘
```

**Indikator Perubahan:**
- 🟡 **Kuning** = Field yang berubah
- ✅ **Hijau** = Field yang sama
- 📝 **Catatan** = Alasan perubahan dari Management

---

### Apakah Perubahan Perlu Approval Ulang?

**Tidak!** Perubahan oleh Management adalah **final decision**. Anda tidak perlu approve ulang. Tapi Anda bisa:
- ✅ Lihat apa yang berubah
- ✅ Baca alasan perubahan
- ✅ Diskusi dengan Management jika ada pertanyaan

---

## Alokasi Cabang

### Apa itu Alokasi Cabang?

**Alokasi Cabang** adalah pembagian biaya transaksi ke beberapa cabang. Berguna untuk:
- Barang yang digunakan beberapa cabang
- Biaya yang ditanggung bersama
- Tracking pengeluaran per cabang

---

### Metode Alokasi

#### 1. Equal Distribution (Rata)

**Kapan Digunakan:**
- Barang digunakan sama rata oleh semua cabang
- Tidak ada perbedaan kontribusi

**Contoh:**
```
Total: Rp 6.000.000
Cabang: 3 (Sudirman, Thamrin, Kuningan)

Alokasi:
- Sudirman: 33.33% = Rp 2.000.000
- Thamrin: 33.33% = Rp 2.000.000
- Kuningan: 33.33% = Rp 2.000.000
```

---

#### 2. Percentage (Persentase Manual)

**Kapan Digunakan:**
- Ada perbedaan kontribusi antar cabang
- Berdasarkan kesepakatan Management

**Contoh:**
```
Total: Rp 6.000.000
Cabang: 3 (Sudirman, Thamrin, Kuningan)

Alokasi:
- Sudirman: 50% = Rp 3.000.000 (cabang besar)
- Thamrin: 30% = Rp 1.800.000 (cabang sedang)
- Kuningan: 20% = Rp 1.200.000 (cabang kecil)

Total: 100% = Rp 6.000.000 ✅
```

**⚠️ Penting:** Total persentase harus **100%**!

---

#### 3. Manual Amount (Nominal Manual)

**Kapan Digunakan:**
- Nominal sudah ditentukan spesifik per cabang
- Tidak berdasarkan persentase

**Contoh:**
```
Total: Rp 6.000.000
Cabang: 3 (Sudirman, Thamrin, Kuningan)

Alokasi:
- Sudirman: Rp 3.500.000
- Thamrin: Rp 1.500.000
- Kuningan: Rp 1.000.000

Total: Rp 6.000.000 ✅
```

**⚠️ Penting:** Total nominal harus **sama dengan total transaksi**!

---

### Hutang Antar Cabang

**Apa itu Hutang Antar Cabang?**

Jika satu cabang bayar untuk cabang lain, maka cabang yang dibantu akan **berhutang** ke cabang yang bayar.

**Contoh Skenario:**
```
Transaksi: Rp 6.000.000
Dibayar oleh: Cabang Sudirman (100%)

Alokasi:
- Sudirman: Rp 3.000.000 (50%)
- Thamrin: Rp 1.800.000 (30%)
- Kuningan: Rp 1.200.000 (20%)

Hutang:
- Thamrin hutang ke Sudirman: Rp 1.800.000
- Kuningan hutang ke Sudirman: Rp 1.200.000
```

**Pelunasan Hutang:**
- Cabang yang berhutang transfer ke cabang yang bayar
- Upload bukti transfer di sistem
- Sistem otomatis catat pelunasan
- Status transaksi jadi `Completed` setelah semua hutang lunas

---

## Memahami Status Pengajuan

### Status Lifecycle

```
┌─────────┐
│ Pending │ ← Baru disubmit
└────┬────┘
     │
     ├─→ [Rejected] ← Ditolak
     │
     ├─→ [Approved] ← Disetujui Admin (≥ 1 Jt, tunggu Owner)
     │       │
     │       └─→ [Waiting Payment] ← Owner approve
     │
     └─→ [Waiting Payment] ← Disetujui Admin (< 1 Jt)
             │
             └─→ [Completed] ← Invoice upload + Hutang lunas
```

---

### Detail Status

| Status | Arti | Apa yang Harus Dilakukan |
|--------|------|--------------------------|
| **Pending** | Menunggu review Management | Tunggu notifikasi (1-3 hari kerja) |
| **Approved** | Disetujui Admin, tunggu Owner (≥ 1 Jt) | Tunggu notifikasi Owner approval |
| **Waiting Payment** | Disetujui, boleh beli barang | **Beli barang sesuai approval!** |
| **Completed** | Invoice sudah upload, hutang lunas | Selesai! Cek rekening untuk penggantian |
| **Rejected** | Ditolak Management | Baca alasan penolakan, perbaiki jika perlu |

---

## Setelah Disetujui

### Langkah Setelah Status "Waiting Payment"

**1. Beli Barang Sesuai Approval**
- ✅ Beli barang sesuai spesifikasi yang disetujui
- ✅ Jika ada perubahan dari Management, ikuti versi Management
- ✅ Simpan invoice/nota pembelian

**2. Upload Invoice**
- Login ke sistem
- Buka detail pengajuan
- Klik "Upload Invoice"
- Upload foto/scan invoice
- Submit

**3. Tunggu Verifikasi**
- Admin akan verifikasi invoice
- Cek apakah sesuai dengan approval
- Jika OK, proses pembayaran

**4. Pelunasan Hutang (Jika Ada)**
- Jika ada alokasi multi-cabang, mungkin ada hutang
- Cabang yang berhutang transfer ke cabang yang bayar
- Upload bukti transfer
- Status jadi `Completed` setelah semua lunas

**5. Terima Penggantian**
- Setelah status `Completed`, uang akan ditransfer
- Cek rekening Anda (1-3 hari kerja)

---

## Troubleshooting

### Masalah 1: "Total Persentase Tidak 100%"

**Penyebab:**
- Salah hitung persentase alokasi
- Contoh: 50% + 40% = 90% (kurang 10%)

**Solusi:**
1. Cek ulang total persentase
2. Pastikan total = 100%
3. Gunakan kalkulator jika perlu

---

### Masalah 2: "Total Nominal Tidak Sesuai"

**Penyebab:**
- Salah hitung nominal alokasi manual
- Total alokasi ≠ total transaksi

**Solusi:**
1. Cek ulang total nominal
2. Pastikan total alokasi = total transaksi
3. Gunakan kalkulator

---

### Masalah 3: "Harga Ditolak karena Terlalu Tinggi"

**Penyebab:**
- Harga yang diajukan melebihi referensi Price Index
- Sistem deteksi anomali harga

**Solusi:**
1. **Survey ulang** harga di vendor lain
2. **Jelaskan alasan** jika memang harga segitu (barang langka, urgent, dll.)
3. **Nego vendor** untuk harga lebih baik
4. **Konsultasi Management** sebelum submit

---

### Masalah 4: "Pengajuan Ditolak"

**Penyebab:**
- Spesifikasi tidak jelas
- Harga terlalu tinggi
- Tidak urgent/tidak perlu
- Budget tidak tersedia

**Solusi:**
1. **Baca alasan penolakan** di detail transaksi
2. **Perbaiki** sesuai feedback
3. **Submit ulang** dengan data yang lebih baik
4. **Diskusi** dengan Management jika tidak setuju

---

## FAQ

**Q: Berapa lama proses approval Pengajuan?**  
A: Biasanya 1-3 hari kerja. Jika urgent, hubungi Management via Telegram.

**Q: Apakah bisa edit Pengajuan setelah submit?**  
A: Tidak bisa. Jika ada kesalahan, hubungi Admin untuk dibatalkan, lalu submit ulang.

**Q: Apakah harus beli di vendor yang diajukan?**  
A: Idealnya ya. Jika ada perubahan vendor, konfirmasi dulu dengan Management.

**Q: Bagaimana jika harga aktual lebih murah dari approval?**  
A: Bagus! Upload invoice dengan harga aktual. Penggantian sesuai harga aktual.

**Q: Bagaimana jika harga aktual lebih mahal dari approval?**  
A: Jangan beli dulu! Hubungi Management untuk approval tambahan.

**Q: Apakah bisa mengajukan beberapa item sekaligus?**  
A: Saat ini 1 pengajuan = 1 item. Jika ada beberapa item, submit terpisah.

**Q: Berapa lama uang cair setelah upload invoice?**  
A: 1-3 hari kerja setelah invoice diverifikasi dan status `Completed`.

**Q: Apakah bisa cancel Pengajuan yang sudah disubmit?**  
A: Hubungi Admin untuk cancel. Jika sudah disetujui, tidak bisa cancel.

---

## 📚 Dokumentasi Terkait

- 📖 [Pengenalan Sistem](00_PENGENALAN_SISTEM.md)
- 💰 [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md)
- ✅ [Panduan Approval](06_APPROVAL_TRANSAKSI.md) *(Coming Soon)*
- 💡 [Price Index System](13_PRICE_INDEX.md) *(Coming Soon)*

---

## 📞 Butuh Bantuan?

Jika ada pertanyaan atau kendala:

- 💬 **Telegram:** @WhusnetSupport
- 📧 **Email:** support@whusnet.com
- 📱 **WhatsApp:** +62 xxx-xxxx-xxxx

---

**Rencanakan pembelian dengan baik untuk approval yang lancar!** 📝✨

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
