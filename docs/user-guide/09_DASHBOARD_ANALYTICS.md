# 📊 Panduan Dashboard & Analytics

**Untuk Siapa:** Admin, Atasan, Owner  
**Waktu Baca:** 10 menit  
**Level:** Menengah

---

## 📋 Daftar Isi
- [Mengenal Dashboard](#mengenal-dashboard)
- [Statistik Transaksi](#statistik-transaksi)
- [Rincian Biaya per Cabang](#rincian-biaya-per-cabang)
- [Monitoring Hutang Antar Cabang](#monitoring-hutang-antar-cabang)
- [Transaksi Pending](#transaksi-pending)
- [Filter & Pencarian](#filter--pencarian)
- [Export Data](#export-data)
- [FAQ](#faq)

---

## Mengenal Dashboard

### Apa itu Dashboard?

**Dashboard** adalah halaman utama yang menampilkan **overview** keuangan perusahaan secara real-time. Dashboard hanya bisa diakses oleh **Admin, Atasan, dan Owner**.

---

### Layout Dashboard

```
┌─────────────────────────────────────────────────────────┐
│ 🏢 WHUSNET Admin Payment          [🔔] [👤 Nama Anda ▼] │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  📊 STATISTIK TRANSAKSI                                 │
│  ┌──────────┬──────────┬──────────┬──────────┐        │
│  │ Total    │ Pending  │ Approved │ Rejected │        │
│  │ 1,234    │ 45       │ 1,100    │ 89       │        │
│  └──────────┴──────────┴──────────┴──────────┘        │
│                                                         │
│  💰 RINCIAN BIAYA PER CABANG                           │
│  [Filter: Mei 2026 ▼]                                  │
│  ┌─────────────────────────────────────────┐          │
│  │ Cabang Sudirman    Rp 45.000.000  [📊]  │          │
│  │ Cabang Thamrin     Rp 32.000.000  [📊]  │          │
│  │ Cabang Kuningan    Rp 28.000.000  [📊]  │          │
│  └─────────────────────────────────────────┘          │
│                                                         │
│  📋 TRANSAKSI PENDING (Perlu Approval)                 │
│  ┌─────────────────────────────────────────┐          │
│  │ REM-20260521-00123  Rp 150.000  [View]  │          │
│  │ PEN-20260521-00045  Rp 2.500.000 [View] │          │
│  │ REM-20260521-00124  Rp 85.000   [View]  │          │
│  └─────────────────────────────────────────┘          │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

### Komponen Dashboard

**1. Header Bar**
- Logo & nama aplikasi
- Icon notifikasi (🔔) dengan badge
- Dropdown profil (👤)

**2. Statistik Transaksi**
- Total transaksi
- Jumlah pending
- Jumlah approved
- Jumlah rejected

**3. Rincian Biaya per Cabang**
- Breakdown pengeluaran per cabang
- Filter bulan/tahun
- Grafik visual (bar chart)

**4. Transaksi Pending**
- Daftar transaksi yang perlu approval
- Quick action: View & Approve

---

## Statistik Transaksi

### Card Statistik

```
┌──────────────────────────────────────────────┐
│  📊 STATISTIK TRANSAKSI                      │
│                                              │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐    │
│  │ Total    │ │ Pending  │ │ Approved │    │
│  │ 1,234    │ │ 45       │ │ 1,100    │    │
│  │ +12%     │ │ -5%      │ │ +15%     │    │
│  └──────────┘ └──────────┘ └──────────┘    │
│                                              │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐    │
│  │ Rejected │ │ Completed│ │ Flagged  │    │
│  │ 89       │ │ 980      │ │ 3        │    │
│  │ +2%      │ │ +18%     │ │ -50%     │    │
│  └──────────┘ └──────────┘ └──────────┘    │
│                                              │
│  [Lihat Detail]                              │
└──────────────────────────────────────────────┘
```

---

### Arti Setiap Statistik

**1. Total Transaksi**
- Jumlah semua transaksi (semua status)
- Termasuk: Pending, Approved, Completed, Rejected, dll.
- Persentase: Perbandingan dengan bulan lalu

**2. Pending**
- Transaksi yang menunggu approval
- Perlu action dari Admin/Atasan/Owner
- **Penting:** Jangan biarkan pending terlalu lama!

**3. Approved**
- Transaksi yang sudah disetujui Admin/Atasan
- Menunggu approval Owner (jika ≥ 1 Jt)
- Atau menunggu upload bukti bayar (jika < 1 Jt)

**4. Rejected**
- Transaksi yang ditolak
- Perlu perbaikan dari submitter
- Bisa submit ulang setelah diperbaiki

**5. Completed**
- Transaksi yang sudah selesai
- Pembayaran sudah dilakukan
- Tidak bisa diedit lagi

**6. Flagged**
- Transaksi yang di-flag karena selisih nominal
- Perlu review Owner untuk Force Approve
- **Penting:** Segera review!

---

### Cara Membaca Persentase

**Contoh:**
```
Pending: 45 (+12%)
```

**Arti:**
- Saat ini ada 45 transaksi pending
- Naik 12% dibanding bulan lalu
- Bulan lalu: ~40 transaksi pending

**Interpretasi:**
- ✅ **Hijau (+):** Naik (bisa baik atau buruk tergantung konteks)
- ❌ **Merah (-):** Turun (bisa baik atau buruk tergantung konteks)

**Contoh Interpretasi:**
- Pending naik (+12%): ⚠️ **Buruk** - Banyak transaksi belum diproses
- Completed naik (+18%): ✅ **Baik** - Banyak transaksi selesai
- Flagged turun (-50%): ✅ **Baik** - Sedikit masalah

---

## Rincian Biaya per Cabang

### Card Rincian Biaya

```
┌──────────────────────────────────────────────┐
│  💰 RINCIAN BIAYA PER CABANG                 │
│                                              │
│  Filter: [Mei 2026 ▼]  [Export Excel]       │
│                                              │
│  ┌────────────────────────────────────────┐ │
│  │ Cabang Sudirman                        │ │
│  │ Rp 45.000.000                          │ │
│  │ ████████████████████░░░░░░░░░░ 42.9%  │ │
│  │ [📊 Detail] [💳 Hutang]                │ │
│  └────────────────────────────────────────┘ │
│                                              │
│  ┌────────────────────────────────────────┐ │
│  │ Cabang Thamrin                         │ │
│  │ Rp 32.000.000                          │ │
│  │ ██████████████░░░░░░░░░░░░░░░░ 30.5%  │ │
│  │ [📊 Detail] [💳 Hutang]                │ │
│  └────────────────────────────────────────┘ │
│                                              │
│  ┌────────────────────────────────────────┐ │
│  │ Cabang Kuningan                        │ │
│  │ Rp 28.000.000                          │ │
│  │ ████████████░░░░░░░░░░░░░░░░░░ 26.7%  │ │
│  │ [📊 Detail] [💳 Hutang]                │ │
│  └────────────────────────────────────────┘ │
│                                              │
│  Total: Rp 105.000.000                       │
└──────────────────────────────────────────────┘
```

---

### Cara Membaca Rincian Biaya

**Informasi yang Ditampilkan:**
1. **Nama Cabang:** Cabang Sudirman, Thamrin, Kuningan
2. **Total Biaya:** Total pengeluaran cabang di periode tersebut
3. **Persentase:** Proporsi biaya cabang terhadap total
4. **Progress Bar:** Visual proporsi biaya
5. **Action Buttons:**
   - 📊 **Detail:** Lihat breakdown biaya per kategori
   - 💳 **Hutang:** Lihat hutang antar cabang

---

### Filter Periode

**Cara Filter:**
```
1. Klik dropdown "Filter: Mei 2026"
2. Pilih periode:
   - Bulan ini
   - Bulan lalu
   - 3 bulan terakhir
   - 6 bulan terakhir
   - Tahun ini
   - Custom range
3. Data akan update otomatis
```

**Contoh Custom Range:**
```
┌─────────────────────────────────────┐
│  Filter Custom Range                │
│                                     │
│  Dari Tanggal:                      │
│  [01/01/2026]                       │
│                                     │
│  Sampai Tanggal:                    │
│  [31/05/2026]                       │
│                                     │
│  [Batal]  [Terapkan]               │
└─────────────────────────────────────┘
```

---

### Detail Biaya per Cabang

**Klik "📊 Detail" untuk melihat breakdown:**

```
┌──────────────────────────────────────────────┐
│  DETAIL BIAYA - CABANG SUDIRMAN              │
│  Periode: Mei 2026                           │
│                                              │
│  Total: Rp 45.000.000                        │
│                                              │
│  Breakdown per Kategori:                     │
│  ┌────────────────────────────────────────┐ │
│  │ Instalasi        Rp 20.000.000  44.4%  │ │
│  │ Pembelian Alat   Rp 15.000.000  33.3%  │ │
│  │ Maintenance      Rp 7.000.000   15.6%  │ │
│  │ Operasional      Rp 3.000.000   6.7%   │ │
│  └────────────────────────────────────────┘ │
│                                              │
│  Breakdown per Tipe:                         │
│  ┌────────────────────────────────────────┐ │
│  │ Rembush          Rp 25.000.000  55.6%  │ │
│  │ Pengajuan        Rp 18.000.000  40.0%  │ │
│  │ Gudang           Rp 2.000.000   4.4%   │ │
│  └────────────────────────────────────────┘ │
│                                              │
│  [Export Excel]  [Tutup]                     │
└──────────────────────────────────────────────┘
```

---

## Monitoring Hutang Antar Cabang

### Card Hutang

**Klik "💳 Hutang" untuk melihat:**

```
┌──────────────────────────────────────────────┐
│  HUTANG ANTAR CABANG - CABANG SUDIRMAN       │
│  Periode: Mei 2026                           │
│                                              │
│  Piutang (Yang Berhutang ke Sudirman):       │
│  ┌────────────────────────────────────────┐ │
│  │ Cabang Thamrin   Rp 5.000.000  [Detail]│ │
│  │ Cabang Kuningan  Rp 3.000.000  [Detail]│ │
│  └────────────────────────────────────────┘ │
│  Total Piutang: Rp 8.000.000                 │
│                                              │
│  Hutang (Sudirman Berhutang ke):             │
│  ┌────────────────────────────────────────┐ │
│  │ Cabang Thamrin   Rp 2.000.000  [Detail]│ │
│  └────────────────────────────────────────┘ │
│  Total Hutang: Rp 2.000.000                  │
│                                              │
│  Net: Rp 6.000.000 (Piutang)                 │
│                                              │
│  [Tutup]                                     │
└──────────────────────────────────────────────┘
```

---

### Detail Hutang

**Klik "Detail" untuk melihat transaksi:**

```
┌──────────────────────────────────────────────┐
│  DETAIL HUTANG                               │
│  Thamrin → Sudirman: Rp 5.000.000            │
│                                              │
│  Transaksi:                                  │
│  ┌────────────────────────────────────────┐ │
│  │ PEN-20260515-00032                     │ │
│  │ Rp 3.000.000                           │ │
│  │ Status: Belum Lunas                    │ │
│  │ [Lihat Transaksi]                      │ │
│  └────────────────────────────────────────┘ │
│                                              │
│  ┌────────────────────────────────────────┐ │
│  │ PEN-20260518-00038                     │ │
│  │ Rp 2.000.000                           │ │
│  │ Status: Belum Lunas                    │ │
│  │ [Lihat Transaksi]                      │ │
│  └────────────────────────────────────────┘ │
│                                              │
│  [Tutup]                                     │
└──────────────────────────────────────────────┘
```

---

### Cara Membaca Hutang

**Piutang:**
- Cabang lain berhutang ke cabang ini
- Cabang ini **akan menerima** uang

**Hutang:**
- Cabang ini berhutang ke cabang lain
- Cabang ini **harus membayar** uang

**Net:**
- Selisih antara piutang dan hutang
- Positif: Lebih banyak piutang (baik)
- Negatif: Lebih banyak hutang (perlu bayar)

---

## Transaksi Pending

### Card Transaksi Pending

```
┌──────────────────────────────────────────────┐
│  📋 TRANSAKSI PENDING (Perlu Approval)       │
│                                              │
│  [Filter: Semua ▼]  [Refresh]               │
│                                              │
│  ┌────────────────────────────────────────┐ │
│  │ REM-20260521-00123                     │ │
│  │ Rp 150.000 - Instalasi                 │ │
│  │ Teknisi Budi - 2 jam lalu              │ │
│  │ [View] [Approve] [Reject]              │ │
│  └────────────────────────────────────────┘ │
│                                              │
│  ┌────────────────────────────────────────┐ │
│  │ PEN-20260521-00045                     │ │
│  │ Rp 2.500.000 - Pembelian Alat          │ │
│  │ Teknisi Andi - 5 jam lalu              │ │
│  │ [View] [Approve] [Reject]              │ │
│  └────────────────────────────────────────┘ │
│                                              │
│  Showing 2 of 45 pending transactions       │
│  [Lihat Semua]                               │
└──────────────────────────────────────────────┘
```

---

### Quick Action

**Dari Dashboard, Anda bisa:**
1. **View:** Lihat detail transaksi
2. **Approve:** Langsung approve (jika < 1 Jt)
3. **Reject:** Langsung reject dengan alasan

**Tips:**
- ✅ Review transaksi pending setiap hari
- ✅ Prioritaskan transaksi urgent
- ✅ Jangan biarkan pending > 3 hari

---

## Filter & Pencarian

### Filter Transaksi

**Di Halaman Transaksi:**
```
┌─────────────────────────────────────┐
│  FILTER TRANSAKSI                   │
│                                     │
│  Status:                            │
│  ☑ Semua                            │
│  ☐ Pending                          │
│  ☐ Approved                         │
│  ☐ Waiting Payment                  │
│  ☐ Completed                        │
│  ☐ Rejected                         │
│                                     │
│  Tipe:                              │
│  ☑ Semua                            │
│  ☐ Rembush                          │
│  ☐ Pengajuan                        │
│  ☐ Gudang                           │
│                                     │
│  Tanggal:                           │
│  Dari: [01/05/2026]                 │
│  Sampai: [31/05/2026]               │
│                                     │
│  Cabang:                            │
│  [Semua Cabang ▼]                   │
│                                     │
│  [Reset]  [Terapkan]               │
└─────────────────────────────────────┘
```

---

### Pencarian

**Search Box:**
```
[🔍 Cari transaksi...]
```

**Bisa cari berdasarkan:**
- ID Transaksi (REM-20260521-00123)
- Vendor/Toko (Toko Elektronik Jaya)
- Kategori (Instalasi)
- Nominal (150000)
- Submitter (Teknisi Budi)

**Tips Pencarian:**
- Ketik minimal 3 karakter
- Hasil muncul otomatis (real-time)
- Gunakan filter untuk hasil lebih spesifik

---

## Export Data

### Export Excel

**Cara Export:**
```
1. Buka halaman Transaksi atau Dashboard
2. Klik tombol "Export Excel"
3. Pilih periode:
   - Bulan ini
   - Bulan lalu
   - Custom range
4. Pilih data yang ingin diexport:
   ☑ Semua transaksi
   ☐ Hanya Completed
   ☐ Hanya Pending
5. Klik "Export"
6. File Excel akan terdownload
```

---

### Isi File Excel

**Sheet 1: Summary**
- Total transaksi
- Total nominal
- Breakdown per status
- Breakdown per tipe
- Breakdown per cabang

**Sheet 2: Transactions**
- ID Transaksi
- Tanggal
- Tipe
- Vendor
- Item
- Nominal
- Status
- Submitter
- Approver
- Cabang

**Sheet 3: Branch Cost**
- Nama Cabang
- Total Biaya
- Breakdown per Kategori
- Breakdown per Tipe

---

### Tips Export

**Untuk Laporan Bulanan:**
```
1. Export di awal bulan berikutnya
2. Pilih periode: Bulan lalu
3. Pilih: Semua transaksi
4. Gunakan untuk:
   - Laporan ke Management
   - Analisis pengeluaran
   - Audit trail
```

**Untuk Analisis:**
```
1. Export dengan filter spesifik
2. Contoh: Hanya Completed, Cabang Sudirman
3. Analisis di Excel:
   - Pivot table
   - Chart
   - Formula
```

---

## FAQ

**Q: Apakah dashboard update real-time?**  
A: Ya, dashboard update otomatis setiap ada perubahan transaksi (via WebSocket).

**Q: Berapa lama data tersimpan?**  
A: Data tersimpan permanen. Anda bisa lihat transaksi dari bulan/tahun lalu.

**Q: Apakah bisa export data tahun lalu?**  
A: Ya, pilih custom range dan tentukan periode yang diinginkan.

**Q: Apakah Teknisi bisa lihat dashboard?**  
A: Tidak, dashboard hanya untuk Admin/Atasan/Owner.

**Q: Bagaimana cara melihat transaksi yang di-flag?**  
A: Filter status: "Flagged" di halaman Transaksi.

**Q: Apakah bisa lihat detail transaksi dari dashboard?**  
A: Ya, klik "View" pada transaksi pending atau klik ID transaksi.

**Q: Berapa lama hutang antar cabang harus dilunasi?**  
A: Tidak ada batas waktu, tapi sebaiknya segera dilunasi untuk cash flow yang sehat.

**Q: Apakah bisa export data per cabang saja?**  
A: Ya, gunakan filter cabang sebelum export.

---

## 📚 Dokumentasi Terkait

- 📖 [Pengenalan Sistem](00_PENGENALAN_SISTEM.md)
- 👥 [Peran Pengguna](02_PERAN_PENGGUNA.md)
- ✅ [Panduan Approval](06_APPROVAL_TRANSAKSI.md)
- 💸 [Panduan Pembayaran](07_PEMBAYARAN.md)
- 🏢 [Manajemen Cabang](10_MANAJEMEN_CABANG.md) *(Coming Soon)*

---

## 📞 Butuh Bantuan?

Jika ada pertanyaan atau kendala:

- 💬 **Telegram:** @WhusnetSupport
- 📧 **Email:** support@whusnet.com
- 📱 **WhatsApp:** +62 xxx-xxxx-xxxx
- 🕐 **Jam Operasional:** Senin-Jumat, 08:00-17:00 WIB

---

**Monitor keuangan perusahaan dengan dashboard yang powerful!** 📊✨

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
