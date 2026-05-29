# Changelog: Dropdown Filter Per Page

## Tanggal: 26 Mei 2026

### Fitur Baru: Dropdown Filter Jumlah Data Per Halaman

Menambahkan dropdown filter untuk mengatur jumlah data yang ditampilkan per halaman dengan format:
**"Menampilkan [ 20 ▼ ] dari 40.000 transaksi"**

---

## Perubahan File

### 1. **resources/views/transactions/partials/index/pagination.blade.php**
- ✅ Mengganti format "Menampilkan X - Y dari Z transaksi" menjadi "Menampilkan [dropdown] dari Z transaksi"
- ✅ Menambahkan dropdown `<select>` dengan opsi: 20, 50, 100
- ✅ Styling dropdown dengan Tailwind CSS dan ikon chevron-down dari Lucide
- ✅ Memindahkan posisi teks dan dropdown ke sisi kiri (order-2 sm:order-1)
- ✅ Export button dan pagination tetap di sisi kanan (order-1 sm:order-2)

### 2. **resources/js/transactions/search-engine.js**
- ✅ Menambahkan state `perPage` yang dinamis (default: 20)
- ✅ Update fungsi `updateShowingText()` untuk sync dengan dropdown value
- ✅ Menambahkan fungsi `changePerPage(newPerPage)` untuk handle perubahan per page
- ✅ Update semua referensi `Config.ui.itemsPerPage` menjadi variabel `perPage`
- ✅ Menambahkan `changePerPage` ke public API
- ✅ Format angka total dengan `toLocaleString('id-ID')` untuk pemisah ribuan

### 3. **resources/js/transactions/config.js**
- ✅ Menambahkan `perPageOptions: [20, 50, 100]` ke `Config.ui`

### 4. **resources/js/transactions/main.js**
- ✅ Event listener untuk dropdown `#per-page-select` sudah ada (baris 289-308)
- ✅ Sync per_page dengan URL parameter saat load (baris 277-286)
- ✅ Update URL parameter saat dropdown berubah
- ✅ Trigger `SearchEngine.applyFilters(true)` untuk reload data

---

## Cara Kerja

### Client-Side Mode (< 5000 records)
1. User memilih opsi dari dropdown (20/50/100)
2. Event listener menangkap perubahan
3. `SearchEngine.changePerPage()` dipanggil
4. State `perPage` diupdate
5. `totalPages` dihitung ulang
6. `renderPage()` dipanggil untuk render ulang dengan pagination baru

### Server-Side Mode (≥ 5000 records)
1. User memilih opsi dari dropdown
2. Event listener menangkap perubahan
3. `SearchEngine.changePerPage()` dipanggil
4. State `perPage` diupdate
5. `loadServerSideData()` dipanggil dengan parameter `per_page` baru
6. Server mengembalikan data sesuai limit baru

---

## Testing

### Manual Testing Checklist
- [ ] Dropdown muncul dengan benar di desktop dan mobile
- [ ] Opsi 20, 50, 100 tersedia
- [ ] Perubahan dropdown memicu reload data
- [ ] Total transaksi ditampilkan dengan format ribuan (contoh: 40.000)
- [ ] Pagination update sesuai dengan per_page yang dipilih
- [ ] URL parameter `per_page` tersimpan saat dropdown berubah
- [ ] Refresh halaman mempertahankan pilihan per_page dari URL
- [ ] Client-side mode (< 5k records) bekerja dengan benar
- [ ] Server-side mode (≥ 5k records) bekerja dengan benar

### Browser Testing
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari
- [ ] Mobile Chrome
- [ ] Mobile Safari

---

## Screenshot Lokasi Perubahan

```
┌─────────────────────────────────────────────────────────────┐
│  Menampilkan [ 20 ▼ ] dari 40.000 transaksi    [Export] [Pagination] │
└─────────────────────────────────────────────────────────────┘
     ↑                    ↑
  Dropdown          Total dengan
  per page       format ribuan
```

---

## Rollback Instructions

Jika perlu rollback, kembalikan file-file berikut ke commit sebelumnya:
1. `resources/views/transactions/partials/index/pagination.blade.php`
2. `resources/js/transactions/search-engine.js`
3. `resources/js/transactions/config.js`

Kemudian jalankan:
```bash
npm run build
```

---

## Notes

- Dropdown menggunakan native `<select>` HTML untuk kompatibilitas maksimal
- Styling menggunakan Tailwind CSS sesuai design system yang ada
- Ikon chevron menggunakan Lucide Icons yang sudah digunakan di seluruh aplikasi
- Format angka menggunakan `toLocaleString('id-ID')` untuk format Indonesia (titik sebagai pemisah ribuan)
- Perubahan per_page disimpan di URL parameter untuk persistensi saat refresh
