# Laporan Analisis Bug UI/UX - WHUSNET Admin Payment

Berdasarkan investigasi pada codebase, berikut adalah analisa mendalam mengenai bug yang dilaporkan dan usulan perbaikannya.

## 1. Bug Loading Form (Rembush & Pengajuan)
### Deskripsi Masalah:
Halaman loading (`transactions/loading.blade.php`) dapat di-scroll dan teks tidak selalu berada tepat di tengah (center) secara vertikal/horizontal pada semua kondisi. Hal ini terjadi karena halaman tersebut menggunakan `min-h-screen` dan berada di dalam layout `teknisi` yang memiliki scroll body aktif.

### Analisa Teknis:
*   **File:** `resources/views/transactions/loading.blade.php`
*   **Penyebab:** Penggunaan `min-h-screen` pada container utama dan tidak adanya mekanisme pengunci scroll (`overflow-hidden`) pada elemen `body` saat halaman loading ditampilkan.
*   **Dampak:** User bisa melakukan scroll ke bawah/atas saat proses OCR berlangsung, merusak estetik "AI Scanning".

### Usulan Perbaikan:
*   Ganti `min-h-screen` dengan `fixed inset-0 h-screen w-screen z-[100]`.
*   Tambahkan `overflow-hidden` pada container utama untuk memastikan tidak ada scrollbar yang muncul.
*   Pastikan menggunakan `flex items-center justify-center` untuk menjaga konten tetap di tengah viewport.

---

## 2. Bug Detail Transaksi (Modal & Scroll Chaining)
### Deskripsi Masalah:
1.  Modal detail hanya terbuka sebagian (tidak full/proporsional).
2.  *Scroll Chaining*: Saat melakukan scroll di dalam modal, halaman daftar transaksi di belakangnya ikut bergeser (scroll).
3.  Image Preview muncul terpotong dari bawah.

### Analisa Teknis:
*   **File:** `resources/views/transactions/index.blade.php`
*   **Penyebab:**
    *   `max-h-[90vh]` pada modal content mungkin terlalu kecil atau bertabrakan dengan padding container pada device tertentu.
    *   Tidak ada logic JavaScript untuk menambahkan `overflow: hidden` pada `document.body` saat modal terbuka.
    *   `image-viewer` menggunakan `max-h-[85vh]` yang jika digabung dengan padding dan margin bisa menyebabkan gambar terpotong atau posisi tidak sentral.
*   **Layout:** Layout `teknisi` di `app.blade.php` tidak memiliki wrapper `h-screen overflow-hidden` seperti layout admin, sehingga scroll body selalu aktif.

### Usulan Perbaikan:
*   **Body Scroll Lock:** Tambahkan fungsi JS `document.body.style.overflow = 'hidden'` saat modal dibuka dan kembalikan ke `''` saat ditutup.
*   **Modal Sizing:** Gunakan `h-[95vh]` atau `h-full` pada mobile untuk modal detail agar terlihat lebih penuh.
*   **Fixed Header/Footer:** Pastikan header dan footer modal menggunakan `shrink-0` dan area konten menggunakan `overflow-y-auto grow`.

---

## 3. Bug Horizontal Scroll (Galaxy A12 / Mobile)
### Deskripsi Masalah:
Halaman dapat di-geser ke kanan/kiri (horizontal scroll) yang seharusnya tidak terjadi pada desain responsive.

### Analisa Teknis:
*   **Penyebab Umum:** Penggunaan `w-screen` atau `100vw` yang tidak memperhitungkan lebar scrollbar, atau adanya elemen dengan padding/margin negatif yang keluar dari container.
*   **File Terkait:** `resources/views/layouts/app.blade.php` dan `resources/views/transactions/form.blade.php`.
*   **Spesifik:** Tabel pada "Daftar Barang" menggunakan `whitespace-nowrap` yang memaksa lebar tabel melebihi layar jika datanya panjang, meskipun sudah ada `overflow-x-auto`.

### Usulan Perbaikan:
*   Pastikan container utama menggunakan `max-w-full overflow-x-hidden`.
*   Hindari penggunaan `vw` unit untuk lebar elemen jika elemen tersebut memiliki padding/border.
*   Gunakan `break-words` atau `truncate` pada elemen teks yang berpotensi memanjang.

---

## 4. Bug Toast Mobile (Terlalu Besar)
### Deskripsi Masalah:
Notifikasi Toast memakan terlalu banyak ruang di layar HP.

### Analisa Teknis:
*   **File:** `resources/views/layouts/app.blade.php`
*   **Penyebab:** Container `toast-container-stack` memiliki `max-w-sm` (384px) yang hampir memenuhi lebar layar HP kecil. Padding `p-4` dan icon yang besar (`w-12 h-12`) memperparah keadaan.

### Usulan Perbaikan:
*   Gunakan media query atau utility Tailwind `sm:max-w-sm` dan set default `max-w-[calc(100vw-2rem)]` untuk mobile.
*   Kecilkan ukuran icon dan padding pada view mobile menggunakan prefix `sm:`.

---

## 5. Bug Daftar Barang Rembush (Mobile View)
### Deskripsi Masalah:
Tabel Daftar Barang sulit diisi/dilihat di perangkat mobile karena format tabel yang menyamping.

### Analisa Teknis:
*   **File:** `resources/views/transactions/form.blade.php`
*   **Penyebab:** Menggunakan elemen `<table>` standar yang tidak responsif secara layout (hanya scroll horizontal).
*   **Dampak:** User harus geser kanan-kiri untuk mengisi harga atau menghapus item, yang sangat tidak efisien di layar kecil.

### Usulan Perbaikan:
*   **Card Layout for Mobile:** Gunakan teknik *Stacked Cards* pada layar mobile (hidden table, show cards) atau gunakan CSS untuk mengubah `display: table` menjadi `display: block` pada media query tertentu.
*   Setiap baris barang diubah menjadi satu kartu (card) yang berisi input Nama, Qty, Harga dalam susunan vertikal.

---

## Kesimpulan Strategi Perbaikan
1.  **Global:** Perbaiki layout `teknisi` agar konsisten dalam menangani modal dan scroll.
2.  **JS Helper:** Buat fungsi global untuk `toggleBodyScroll(lock)`.
3.  **Refactoring Form:** Ubah input tabel menjadi responsive cards pada `form.blade.php`.
4.  **CSS Audit:** Hapus penggunaan `w-screen` yang tidak perlu untuk menghilangkan horizontal scroll.