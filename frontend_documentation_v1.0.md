# Front-End (FE) Documentation
**Version 1.0 | Laravel Blade + Tailwind CSS + Vite**
*Dokumentasi Antarmuka Pengguna, Komponen, dan Integrasi Client-Side*

---

## 1. Arsitektur Front-End

Proyek ini menggunakan model **Monolith with Modern Tooling**:
- **Templating**: Laravel Blade.
- **Styling**: Tailwind CSS (v4).
- **Asset Bundler**: Vite.
- **Interactivity**: Vanilla JavaScript (AJAX/Fetch) & Laravel Echo (WebSocket).
- **Icons**: Lucide Icons.

### 1.1 Struktur Folder Utama
```text
resources/
├── css/
│   └── app.css          # Tailwind base & custom design tokens
├── js/
│   ├── app.js           # Entry point JS
│   ├── bootstrap.js     # Axios & Echo configuration
│   └── echo.js          # Laravel Echo initialization
└── views/
    ├── layouts/
    │   └── app.blade.php # Layout utama (Navbar/Sidebar)
    ├── transactions/    # Modul Transaksi & OCR
    └── dashboard/       # Dashboard & Analytics
```

---

## 2. Design System & Style Guide

### 2.1 Palet Warna & Tema
Sistem menggunakan gradien modern untuk elemen premium:
- **Primary**: Indigo (`via-indigo-600`) & Purple (`to-purple-600`).
- **Success/Accent**: Teal (`to-teal-500`) & Emerald.
- **Background**: Slate-50 ke Slate-100 (Gradient).
- **Gradients**: Sering digunakan pada button, badge, dan logo container.

### 2.2 Tipografi
- **Font Face**: Inter (Google Fonts).
- **Scale**: Menggunakan standard Tailwind text scaling (`text-sm`, `text-xl`, `text-6xl`).
- **Styles**: Font-weight `black` (900) untuk title dan `extrabold` (800) untuk penekanan.

### 2.3 Animasi
Didefinisikan di `app.css`:
- `animate-fade-in-up`: Transisi masuk dari bawah.
- `animate-slide-in`: Transisi masuk dari kanan (untuk toast).
- `badgePulse`: Animasi berdenyut untuk notifikasi badge.

---

## 3. Komponen UI Reusable

### 3.1 Layout Navigation
Layout bersifat responsif dan berubah berdasarkan Role:
- **Teknisi**: Top Navbar (untuk fokus input cepat).
- **Admin/Atasan/Owner**: Sidebar (untuk pengelolaan data intensif).

### 3.2 Modal System
Implementasi modal menggunakan transition CSS untuk efek smooth:
- **ID**: `choiceModal`, `loadingOverlay`, `globalDragOverlay`.
- **Logic**: Menggunakan `hidden` class toggle dan opacity transition (10ms delay untuk trigger).

### 3.3 Notification Badge
Komponen badge yang sinkron di desktop, mobile, dan sidebar:
- **Selector**: `#notif-count-desktop`, `#notif-count-mobile`.
- **Behavior**: Otomatis tersembunyi jika count = 0.

---

## 4. Integrasi API & Alur Data

### 4.1 Client-Side Request
- **AXIOS**: Digunakan untuk request AJAX standar. Header `X-Requested-With` disetel ke `XMLHttpRequest`.
- **CSRF**: Token diambil dari `<meta name="csrf-token">`.

### 4.2 Real-time Updates (Pusher/Echo)
Client mendengarkan event di private channel:
1. `ocr.{userId}`: Menampilkan toast otomatis saat proses OCR Gemini selesai.
2. `notifications.{userId}`: Mengupdate badge counter secara real-time.
3. `transactions`: Update grid transaksi tanpa refresh (untuk Admin/Owner).

### 4.3 Polling (Fallback/Status)
- **OCR Status**: Halaman loading melakukan polling ke `/api/ai/auto-fill/status/{uploadId}`.
- **Notif Count**: `updateNotificationBadge()` melakukan fetch unread count saat load halaman pertama kali.

---

## 5. Panduan Pengembangan

### 5.1 Menjalankan Local Dev
1. Install dependencies: `npm install`.
2. Jalankan Vite: `npm run dev`.
3. Link storage: `php artisan storage:link` (Penting agar file upload/preview muncul).

### 5.2 Menambahkan Komponen Baru
1. Gunakan Tailwind classes (hindari inline style berlebihan).
2. Daftarkan event listener di `DOMContentLoaded` di dalam `@push('scripts')`.
3. Gunakan `lucide.createIcons()` jika menambahkan icon baru secara dinamis.

---
*Dokumentasi FE v1.0*
