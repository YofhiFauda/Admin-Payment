# Analisis Ketidaksesuaian Perhitungan Alokasi Transaksi INV-20260421-00004

## 1. Detail Transaksi
Berdasarkan data yang diberikan, berikut adalah rincian perhitungan Total Tagihan:

| Komponen | Nominal | Operasi |
| :--- | :--- | :--- |
| Estimasi Awal | Rp 3.800.000 | (Base) |
| Ongkir | Rp 200.000 | (+) |
| Diskon Pengiriman | Rp 20.000 | (-) |
| Voucher Diskon | Rp 20.000 | (-) |
| Biaya Layanan 1 | Rp 20.000 | (+) |
| Biaya Layanan 2 | Rp 20.000 | (+) |
| **Total Tagihan** | **Rp 4.000.000** | **(Target)** |

## 2. Analisis Masalah
Masalah muncul ketika Total Tagihan dibagikan secara merata ke **6 Cabang**.

### A. Perhitungan Persentase
Sistem menghitung persentase alokasi per cabang:
`100% / 6 cabang = 16,666...%`
Nilai ini dibulatkan oleh sistem menjadi **16,67%**.

### B. Perhitungan Nominal Alokasi di Frontend
Kode di `resources/views/transactions/index.blade.php` menggunakan rumus:
`alloc = Math.round((TotalTagihan * Persentase) / 100)`

Maka untuk setiap cabang:
`alloc = Math.round((4.000.000 * 16,67) / 100) = Rp 666.800`

### C. Akumulasi Total Sumber Dana
`Total = 6 * Rp 666.800 = Rp 4.000.800`

### D. Selisih (Discrepancy)
`Total Sumber Dana (4.000.800) - Total Tagihan (4.000.000) = Rp 800`
Inilah penyebab munculnya alert: **"Kelebihan Rp 800 dari Total Tagihan"**.

## 3. Akar Masalah (Root Cause)
1. **Pembulatan Persentase Berjenjang**: Penggunaan persentase yang sudah dibulatkan (`16,67%`) alih-alih pembagian presisi.
2. **Ketiadaan Logika Remainder (Sisa)**: Frontend menghitung tiap cabang secara independen tanpa memastikan total akhir sesuai dengan target.

## 4. Rancangan Solusi Universal (The Last Penny Principle)
Solusi ini adalah standar industri keuangan untuk menangani sisa pembagian (Penny Problem) agar akurasi mencapai 100% pada kasus apa pun (pembagi 3, 6, 7, dll).

### A. Strategi Distribusi Sisa (Remainder Absorption)
Alih-alih menghitung tiap cabang dengan persentase, sistem harus menggunakan akumulasi untuk menyerap selisih pembulatan:
1. Hitung alokasi dasar per cabang menggunakan pembagian bulat.
2. Simpan sisa pembagian (modulus).
3. Tambahkan sisa tersebut ke cabang terakhir.

### B. Contoh Implementasi (Target 4.000.000 / 6 Cabang)
1. `BaseAlloc = Math.floor(4.000.000 / 6) = 666.666`
2. `TotalDistributedSoFar = 666.666 * 5 = 3.333.330`
3. `LastBranchAlloc = 4.000.000 - 3.333.330 = 666.670`
4. **Total Akhir: 3.333.330 + 666.670 = 4.000.000 (PAS)**

### C. Draft Perbaikan Logika Frontend (JavaScript)
Logika pada fungsi `calculateSumberDanaTotal` di `index.blade.php` harus diubah menjadi:

```javascript
let totalAllocated = 0;
const checkboxes = document.querySelectorAll('.sd-checkbox');
const totalBranches = checkboxes.length;

checkboxes.forEach((cb, index) => {
    let alloc = 0;
    if (index === totalBranches - 1) {
        // Cabang terakhir menyerap semua sisa agar total pas
        alloc = finalTotalTarget - totalAllocated;
    } else {
        // Cabang lainnya menggunakan pembagian rata
        alloc = Math.floor(finalTotalTarget / totalBranches);
    }
    totalAllocated += alloc;
    // Update UI dengan nilai 'alloc' yang baru...
});
```

## 5. Keuntungan Solusi
1. **General & Skalabel**: Berlaku untuk jumlah cabang berapa pun (3, 6, 7, 9, dll).
2. **Konsistensi Data**: Menyamakan logika Frontend dengan Backend yang sudah memiliki mekanisme pengaman serupa.
3. **Zero Discrepancy**: Menghilangkan alert "Kelebihan/Kekurangan Rp 1" yang sering mengganggu user pada angka-angka ganjil.

---
*Laporan ini diperbarui untuk mencakup solusi teknis universal bagi sistem alokasi multi-cabang.*
