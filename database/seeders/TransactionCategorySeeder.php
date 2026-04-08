<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $rembushCategories = [
            'Biaya Marketing',
            'Beban Entertain',
            'Beban Komisi',
            'Beban Bensin, Parkir, Tol Kendaraan',
            'Beban Gaji, Upah & Honorar',
            'Beban Pertemuan',
            'Beban Konsumsi',
            'Beban Listrik',
            'Beban Perlengkapan Kantor',
            'Beban Perawatan dan Perbaikan',
            'Beban Repeter',
            'Beban Lain-lain',
            'Beban AI',
            'Beban Administrasi Bank',
            'Beban Ekspedisi, Pos & Materai',
            'Beban Sewa',
            'Beban Tagihan BPJS Ketenagakerjaan',
            'Beban Pembayaran BPJS Kesehatan',
            'Beban Seragam Karyawan',
            'Beban Promosi/Iklan',
            'Beban Kebersihan dan Keamanan',
            'Beban Konsultan',
            'PPH Final',
            'PPH 21',
            'Beban Sumbangan / Amal',
            'Beban Telekomunikasi',
            'Pembelian Internet',
            'Peralatan',
            'Persediaan',
            'Piutang Usaha',
            'Piutang Karyawan',
            'Prive',
            'Diskon Penjualan',
            'Kendaraan',
            'Retur Penjualan',
            'Utang Usaha',
            'Perlengkapan',
            'Beban Operasional Lainnya',
            'Beban Vendor',
            'Bagi Hasil',
        ];

        $pengajuanCategories = [
            'Persediaan',
            'Peralatan',
            'Perlengkapan',
            'Cadangan',
            'Kebutuhan Rutin',
            'Perawatan',
            'Ekspansi',
            'Mengganti Barang Rusak/Perbaikan',
            'Upgrade',
            'Inventaris',
            'Marketing',
            'Perizinan',
            'Kelengkapan Teknisi/Penunjang Teknisi',
            'Lisensi',
            'Optimalisasi Sistem',
            'Vendor',
            'Meningkatkan Efisien Kerja (AI)',
            'Lainnya',
        ];

        $legacyMap = [
            'Beban Bensin, Parkir, Tol Kendaraan' => 'beban_bensin_parkir_tol_kendaraan',
            'Beban Ekspedisi, Pos & Materai' => 'beban_ekspedisi_pos_materai',
            'Beban Sumbangan / Amal' => 'beban_sumbangan_amal',
            'Mengganti Barang Rusak/Perbaikan' => 'perbaikan',
            'Kelengkapan Teknisi/Penunjang Teknisi' => 'kelengkapan_teknisi',
            'Meningkatkan Efisien Kerja (AI)' => 'efisien_kerja',
        ];

        $now = now();
        $rembushRows = array_map(fn($name, $idx) => [
            'name' => $name,
            'code' => $legacyMap[$name] ?? Str::snake($name),
            'type' => 'rembush',
            'sort_order' => $idx,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rembushCategories, array_keys($rembushCategories));

        $pengajuanRows = array_map(fn($name, $idx) => [
            'name' => $name,
            'code' => $legacyMap[$name] ?? Str::snake($name),
            'type' => 'pengajuan',
            'sort_order' => $idx,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $pengajuanCategories, array_keys($pengajuanCategories));

        DB::table('transaction_categories')->insert(array_merge($rembushRows, $pengajuanRows));
    }
}
