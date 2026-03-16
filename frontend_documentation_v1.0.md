# Front-End (FE) Documentation
**Version 4.5 | Laravel Blade + Tailwind CSS v4 + Vite**
*Dokumentasi Antarmuka Pengguna, Komponen, dan Integrasi Client-Side*

---

## 1. Arsitektur Front-End

Proyek ini menggunakan model **Monolith with Modern Tooling**:
- **Templating**: Laravel Blade.
- **Styling**: Tailwind CSS (v4) — Menggunakan modern CSS engine.
- **Asset Bundler**: Vite 6.x.
- **Interactivity**: Vanilla JavaScript (AJAX/Fetch) & Laravel Echo (WebSocket via Reverb).
- **Icons**: Lucide Icons.

### 1.1 Struktur Folder Utama
```text
resources/
├── css/
│   └── app.css          # Tailwind base & custom design tokens
├── js/
│   ├── app.js           # Entry point JS
│   ├── bootstrap.js     # Axios & Echo configuration
│   └── echo.js          # Laravel Echo initialization (Reverb)
└── views/
    ├── layouts/
    │   └── app.blade.php # Layout utama (Sidebar/Navbar)
    ├── transactions/    # Modul Transaksi (Rembush, Pengajuan)
    ├── dashboard/       # Dashboard & Analytics Charts
    └── components/      # Reusable Blade Components (Modal, Badge, Button)
```

---

## 2. Design System & Style Guide

### 2.1 Palet Warna & Tema (Tailwind v4)
Sistem menggunakan gradien modern untuk elemen premium:
- **Gradients**: `bg-linear-to-r from-indigo-600 to-purple-600` untuk header dan tombol utama.
- **Cards**: Background `bg-white/80` dengan backdrop-blur untuk efek Glassmorphism.
- **Badges**: 
  - `completed`: Green/Emerald.
  - `flagged/rejected`: Red/Rose.
  - `pending/waiting`: Amber/Orange.

### 2.2 Tipografi & Ikon
- **Font**: Inter (Google Fonts).
- **Icons**: Lucide Icons (Ringan, konsisten, dan mudah diintegrasikan).

### 2.3 Animasi CSS
- `animate-fade-in-up`: Digunakan pada saat data transaksi muncul.
- `animate-slide-in`: Digunakan untuk toast notifikasi real-time.
- `badgePulse`: Animasi heartbeat pada badge notifikasi.

---

## 3. Komponen UI & Logic

### 3.1 Layout Responsif
- **Role Teknisi**: Sidebar minimalis atau top-nav untuk kemudahan upload di mobile.
- **Role Admin/Owner**: Sidebar lengkap dengan navigasi master data dan monitoring.

### 3.2 Modal & Overlay
- **Loading Overlay**: Muncul saat user upload nota dan menunggu polling OCR selesai.
- **Choice Modal**: Modal awal saat memilih antara "Rembush (OCR)" atau "Pengajuan (Manual)".

---

## 4. Integrasi Real-time (Laravel Echo)

Sistem menggunakan **Laravel Reverb** sebagai WebSocket server:
1. **OCR Processing**: User tidak perlu refresh. Toast akan muncul saat status OCR berubah dari `processing` ke `completed`.
2. **Notification Counter**: Badge notifikasi di sidebar/navbar update secara instan.
3. **Transaction Feed**: Daftar transaksi di dashboard Admin terupdate otomatis jika ada upload baru.
4. **Queue Monitoring**: Grafik antrian di dashboard Admin bersifat live (streaming data).

---

## 5. Alur Client-Side (Nota Upload)

1. **Upload**: User memilih file -> Submit AJAX via Axios.
2. **Loading Page**: User diarahkan ke `/rembush/loading` yang melakukan polling asinkron.
3. **Polling**: `setInterval` memanggil `/api/ai/auto-fill/status/{uploadId}` setiap 2 detik.
4. **Completion**: Jika status `completed`, user otomatis redirect ke `/rembush/form` dengan data yang sudah terisi (auto-filled).

---

## 6. Panduan Pengembangan

1. **Style Guide**: Gunakan Tailwind utility classes. Hindari custom CSS kecuali untuk animasi kompleks di `app.css`.
2. **Assets**: Jalankan `npm run dev` untuk development atau `npm run build` untuk produksi.
3. **Icons**: Panggil icon baru menggunakan atribut `data-lucide="name"` dan jalankan `lucide.createIcons()` jika render dinamis.

---
*Dokumentasi FE v4.5 | WHUSNET Frontend*
