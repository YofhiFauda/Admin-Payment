# 🚀 Cara Memulai - WHUSNET Admin Payment

**Untuk Siapa:** Semua Pengguna  
**Waktu Baca:** ~7 menit  
**Level:** Pemula

---

## 📋 Daftar Isi

- [Mengakses Sistem](#mengakses-sistem)
- [Login Pertama Kali](#login-pertama-kali)
- [Mengenal Dashboard](#mengenal-dashboard)
- [Navigasi Dasar](#navigasi-dasar)
- [Mengubah Password](#mengubah-password)
- [Tips untuk Pengguna Baru](#tips-untuk-pengguna-baru)
- [Troubleshooting Login](#troubleshooting-login)
- [FAQ](#faq)

---

## Mengakses Sistem

### URL Akses

Sistem WHUSNET Admin Payment dapat diakses melalui:

```
🌐 https://admin-payment.whusnet.com
```

### Persyaratan Browser

Untuk pengalaman terbaik, gunakan browser modern:

| Browser | Versi Minimum | Status |
|---------|---------------|--------|
| **Google Chrome** | 90+ | ✅ Direkomendasikan |
| **Mozilla Firefox** | 88+ | ✅ Direkomendasikan |
| **Microsoft Edge** | 90+ | ✅ Direkomendasikan |
| **Safari** | 14+ | ✅ Didukung |
| **Opera** | 76+ | ✅ Didukung |

⚠️ **Catatan:** Internet Explorer tidak didukung.

### Persyaratan Koneksi

- **Koneksi Internet:** Stabil (minimal 1 Mbps)
- **Akses Mobile:** Responsif untuk smartphone & tablet
- **Notifikasi Real-time:** Memerlukan WebSocket (port 8081)

---

## Login Pertama Kali

### Langkah 1: Buka Halaman Login

1. Buka browser Anda
2. Ketik URL: `https://admin-payment.whusnet.com`
3. Anda akan melihat halaman login seperti ini:

```
┌─────────────────────────────────────────┐
│                                         │
│     🏢 WHUSNET Admin Payment           │
│                                         │
│     ┌─────────────────────────────┐   │
│     │ Email                       │   │
│     │ [________________]          │   │
│     │                             │   │
│     │ Password                    │   │
│     │ [________________] 👁       │   │
│     │                             │   │
│     │ Pilih Role                  │   │
│     │ [▼ Pilih Role Anda]        │   │
│     │                             │   │
│     │  [    🔐 Login    ]        │   │
│     │                             │   │
│     │  ☐ Ingat Saya              │   │
│     └─────────────────────────────┘   │
│                                         │
└─────────────────────────────────────────┘
```

### Langkah 2: Masukkan Kredensial

1. **Email:** Masukkan email yang diberikan oleh Admin
   - Format: `nama@whusnet.com`
   - Contoh: `budi.teknisi@whusnet.com`

2. **Password:** Masukkan password Anda
   - Klik icon 👁 untuk melihat password yang diketik
   - Password bersifat case-sensitive (huruf besar/kecil berbeda)

3. **Pilih Role:** Pilih peran Anda dari dropdown
   - Teknisi
   - Admin
   - Atasan
   - Owner

### Langkah 3: Klik Tombol Login

Setelah semua terisi, klik tombol **"🔐 Login"**.

### Langkah 4: Verifikasi Role

Sistem akan memverifikasi bahwa role yang Anda pilih sesuai dengan akun Anda.

✅ **Berhasil:** Anda akan diarahkan ke halaman sesuai role Anda  
❌ **Gagal:** Pesan error akan muncul (lihat [Troubleshooting](#troubleshooting-login))

---

## Mengenal Dashboard

Setelah login berhasil, Anda akan melihat tampilan berbeda tergantung role:

### Dashboard Teknisi

```
┌────────────────────────────────────────────────────────┐
│  🏢 WHUSNET Admin Payment          👤 Budi (Teknisi)  │
├────────────────────────────────────────────────────────┤
│                                                        │
│  📋 Transaksi Saya                                    │
│  ┌──────────────────────────────────────────────┐    │
│  │ Status      │ Jumlah │ Aksi                  │    │
│  ├──────────────────────────────────────────────┤    │
│  │ ⏳ Pending   │   3    │ [Lihat Detail]       │    │
│  │ ✅ Disetujui │   12   │ [Lihat Detail]       │    │
│  │ ❌ Ditolak   │   1    │ [Lihat Detail]       │    │
│  └──────────────────────────────────────────────┘    │
│                                                        │
│  [➕ Buat Rembush Baru]  [➕ Buat Pengajuan Baru]    │
│                                                        │
└────────────────────────────────────────────────────────┘
```

### Dashboard Admin/Atasan/Owner

```
┌────────────────────────────────────────────────────────┐
│  🏢 WHUSNET Admin Payment            👤 Andi (Admin)  │
├────────────────────────────────────────────────────────┤
│                                                        │
│  📊 Statistik Transaksi (Bulan Ini)                   │
│  ┌──────────────────────────────────────────────┐    │
│  │  Total: 45  │  Pending: 8  │  Approved: 35  │    │
│  │  Rejected: 2  │  Total Nilai: Rp 25.500.000  │    │
│  └──────────────────────────────────────────────┘    │
│                                                        │
│  💰 Rincian Biaya per Cabang                          │
│  [Filter: Mei 2026 ▼]                                 │
│  ┌──────────────────────────────────────────────┐    │
│  │ Cabang A    │ Rp 8.500.000  │ [Detail]      │    │
│  │ Cabang B    │ Rp 12.200.000 │ [Detail]      │    │
│  │ Cabang C    │ Rp 4.800.000  │ [Detail]      │    │
│  └──────────────────────────────────────────────┘    │
│                                                        │
│  ⏳ Transaksi Menunggu Approval                       │
│  [Lihat Semua Pending]                                │
│                                                        │
└────────────────────────────────────────────────────────┘
```

### Elemen Dashboard Umum

| Elemen | Fungsi |
|--------|--------|
| **Header Bar** | Menampilkan logo, nama sistem, dan info user |
| **Sidebar Menu** | Navigasi utama ke berbagai fitur |
| **Notifikasi Bell** 🔔 | Menampilkan notifikasi real-time |
| **User Menu** 👤 | Akses ke profil dan logout |
| **Content Area** | Area utama untuk konten halaman |

---

## Navigasi Dasar

### Sidebar Menu

Menu yang tersedia berbeda untuk setiap role:

#### Menu Teknisi

```
📋 Transaksi Saya
   ├─ Daftar Transaksi
   ├─ Buat Rembush
   └─ Buat Pengajuan

🔔 Notifikasi

👤 Profil Saya
   ├─ Lihat Profil
   └─ Ubah Password

🚪 Logout
```

#### Menu Admin/Atasan/Owner

```
📊 Dashboard

📋 Transaksi
   ├─ Semua Transaksi
   ├─ Pending Approval
   ├─ Buat Rembush
   ├─ Buat Pengajuan
   └─ Buat Gudang (Admin/Owner)

🏢 Manajemen
   ├─ Cabang
   ├─ Rekening Bank
   ├─ Kategori
   └─ Pengguna (Admin/Owner)

📊 Laporan
   ├─ Dashboard Analytics
   ├─ Price Index (Owner)
   └─ Activity Log

🔔 Notifikasi

👤 Profil Saya
   ├─ Lihat Profil
   └─ Ubah Password

🚪 Logout
```

### Cara Navigasi

1. **Klik Menu:** Klik item di sidebar untuk membuka halaman
2. **Breadcrumb:** Gunakan breadcrumb di atas konten untuk navigasi cepat
3. **Tombol Kembali:** Gunakan tombol "← Kembali" untuk kembali ke halaman sebelumnya
4. **Search:** Gunakan fitur pencarian untuk menemukan transaksi cepat

---

## Mengubah Password

### Langkah-langkah

1. **Buka Menu Profil**
   - Klik icon 👤 di pojok kanan atas
   - Pilih "Ubah Password"

2. **Isi Form**
   ```
   ┌─────────────────────────────────┐
   │ Password Lama                   │
   │ [___________________] 👁        │
   │                                 │
   │ Password Baru                   │
   │ [___________________] 👁        │
   │                                 │
   │ Konfirmasi Password Baru        │
   │ [___________________] 👁        │
   │                                 │
   │  [Batal]  [💾 Simpan]          │
   └─────────────────────────────────┘
   ```

3. **Klik Simpan**
   - Sistem akan memvalidasi password lama
   - Password baru harus minimal 8 karakter
   - Konfirmasi harus sama dengan password baru

4. **Logout Otomatis**
   - Setelah berhasil, Anda akan logout otomatis
   - Login kembali dengan password baru

### Syarat Password yang Kuat

✅ **DO:**
- Minimal 8 karakter
- Kombinasi huruf besar & kecil
- Tambahkan angka
- Tambahkan simbol (!@#$%^&*)
- Contoh: `Whusnet2026!`

❌ **DON'T:**
- Password terlalu pendek (< 8 karakter)
- Password sama dengan email
- Password umum (password123, 12345678)
- Tanggal lahir atau nama sendiri

---

## Tips untuk Pengguna Baru

### 1. Pahami Role Anda

Setiap role memiliki akses dan tanggung jawab berbeda. Baca [Panduan Peran Pengguna](02_PERAN_PENGGUNA.md) untuk detail lengkap.

### 2. Aktifkan Notifikasi

Pastikan notifikasi browser diaktifkan untuk mendapat update real-time:

```
Browser akan menampilkan popup:
"admin-payment.whusnet.com ingin mengirim notifikasi"

Klik: [Izinkan] ✅
```

### 3. Bookmark Halaman

Simpan URL sistem di bookmark browser untuk akses cepat.

### 4. Gunakan "Ingat Saya"

Centang "☑ Ingat Saya" saat login agar tidak perlu login berulang kali (kecuali di komputer publik).

### 5. Jelajahi Menu

Luangkan 5-10 menit untuk mengeksplorasi menu yang tersedia sesuai role Anda.

### 6. Baca Dokumentasi

Baca dokumentasi sesuai tugas Anda:
- **Teknisi:** [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md) & [Panduan Pengajuan](04_PENGAJUAN_PEMBELIAN.md)
- **Admin:** [Panduan Approval](06_APPROVAL_TRANSAKSI.md) & [Panduan Pembayaran](07_PEMBAYARAN.md)
- **Owner:** [Dashboard Analytics](09_DASHBOARD_ANALYTICS.md) & [Price Index](13_PRICE_INDEX.md)

### 7. Hubungi Support Jika Bingung

Jangan ragu untuk menghubungi support via Telegram @WhusnetSupport jika ada yang tidak jelas.

---

## Troubleshooting Login

### Masalah 1: "Email atau Password Salah"

**Penyebab:**
- Email salah ketik
- Password salah ketik
- Caps Lock aktif

**Solusi:**
1. Periksa kembali email Anda (huruf besar/kecil, spasi)
2. Klik icon 👁 untuk melihat password yang diketik
3. Pastikan Caps Lock tidak aktif
4. Jika lupa password, hubungi Admin untuk reset

### Masalah 2: "Role Tidak Sesuai"

**Penyebab:**
- Memilih role yang tidak sesuai dengan akun Anda

**Solusi:**
1. Pilih role yang benar sesuai dengan akun Anda
2. Jika tidak yakin, tanyakan ke Admin
3. Satu akun hanya bisa memiliki satu role

### Masalah 3: "Akun Terkunci"

**Penyebab:**
- Terlalu banyak percobaan login gagal (5x berturut-turut)

**Solusi:**
1. Tunggu 15 menit sebelum mencoba lagi
2. Atau hubungi Admin untuk unlock akun

### Masalah 4: Halaman Tidak Muncul / Blank

**Penyebab:**
- Browser tidak didukung
- JavaScript dinonaktifkan
- Koneksi internet lambat

**Solusi:**
1. Gunakan browser yang didukung (Chrome, Firefox, Edge)
2. Pastikan JavaScript diaktifkan
3. Refresh halaman (F5 atau Ctrl+R)
4. Clear cache browser
5. Coba browser lain

### Masalah 5: Notifikasi Tidak Muncul

**Penyebab:**
- Notifikasi browser diblokir

**Solusi:**
1. Klik icon 🔒 di address bar
2. Cari "Notifications" atau "Notifikasi"
3. Ubah ke "Allow" atau "Izinkan"
4. Refresh halaman

---

## FAQ

### Q: Apakah saya bisa login dari HP?

**A:** Ya! Sistem ini responsif dan bisa diakses dari smartphone atau tablet. Gunakan browser mobile seperti Chrome atau Safari.

---

### Q: Apakah saya bisa login dari beberapa perangkat sekaligus?

**A:** Ya, Anda bisa login dari komputer, HP, dan tablet secara bersamaan. Namun, untuk keamanan, logout setelah selesai menggunakan di perangkat publik.

---

### Q: Berapa lama session login bertahan?

**A:** 
- **Tanpa "Ingat Saya":** 2 jam (atau sampai browser ditutup)
- **Dengan "Ingat Saya":** 30 hari

---

### Q: Apakah saya bisa mengubah email login?

**A:** Tidak bisa mengubah sendiri. Hubungi Admin jika perlu mengubah email.

---

### Q: Apa yang harus dilakukan jika lupa password?

**A:** Hubungi Admin via Telegram atau email untuk reset password. Admin akan memberikan password sementara yang harus Anda ubah setelah login.

---

### Q: Apakah ada batasan jumlah login gagal?

**A:** Ya, setelah 5x login gagal berturut-turut, akun akan terkunci selama 15 menit untuk keamanan.

---

### Q: Bagaimana cara logout?

**A:** Klik menu "🚪 Logout" di sidebar, atau klik icon 👤 di pojok kanan atas lalu pilih "Logout".

---

### Q: Apakah data saya aman?

**A:** Ya! Sistem menggunakan enkripsi HTTPS, password di-hash, dan memiliki audit trail lengkap untuk setiap aktivitas.

---

## 📚 Dokumentasi Terkait

- **Selanjutnya:** [Peran Pengguna](02_PERAN_PENGGUNA.md) - Pahami role dan akses Anda
- **Untuk Teknisi:** [Panduan Rembush](03_REMBUSH_REIMBURSEMENT.md) - Cara mengajukan reimbursement
- **Untuk Admin:** [Panduan Approval](06_APPROVAL_TRANSAKSI.md) - Cara menyetujui transaksi
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
