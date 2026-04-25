# 🚀 Panduan Migrasi MPA ke HTMX SPA - WHUSNET Admin Payment

Untuk mengubah sistem Admin Payment yang saat ini berupa Multi-Page Application (MPA) menjadi Single Page Application (SPA) menggunakan **HTMX**, kita tidak perlu merombak seluruh framework frontend (tidak perlu Vue/React). HTMX memungkinkan kita mendapatkan feel SPA hanya dengan HTML Attributes.

Berikut adalah daftar komponen dan halaman yang **perlu dirubah**:

---

## 1. Layout Utama (`resources/views/layouts/app.blade.php`)
Ini adalah pondasi utama SPA. Kita harus menyiapkan "wadah" untuk konten yang akan di-swap oleh HTMX.

*   **Install HTMX**: Tambahkan script HTMX di `<head>`.
*   **Wadah Konten (Target)**: Beri ID pada tag `<main>` (misal: `<main id="main-content">`).
*   **Ubah Navigasi**: Semua link menu di Sidebar (Admin) dan Navbar (Teknisi) harus diubah.
    *   *Sebelum*: `<a href="{{ route('dashboard') }}">`
    *   *Sesudah*: `<a hx-get="{{ route('dashboard') }}" hx-target="#main-content" hx-push-url="true" hx-indicator="#nprogress">`
*   **Integrasi Loading (NProgress)**: Hubungkan event HTMX dengan NProgress agar loading bar muncul saat pindah halaman.
    ```javascript
    document.addEventListener('htmx:beforeRequest', () => NProgress.start());
    document.addEventListener('htmx:afterRequest', () => NProgress.done());
    ```

---

## 2. Sistem Controller & Routing (Backend)
HTMX bekerja dengan meminta **sebagian HTML (Partial)**, bukan seluruh halaman utuh. Oleh karena itu, backend harus cerdas merespon.

*   **Middleware / Macro**: Jika request datang dari HTMX (cek header `HX-Request: true`), maka Laravel hanya boleh mengembalikan isi dari `@section('content')`, **TANPA** menyertakan `layouts/app.blade.php` (tanpa tag `<html>`, `<head>`, sidebar, dll).
*   **Cara Termudah (Laravel Fragment)**:
    ```php
    // Di Controller:
    if (request()->header('HX-Request')) {
        return view('transactions.index', $data)->fragment('content');
    }
    return view('transactions.index', $data);
    ```

---

## 3. Halaman yang Perlu Dirubah (Views)
Hampir semua halaman di dalam `resources/views/` perlu disesuaikan, terutama yang memiliki Form dan Pagination.

### A. Form Submit (Create & Edit)
Halaman yang memiliki form `POST`/`PUT` harus menggunakan attribute HTMX agar tidak full-reload saat disubmit.
*   **Halaman Terpengaruh**:
    *   `transactions/create.blade.php` & form-form di dalamnya.
    *   `users/create.blade.php` & `users/edit.blade.php`
    *   `branches/index.blade.php` (form rekening cabang)
    *   `pengeluaran-lain/gaji/create.blade.php` (dan form pengeluaran lain)
    *   `price-index/index.blade.php` (form referensi harga)
*   **Perubahan Form**:
    *   *Sebelum*: `<form method="POST" action="/users">`
    *   *Sesudah*: `<form hx-post="/users" hx-target="#main-content" hx-push-url="true">`

### B. List Data & Filter (Index)
Form pencarian dan filter harus melakukan AJAX request.
*   **Halaman Terpengaruh**:
    *   `transactions/index.blade.php` (Catatan: Ini sudah pakai AJAX kustom, bisa distandarisasi pakai HTMX).
    *   `users/index.blade.php` (Search form)
    *   `pengeluaran-lain/*/index.blade.php`
*   **Perubahan Filter**:
    *   *Sebelum*: `<form method="GET" action="...">`
    *   *Sesudah*: `<form hx-get="..." hx-target="#main-content" hx-trigger="submit, input delay:500ms from:input[type='search']">` (Bisa auto-search saat ngetik).

### C. Link Pindah Halaman Internal
Link yang berada di dalam konten halaman (seperti tombol "Lihat Detail", "Edit", "Kembali") harus diubah ke format HTMX.
*   *Sebelum*: `<a href="/users/1/edit" class="btn">Edit</a>`
*   *Sesudah*: `<a hx-get="/users/1/edit" hx-target="#main-content" hx-push-url="true">Edit</a>`

---

## 4. Penyesuaian JavaScript & DOM
Ini adalah bagian paling *tricky* saat migrasi SPA.

*   **Masalah `DOMContentLoaded`**: Script yang berjalan saat halaman pertama kali diload tidak akan jalan lagi saat pindah halaman via HTMX.
*   **Solusi**: Semua inisialisasi plugin JS (seperti *Lucide Icons*, Modal listeners, SearchEngine kustom) harus dibungkus dalam event `htmx:load` atau `htmx:afterSwap`.
    ```javascript
    // Ganti ini:
    document.addEventListener('DOMContentLoaded', initApp);
    
    // Menjadi ini:
    document.addEventListener('htmx:load', initApp);
    ```

## Urutan Eksekusi Jika Ingin Dimulai:
1.  **Tahap 1**: Pasang HTMX di Layout utama dan ubah semua navigasi Sidebar/Navbar.
2.  **Tahap 2**: Buat Middleware/Trait di backend untuk merender view secara partial (menggunakan fitur Blade Fragment) jika ada header `HX-Request`.
3.  **Tahap 3**: Konversi Form satu persatu (mulai dari form simpel seperti manajemen User/Cabang, lalu ke Transaksi).
4.  **Tahap 4**: Perbaiki event JavaScript yang hilang setelah navigasi.
