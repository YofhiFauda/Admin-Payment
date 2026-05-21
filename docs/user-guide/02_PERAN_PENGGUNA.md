# 👥 Peran Pengguna - WHUSNET Admin Payment

**Untuk Siapa:** Semua Pengguna  
**Waktu Baca:** ~8 menit  
**Level:** Pemula

---

## 📋 Daftar Isi

- [Overview Peran](#overview-peran)
- [Teknisi](#teknisi)
- [Admin](#admin)
- [Atasan](#atasan)
- [Owner](#owner)
- [Tabel Perbandingan Akses](#tabel-perbandingan-akses)
- [Hierarki Approval](#hierarki-approval)
- [FAQ](#faq)

---

## Overview Peran

Sistem WHUSNET Admin Payment memiliki **4 peran pengguna** dengan hak akses hierarkis:

```
┌─────────────────────────────────────────────┐
│                                             │
│              👑 OWNER                       │
│         (Akses Penuh + Approval Final)      │
│                     │                       │
│              👔 ATASAN                      │
│         (Approval + Full Edit)              │
│                     │                       │
│              👨‍💼 ADMIN                       │
│         (Approval + Limited Edit)           │
│                     │                       │
│              👷 TEKNISI                     │
│         (Input Transaksi Only)              │
│                                             │
└─────────────────────────────────────────────┘
```

### Prinsip Hierarki

1. **Role lebih tinggi** memiliki semua akses role di bawahnya
2. **Approval** mengikuti aturan nominal dan hierarki
3. **Edit Protection** berlaku untuk semua role pada transaksi `completed`

---

## Teknisi

### 👷 Deskripsi Role

Teknisi adalah **pengguna lapangan** yang mengajukan reimbursement dan pengajuan pembelian untuk kebutuhan operasional.

### Tugas Utama

| Tugas | Deskripsi |
|-------|-----------|
| **Mengajukan Rembush** | Upload nota belanja yang sudah dibayar sendiri untuk direimbursement |
| **Mengajukan Pengajuan** | Membuat pengajuan pembelian sebelum membeli barang |
| **Konfirmasi Cash** | Konfirmasi penerimaan uang cash via Telegram Bot |
| **Monitoring Status** | Melihat status transaksi yang diajukan |

### Hak Akses

✅ **Bisa:**
- Membuat transaksi Rembush
- Membuat transaksi Pengajuan
- Melihat transaksi yang diajukan sendiri
- Melihat detail transaksi sendiri
- Menerima notifikasi status transaksi
- Konfirmasi pembayaran cash via Telegram

❌ **Tidak Bisa:**
- Melihat transaksi user lain
- Mengedit transaksi setelah submit
- Approve/reject transaksi
- Mengakses dashboard analytics
- Mengelola cabang, kategori, atau user
- Membuat transaksi Gudang

### Alur Kerja Harian

```
1. Belanja untuk kebutuhan operasional
   ↓
2. Simpan nota/struk
   ↓
3. Upload nota via sistem (Rembush)
   ↓
4. Tunggu OCR selesai (2-5 menit)
   ↓
5. Review & submit transaksi
   ↓
6. Tunggu approval Admin/Atasan
   ↓
7. Jika approved, tunggu pembayaran
   ↓
8. Konfirmasi terima uang (jika cash)
```

### Tips untuk Teknisi

💡 **Foto nota yang baik:** Pastikan nota jelas, tidak blur, dan semua teks terbaca. Lihat [Tips Foto Nota](03_REMBUSH_REIMBURSEMENT.md#tips-foto-nota-yang-baik).

💡 **Submit segera:** Nota maksimal 2 hari kalender. Lewat dari itu akan auto-reject.

💡 **Lengkapi data:** Pastikan kategori dan alokasi cabang terisi dengan benar.

💡 **Cek notifikasi:** Aktifkan notifikasi Telegram untuk update real-time.

---

## Admin

### 👨‍💼 Deskripsi Role

Admin adalah **pengelola operasional** yang mereview, menyetujui, dan memproses pembayaran transaksi.

### Tugas Utama

| Tugas | Deskripsi |
|-------|-----------|
| **Review Transaksi** | Memeriksa kelengkapan dan validitas transaksi |
| **Approve/Reject** | Menyetujui atau menolak transaksi < Rp 1.000.000 |
| **Upload Bukti Bayar** | Upload struk transfer atau konfirmasi cash |
| **Kelola Cabang** | Menambah, edit, atau hapus cabang |
| **Kelola Kategori** | Menambah, edit, atau nonaktifkan kategori |
| **Input Gudang** | Mencatat belanja gudang internal |
| **Limited Edit Pengajuan** | Edit alokasi cabang pada transaksi `waiting_payment` |

### Hak Akses

✅ **Bisa:**
- Semua akses Teknisi
- Melihat semua transaksi
- Approve/reject transaksi < Rp 1.000.000
- Upload bukti pembayaran
- Edit alokasi cabang (Limited Edit)
- Mengelola cabang
- Mengelola kategori
- Membuat transaksi Gudang
- Mengakses dashboard analytics
- Melihat activity log

❌ **Tidak Bisa:**
- Approve transaksi ≥ Rp 1.000.000 (perlu Owner)
- Full edit item/harga pada Pengajuan
- Mengelola rekening bank (read-only)
- Mengelola user
- Force Approve atau Override
- Mengakses Price Index

### Limited Edit vs Full Edit

**Limited Edit (Admin):**
- ✅ Edit alokasi cabang (persentase, manual)
- ✅ Edit metode distribusi (Equal, Percentage, Manual)
- ❌ Edit item, harga, DPP, PPN (Read-only)

**Full Edit (Atasan/Owner):**
- ✅ Edit semua field termasuk item & harga

### Alur Kerja Harian

```
1. Cek dashboard untuk transaksi pending
   ↓
2. Review detail transaksi
   ↓
3. Verifikasi nota dan data
   ↓
4. Approve (jika valid) atau Reject (jika tidak valid)
   ↓
5. Upload bukti pembayaran
   ↓
6. Monitoring status pembayaran
   ↓
7. Input transaksi Gudang (jika ada)
```

### Tips untuk Admin

💡 **Review teliti:** Periksa kesesuaian nota dengan data yang diinput.

💡 **Komunikasi:** Jika ada yang tidak jelas, hubungi Teknisi via Telegram sebelum reject.

💡 **Prioritas:** Proses transaksi urgent terlebih dahulu (lihat tanggal submit).

💡 **Bukti bayar:** Upload bukti transfer yang jelas untuk verifikasi AI.

---

## Atasan

### 👔 Deskripsi Role

Atasan adalah **supervisor operasional** dengan akses approval dan full edit untuk transaksi.

### Tugas Utama

| Tugas | Deskripsi |
|-------|-----------|
| **Review & Approve** | Menyetujui atau menolak transaksi < Rp 1.000.000 |
| **Full Edit Pengajuan** | Merevisi item, harga, dan alokasi pada Pengajuan |
| **Monitoring** | Memantau pengeluaran per cabang |
| **Input Transaksi** | Membuat transaksi Gudang dan Pengajuan |

### Hak Akses

✅ **Bisa:**
- Semua akses Admin
- **Full Edit** pada Pengajuan (item, harga, DPP, PPN)
- Membuat transaksi Pengajuan
- Membuat transaksi Gudang

❌ **Tidak Bisa:**
- Approve transaksi ≥ Rp 1.000.000 (perlu Owner)
- Mengelola rekening bank (read-only)
- Mengelola user
- Force Approve atau Override
- Mengakses Price Index

### Full Edit Privilege

Atasan dapat melakukan **Full Edit** pada Pengajuan dengan fitur:

1. **Dual-Version System:** Perubahan dicatat sebagai "Versi Management"
2. **Transparency:** User dapat melihat perbandingan versi asli vs revisi
3. **Audit Trail:** Semua perubahan tercatat di activity log

### Alur Kerja Harian

```
1. Cek dashboard untuk transaksi pending
   ↓
2. Review detail transaksi
   ↓
3. Edit Pengajuan jika perlu (Full Edit)
   ↓
4. Approve atau Reject
   ↓
5. Monitoring pengeluaran per cabang
   ↓
6. Input transaksi Gudang/Pengajuan (jika ada)
```

### Tips untuk Atasan

💡 **Edit dengan bijak:** Gunakan Full Edit hanya jika benar-benar diperlukan.

💡 **Komunikasi:** Informasikan ke Teknisi jika ada perubahan signifikan.

💡 **Monitoring:** Gunakan dashboard untuk memantau tren pengeluaran.

💡 **Dual-Version:** Manfaatkan fitur perbandingan versi untuk transparansi.

---

## Owner

### 👑 Deskripsi Role

Owner adalah **pemilik perusahaan** dengan akses penuh dan approval final untuk semua transaksi.

### Tugas Utama

| Tugas | Deskripsi |
|-------|-----------|
| **Final Approval** | Menyetujui transaksi ≥ Rp 1.000.000 |
| **Force Approve** | Memulihkan transaksi yang di-flag AI |
| **Override** | Memulihkan transaksi auto-reject |
| **Monitoring Penuh** | Memantau pengeluaran seluruh perusahaan |
| **Kelola Rekening** | Mengelola rekening bank cabang |
| **Price Index** | Mereview anomali harga dan referensi harga |
| **Kelola User** | Menambah, edit, atau hapus user |

### Hak Akses

✅ **Bisa:**
- **Semua akses** Atasan
- Approve **semua transaksi** (termasuk ≥ Rp 1.000.000)
- **Force Approve** transaksi flagged
- **Override** transaksi auto-reject
- Mengelola **rekening bank** cabang
- Mengelola **user** (semua role)
- Mengakses **Price Index** & anomali harga
- Melihat **audit trail** lengkap

❌ **Tidak Bisa:**
- Edit transaksi `completed` (Edit Protection berlaku untuk semua role)
- Edit transaksi dalam fase pelunasan (`isSettlementPhase`)

### Fitur Khusus Owner

#### 1. Force Approve

Digunakan untuk memulihkan transaksi yang di-flag AI karena selisih nominal:

```
Status: flagged
Alasan: Selisih Rp 50.000 antara struk vs transaksi

[Force Approve dengan Alasan]
Alasan: Biaya admin bank Rp 50.000
```

#### 2. Override

Digunakan untuk memulihkan transaksi auto-reject karena nota > 2 hari:

```
Status: auto-reject
Alasan: Nota berumur 3 hari

[Override dengan Alasan]
Alasan: Teknisi sakit, baru bisa submit hari ini
```

#### 3. Price Index

Dashboard khusus untuk mereview anomali harga:

```
🚨 Anomali Harga Terdeteksi

Item: Kabel LAN Cat6
Harga Input: Rp 150.000
Referensi Max: Rp 100.000
Selisih: +50% (Critical)

[Review] [Approve] [Reject]
```

### Alur Kerja Harian

```
1. Cek dashboard untuk overview pengeluaran
   ↓
2. Review transaksi ≥ Rp 1.000.000
   ↓
3. Approve atau Reject
   ↓
4. Review anomali harga (Price Index)
   ↓
5. Force Approve/Override jika diperlukan
   ↓
6. Monitoring hutang antar cabang
   ↓
7. Review activity log untuk audit
```

### Tips untuk Owner

💡 **Delegasi:** Percayakan transaksi < Rp 1.000.000 ke Admin/Atasan.

💡 **Focus on Big Picture:** Fokus pada transaksi besar dan anomali harga.

💡 **Price Index:** Gunakan Price Index untuk menjaga efisiensi anggaran.

💡 **Audit Trail:** Review activity log secara berkala untuk deteksi fraud.

💡 **Force Approve:** Gunakan dengan bijak dan selalu berikan alasan yang jelas.

---

## Tabel Perbandingan Akses

| Fitur | Teknisi | Admin | Atasan | Owner |
|-------|:-------:|:-----:|:------:|:-----:|
| **Input Transaksi** |
| Rembush | ✅ | ✅ | ✅ | ✅ |
| Pengajuan | ✅ | ✅ | ✅ | ✅ |
| Gudang | ❌ | ✅ | ✅ | ✅ |
| **Approval** |
| < Rp 1 Jt | ❌ | ✅ | ✅ | ✅ |
| ≥ Rp 1 Jt | ❌ | ❌ | ❌ | ✅ |
| **Edit Transaksi** |
| Limited Edit | ❌ | ✅ | ✅ | ✅ |
| Full Edit | ❌ | ❌ | ✅ | ✅ |
| **Fitur Khusus** |
| Force Approve | ❌ | ❌ | ❌ | ✅ |
| Override | ❌ | ❌ | ❌ | ✅ |
| **Manajemen** |
| Cabang | ❌ | ✅ | ✅ | ✅ |
| Kategori | ❌ | ✅ | ✅ | ✅ |
| Rekening Bank | ❌ | 👁️ | 👁️ | ✅ |
| User | ❌ | ❌ | ❌ | ✅ |
| **Analytics** |
| Dashboard | ❌ | ✅ | ✅ | ✅ |
| Price Index | ❌ | ❌ | ❌ | ✅ |
| Activity Log | ❌ | ✅ | ✅ | ✅ |

**Keterangan:**
- ✅ = Akses Penuh
- 👁️ = Read-Only
- ❌ = Tidak Ada Akses

---

## Hierarki Approval

### Aturan Approval Berdasarkan Nominal

```
┌─────────────────────────────────────────────┐
│                                             │
│  Transaksi < Rp 1.000.000                  │
│  ┌───────────────────────────────────┐     │
│  │ Admin/Atasan Approve               │     │
│  │         ↓                          │     │
│  │  Status: waiting_payment           │     │
│  │         ↓                          │     │
│  │  Upload Invoice                    │     │
│  │         ↓                          │     │
│  │  Status: completed                 │     │
│  └───────────────────────────────────┘     │
│                                             │
│  Transaksi ≥ Rp 1.000.000                  │
│  ┌───────────────────────────────────┐     │
│  │ Admin/Atasan Approve               │     │
│  │         ↓                          │     │
│  │  Status: approved                  │     │
│  │         ↓                          │     │
│  │  Owner Approve                     │     │
│  │         ↓                          │     │
│  │  Status: waiting_payment           │     │
│  │         ↓                          │     │
│  │  Upload Invoice                    │     │
│  │         ↓                          │     │
│  │  Status: completed                 │     │
│  └───────────────────────────────────┘     │
│                                             │
└─────────────────────────────────────────────┘
```

### Contoh Kasus

#### Kasus 1: Transaksi Rp 500.000

```
1. Teknisi submit → Status: pending
2. Admin approve → Status: waiting_payment
3. Admin upload invoice → Status: completed
```

#### Kasus 2: Transaksi Rp 2.500.000

```
1. Teknisi submit → Status: pending
2. Admin approve → Status: approved
3. Owner approve → Status: waiting_payment
4. Admin upload invoice → Status: completed
```

#### Kasus 3: Transaksi dengan Hutang Cabang

```
1. Teknisi submit → Status: pending
2. Admin approve → Status: waiting_payment
3. Admin upload invoice → Status: waiting_payment (masih ada hutang)
4. Admin lunasi hutang → Status: completed
```

---

## FAQ

### Q: Apakah satu user bisa memiliki lebih dari satu role?

**A:** Tidak. Satu akun hanya bisa memiliki satu role. Jika perlu akses berbeda, hubungi Owner untuk membuat akun baru.

---

### Q: Apakah Admin bisa approve transaksi sendiri?

**A:** Ya, Admin bisa approve transaksi yang diajukan sendiri jika nominal < Rp 1.000.000. Namun, untuk transparansi, sebaiknya di-review oleh Admin lain atau Atasan.

---

### Q: Apa bedanya Admin dan Atasan?

**A:** 
- **Admin:** Limited Edit (hanya alokasi cabang)
- **Atasan:** Full Edit (termasuk item & harga)

---

### Q: Apakah Owner harus approve semua transaksi?

**A:** Tidak. Owner hanya perlu approve transaksi ≥ Rp 1.000.000. Transaksi < Rp 1.000.000 bisa di-handle oleh Admin/Atasan.

---

### Q: Apa itu Force Approve dan kapan digunakan?

**A:** Force Approve adalah fitur khusus Owner untuk memulihkan transaksi yang di-flag AI karena selisih nominal. Digunakan jika selisih tersebut valid (misal: biaya admin bank).

---

### Q: Apa itu Override dan kapan digunakan?

**A:** Override adalah fitur khusus Owner untuk memulihkan transaksi auto-reject karena nota > 2 hari. Digunakan jika ada alasan valid (misal: teknisi sakit).

---

### Q: Apakah Owner bisa edit transaksi completed?

**A:** Tidak. Edit Protection berlaku untuk **semua role** termasuk Owner. Transaksi `completed` tidak bisa diedit untuk menjaga integritas audit.

---

### Q: Bagaimana cara mengubah role user?

**A:** Hanya Owner yang bisa mengubah role user. Hubungi Owner via Telegram atau email.

---

### Q: Apakah Teknisi bisa melihat transaksi user lain?

**A:** Tidak. Teknisi hanya bisa melihat transaksi yang diajukan sendiri.

---

### Q: Apakah Admin bisa mengelola user?

**A:** Tidak. Hanya Owner yang bisa mengelola user (CRUD).

---

## 📚 Dokumentasi Terkait

- **Sebelumnya:** [Cara Memulai](01_MEMULAI.md) - Login dan navigasi dasar
- **Untuk Teknisi:** [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md) - Cara mengajukan reimbursement
- **Untuk Admin:** [Panduan Approval](06_APPROVAL_TRANSAKSI.md) - Cara menyetujui transaksi
- **Untuk Owner:** [Price Index](13_PRICE_INDEX.md) - Sistem referensi harga

---

**Butuh Bantuan?**  
📧 Email: support@whusnet.com  
💬 Telegram: @WhusnetSupport  
📱 WhatsApp: +62 xxx-xxxx-xxxx

---

**Last Updated:** 21 Mei 2026  
**Version:** 1.0  
**Maintainer:** WHUSNET IT Team
