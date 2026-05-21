# ✅ Panduan Approval Transaksi

**Untuk Siapa:** Admin, Atasan, Owner  
**Waktu Baca:** 10 menit  
**Level:** Menengah

---

## 📋 Daftar Isi
- [Siapa yang Bisa Approve?](#siapa-yang-bisa-approve)
- [Cara Menyetujui Transaksi](#cara-menyetujui-transaksi)
- [Cara Menolak Transaksi](#cara-menolak-transaksi)
- [Approval Berdasarkan Nominal](#approval-berdasarkan-nominal)
- [Force Approve & Override](#force-approve--override)
- [Best Practices Approval](#best-practices-approval)
- [FAQ](#faq)

---

## Siapa yang Bisa Approve?

### Hierarki Approval

```
┌─────────────────────────────────────┐
│     HIERARKI APPROVAL               │
│                                     │
│  Transaksi < Rp 1.000.000           │
│  ┌──────────────────┐               │
│  │ Admin/Atasan     │ ✅ Approve    │
│  └────┬─────────────┘               │
│       │                             │
│       ▼                             │
│  Waiting Payment (Langsung)         │
│                                     │
│  ─────────────────────────────────  │
│                                     │
│  Transaksi ≥ Rp 1.000.000           │
│  ┌──────────────────┐               │
│  │ Admin/Atasan     │ ✅ Approve    │
│  └────┬─────────────┘               │
│       │                             │
│       ▼                             │
│  Approved (Tunggu Owner)            │
│       │                             │
│       ▼                             │
│  ┌──────────────────┐               │
│  │ Owner            │ ✅ Final      │
│  └────┬─────────────┘               │
│       │                             │
│       ▼                             │
│  Waiting Payment                    │
└─────────────────────────────────────┘
```

---

### Tabel Hak Approve

| Nominal Transaksi | Admin | Atasan | Owner |
|-------------------|:-----:|:------:|:-----:|
| **< Rp 500.000** | ✅ Approve langsung | ✅ Approve langsung | ✅ Approve langsung |
| **Rp 500.000 - Rp 999.999** | ✅ Approve langsung | ✅ Approve langsung | ✅ Approve langsung |
| **Rp 1.000.000 - Rp 4.999.999** | ⏳ Approve → Tunggu Owner | ⏳ Approve → Tunggu Owner | ✅ Final Approve |
| **≥ Rp 5.000.000** | ⏳ Approve → Tunggu Owner | ⏳ Approve → Tunggu Owner | ✅ Final Approve |

**Keterangan:**
- ✅ = Bisa approve langsung (status jadi `Waiting Payment`)
- ⏳ = Bisa approve tapi perlu Owner (status jadi `Approved`, tunggu Owner)

---

### Pengecualian Khusus

**1. Transaksi Gudang**
- Admin **tidak bisa approve** transaksi Gudang sendiri
- Harus di-approve oleh Atasan/Owner
- Untuk mencegah conflict of interest

**2. Transaksi Sendiri**
- Tidak direkomendasikan approve transaksi sendiri
- Best practice: minta Admin/Atasan/Owner lain yang approve
- Untuk audit trail yang lebih baik

---

## Cara Menyetujui Transaksi

### 📋 Langkah 1: Lihat Daftar Pending

**Via Dashboard:**
```
1. Login ke sistem
2. Lihat Dashboard
3. Scroll ke bagian "Transaksi Pending"
4. Klik "View" pada transaksi yang ingin direview
```

**Via Halaman Transaksi:**
```
1. Login ke sistem
2. Klik menu "Transaksi"
3. Filter status: "Pending"
4. Klik transaksi yang ingin direview
```

---

### 🔍 Langkah 2: Review Detail Transaksi

**Halaman Detail:**
```
┌─────────────────────────────────────────────┐
│ DETAIL TRANSAKSI                            │
│                                             │
│ ID: REM-20260521-00123                      │
│ Tipe: Rembush                               │
│ Status: Pending                             │
│ Diajukan oleh: Teknisi Budi                 │
│ Tanggal: 21 Mei 2026                        │
│                                             │
│ ─────────────────────────────────────────   │
│                                             │
│ Vendor: Toko Elektronik Jaya                │
│ Item: Kabel LAN Cat6 (10 meter)             │
│ Nominal: Rp 150.000                         │
│ Kategori: Instalasi                         │
│ Cabang: Cabang Sudirman                     │
│                                             │
│ ─────────────────────────────────────────   │
│                                             │
│ Foto Nota: [Lihat Foto]                    │
│ Hasil OCR: ✅ High Confidence (95%)         │
│                                             │
│ ─────────────────────────────────────────   │
│                                             │
│ [❌ Reject]  [✅ Approve]                   │
└─────────────────────────────────────────────┘
```

---

**Checklist Review:**

**1. Cek Foto Nota**
- [ ] Foto jelas & terbaca
- [ ] Nota asli (bukan editan)
- [ ] Tanggal nota valid (< 2 hari)
- [ ] Vendor & nominal sesuai

**2. Cek Data Transaksi**
- [ ] Vendor benar
- [ ] Item jelas & detail
- [ ] Nominal sesuai nota
- [ ] Kategori tepat
- [ ] Cabang benar

**3. Cek Hasil OCR (Jika Rembush)**
- [ ] Confidence level (High/Medium/Low)
- [ ] Data OCR vs data manual (apakah sesuai?)
- [ ] Jika Low Confidence, cek lebih teliti

**4. Cek Kewajaran**
- [ ] Apakah pembelian wajar?
- [ ] Apakah harga wajar? (cek Price Index jika ada)
- [ ] Apakah untuk keperluan perusahaan?
- [ ] Apakah ada duplikasi?

---

### ✅ Langkah 3: Approve Transaksi

**Jika Semua OK:**

1. **Klik tombol "Approve"**
2. **Sistem akan tanya konfirmasi:**

```
┌─────────────────────────────────────┐
│  ⚠️ Konfirmasi Approval              │
│                                     │
│  Anda akan menyetujui transaksi:    │
│  REM-20260521-00123                 │
│  Nominal: Rp 150.000                │
│                                     │
│  Apakah Anda yakin?                 │
│                                     │
│  [Batal]  [Ya, Approve]            │
└─────────────────────────────────────┘
```

3. **Klik "Ya, Approve"**

---

**Hasil Approval:**

**Jika Nominal < Rp 1.000.000:**
```
┌─────────────────────────────────────┐
│  ✅ Transaksi Disetujui!             │
│                                     │
│  Status: Waiting Payment            │
│                                     │
│  Transaksi siap untuk upload        │
│  bukti pembayaran.                  │
│                                     │
│  [OK]                               │
└─────────────────────────────────────┘
```

**Jika Nominal ≥ Rp 1.000.000:**
```
┌─────────────────────────────────────┐
│  ✅ Transaksi Disetujui!             │
│                                     │
│  Status: Approved                   │
│                                     │
│  Transaksi menunggu approval        │
│  Owner untuk nominal ≥ 1 Jt.       │
│                                     │
│  Owner akan menerima notifikasi.    │
│                                     │
│  [OK]                               │
└─────────────────────────────────────┘
```

---

**Notifikasi:**
- ✅ Submitter akan menerima notifikasi "Transaksi Disetujui"
- ✅ Jika ≥ 1 Jt, Owner akan menerima notifikasi "Perlu Approval"

---

## Cara Menolak Transaksi

### ❌ Langkah 1: Klik Tombol Reject

**Di Halaman Detail:**
1. Review transaksi
2. Jika ada masalah, klik tombol **"Reject"**

---

### 📝 Langkah 2: Tulis Alasan Penolakan

**Form Reject:**
```
┌─────────────────────────────────────┐
│  ❌ Tolak Transaksi                  │
│                                     │
│  ID: REM-20260521-00123             │
│  Nominal: Rp 150.000                │
│                                     │
│  Alasan Penolakan *                 │
│  [_____________________________]    │
│  [_____________________________]    │
│  [_____________________________]    │
│                                     │
│  ⚠️ Alasan akan dikirim ke          │
│     submitter via notifikasi.       │
│                                     │
│  [Batal]  [Tolak Transaksi]        │
└─────────────────────────────────────┘
```

---

**Contoh Alasan yang Baik:**

**✅ Alasan yang Jelas & Konstruktif:**
```
"Nota tidak jelas, mohon upload ulang dengan 
foto yang lebih terang. Pastikan semua teks 
terbaca dengan jelas."
```

```
"Harga terlalu tinggi. Harga pasaran kabel 
LAN Cat6 10m sekitar Rp 100.000, bukan 
Rp 150.000. Mohon survey ulang atau nego 
dengan vendor."
```

```
"Item tidak sesuai dengan kebutuhan. Kabel 
yang dibutuhkan adalah Cat5e, bukan Cat6. 
Mohon beli sesuai spesifikasi."
```

**❌ Alasan yang Tidak Jelas:**
```
"Ditolak" ← Tidak informatif
"Salah" ← Tidak jelas salahnya apa
"Tidak sesuai" ← Tidak sesuai dengan apa?
```

---

### ✅ Langkah 3: Submit Penolakan

1. **Tulis alasan** yang jelas & konstruktif
2. **Klik "Tolak Transaksi"**
3. **Sistem akan konfirmasi:**

```
┌─────────────────────────────────────┐
│  ✅ Transaksi Ditolak                │
│                                     │
│  Status: Rejected                   │
│                                     │
│  Submitter akan menerima notifikasi │
│  dengan alasan penolakan.           │
│                                     │
│  [OK]                               │
└─────────────────────────────────────┘
```

---

**Notifikasi:**
- ✅ Submitter akan menerima notifikasi "Transaksi Ditolak"
- ✅ Alasan penolakan akan ditampilkan di notifikasi
- ✅ Submitter bisa perbaiki dan submit ulang

---

## Approval Berdasarkan Nominal

### Skenario 1: Transaksi < Rp 1.000.000

**Contoh: Rembush Rp 150.000**

```
┌─────────────────────────────────────┐
│  Teknisi Submit                     │
│  ↓                                  │
│  Status: Pending                    │
│  ↓                                  │
│  Admin/Atasan Review                │
│  ↓                                  │
│  Admin/Atasan Approve               │
│  ↓                                  │
│  Status: Waiting Payment ✅         │
│  (Langsung, tidak perlu Owner)      │
└─────────────────────────────────────┘
```

**Timeline:** 1-2 hari kerja

---

### Skenario 2: Transaksi ≥ Rp 1.000.000

**Contoh: Pengajuan Rp 5.000.000**

```
┌─────────────────────────────────────┐
│  Teknisi Submit                     │
│  ↓                                  │
│  Status: Pending                    │
│  ↓                                  │
│  Admin/Atasan Review                │
│  ↓                                  │
│  Admin/Atasan Approve               │
│  ↓                                  │
│  Status: Approved ⏳                │
│  (Tunggu Owner)                     │
│  ↓                                  │
│  Owner Review                       │
│  ↓                                  │
│  Owner Approve                      │
│  ↓                                  │
│  Status: Waiting Payment ✅         │
└─────────────────────────────────────┘
```

**Timeline:** 2-4 hari kerja (tergantung ketersediaan Owner)

---

### Tips Mempercepat Approval

**Untuk Submitter:**
- ✅ Upload foto nota yang jelas
- ✅ Isi data lengkap & akurat
- ✅ Pilih kategori yang tepat
- ✅ Tulis keterangan yang informatif

**Untuk Approver:**
- ✅ Cek transaksi pending setiap hari
- ✅ Approve/reject segera jika sudah review
- ✅ Jangan tunda-tunda (max 1-2 hari)
- ✅ Jika urgent, koordinasi via Telegram

---

## Force Approve & Override

### 🔓 Force Approve (Owner Only)

**Apa itu Force Approve?**
- Fitur khusus Owner untuk approve transaksi yang **di-flag** karena selisih nominal
- Contoh: Nota Rp 150.000, tapi transfer Rp 145.000 (selisih Rp 5.000)

**Kapan Digunakan?**
- AI salah deteksi (sebenarnya tidak ada selisih)
- Selisih karena biaya admin bank (wajar)
- Selisih karena potongan yang sudah disepakati

---

**Cara Force Approve:**

1. **Buka transaksi yang di-flag**
2. **Klik tombol "Force Approve"**
3. **Tulis alasan:**

```
┌─────────────────────────────────────┐
│  🔓 Force Approve                    │
│                                     │
│  ID: REM-20260521-00123             │
│  Status: Flagged                    │
│  Alasan Flag: Selisih nominal       │
│                                     │
│  Alasan Force Approve *             │
│  [_____________________________]    │
│  [_____________________________]    │
│                                     │
│  ⚠️ Force Approve akan tercatat     │
│     di audit log.                   │
│                                     │
│  [Batal]  [Force Approve]          │
└─────────────────────────────────────┘
```

**Contoh Alasan:**
```
"AI salah deteksi. Nominal sudah benar 
Rp 150.000. Tidak ada selisih."
```

```
"Selisih Rp 5.000 adalah biaya admin bank. 
Sudah dikonfirmasi dengan Teknisi."
```

4. **Klik "Force Approve"**
5. **Status jadi "Completed"**

---

### 🔓 Override Auto-Reject (Owner Only)

**Apa itu Override?**
- Fitur khusus Owner untuk approve transaksi yang **auto-reject** karena nota kadaluarsa (> 2 hari)

**Kapan Digunakan?**
- Nota memang valid tapi terlambat upload (sakit, internet mati, dll.)
- Ada alasan yang bisa dipertanggungjawabkan

---

**Cara Override:**

1. **Buka transaksi yang auto-reject**
2. **Klik tombol "Override"**
3. **Tulis alasan:**

```
┌─────────────────────────────────────┐
│  🔓 Override Auto-Reject             │
│                                     │
│  ID: REM-20260521-00123             │
│  Status: Auto-Reject                │
│  Alasan: Nota > 2 hari              │
│                                     │
│  Alasan Override *                  │
│  [_____________________________]    │
│  [_____________________________]    │
│                                     │
│  ⚠️ Override akan tercatat di       │
│     audit log.                      │
│                                     │
│  [Batal]  [Override]               │
└─────────────────────────────────────┘
```

**Contoh Alasan:**
```
"Teknisi sakit 3 hari, baru bisa upload 
setelah sembuh. Nota valid, pembelian 
untuk instalasi urgent."
```

```
"Internet kantor mati 4 hari karena 
maintenance provider. Nota valid."
```

4. **Klik "Override"**
5. **Status jadi "Pending"** (bisa diproses normal)

---

## Best Practices Approval

### ✅ DO (Lakukan)

1. **Review Setiap Hari**
   - Cek transaksi pending setiap hari
   - Jangan biarkan pending terlalu lama
   - Target: approve/reject dalam 1-2 hari

2. **Review dengan Teliti**
   - Cek foto nota dengan seksama
   - Cek kewajaran harga (bandingkan dengan Price Index)
   - Cek kesesuaian dengan kebutuhan

3. **Berikan Alasan yang Jelas (Jika Reject)**
   - Tulis alasan yang konstruktif
   - Jelaskan apa yang salah
   - Berikan saran perbaikan

4. **Koordinasi Jika Urgent**
   - Jika transaksi urgent, koordinasi via Telegram
   - Approve segera jika memang layak
   - Jangan biarkan urgent pending lama

5. **Dokumentasi**
   - Jika ada keputusan khusus, tulis di keterangan
   - Untuk audit trail yang lebih baik

---

### ❌ DON'T (Jangan)

1. **Jangan Approve Asal-asalan**
   - ❌ Approve tanpa review
   - ❌ Approve hanya karena kenal submitter
   - ❌ Approve tanpa cek foto nota

2. **Jangan Reject Tanpa Alasan**
   - ❌ Reject dengan alasan "Ditolak"
   - ❌ Reject tanpa penjelasan
   - ❌ Reject tanpa saran perbaikan

3. **Jangan Tunda-tunda**
   - ❌ Biarkan pending > 3 hari tanpa alasan
   - ❌ Ignore notifikasi pending
   - ❌ Tunggu sampai submitter催 (催 =催促/催促)

4. **Jangan Approve Transaksi Sendiri (Jika Bisa Dihindari)**
   - ❌ Approve transaksi Gudang sendiri (Admin)
   - ❌ Approve transaksi sendiri tanpa alasan kuat
   - ✅ Minta Admin/Atasan/Owner lain yang approve

---

## FAQ

**Q: Berapa lama waktu ideal untuk approve transaksi?**  
A: 1-2 hari kerja. Jika urgent, bisa lebih cepat (beberapa jam).

**Q: Apakah bisa approve transaksi sendiri?**  
A: Bisa, tapi tidak direkomendasikan. Best practice: minta orang lain yang approve.

**Q: Bagaimana jika ragu-ragu mau approve atau reject?**  
A: Diskusikan dengan Atasan/Owner. Jangan approve jika ragu. Better safe than sorry.

**Q: Apakah bisa cancel approval yang sudah dilakukan?**  
A: Tidak bisa. Jika salah approve, hubungi Owner untuk Force Reject (jika ada fitur).

**Q: Bagaimana jika Owner tidak ada saat transaksi ≥ 1 Jt perlu approval?**  
A: Transaksi akan pending sampai Owner approve. Owner bisa approve dari mana saja (mobile).

**Q: Apakah bisa delegate approval ke orang lain?**  
A: Tidak ada fitur delegate. Jika tidak bisa approve, minta Admin/Atasan/Owner lain.

**Q: Bagaimana jika submitter tidak setuju dengan rejection?**  
A: Submitter bisa diskusi dengan Approver. Jika masih tidak setuju, escalate ke Owner.

**Q: Apakah ada notifikasi jika ada transaksi pending?**  
A: Ya, Approver akan menerima notifikasi saat ada transaksi baru yang perlu approval.

---

## 📚 Dokumentasi Terkait

- 📖 [Pengenalan Sistem](00_PENGENALAN_SISTEM.md)
- 👥 [Peran Pengguna](02_PERAN_PENGGUNA.md)
- 💰 [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md)
- 📝 [Panduan Pengajuan](04_PENGAJUAN_PEMBELIAN.md)
- 💸 [Panduan Pembayaran](07_PEMBAYARAN.md) *(Coming Soon)*

---

## 📞 Butuh Bantuan?

Jika ada pertanyaan atau kendala:

- 💬 **Telegram:** @WhusnetSupport
- 📧 **Email:** support@whusnet.com
- 📱 **WhatsApp:** +62 xxx-xxxx-xxxx
- 🕐 **Jam Operasional:** Senin-Jumat, 08:00-17:00 WIB

---

**Approve dengan bijak untuk keuangan yang sehat!** ✅💰

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
