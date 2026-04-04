<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['rembush', 'pengajuan']);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'type']);
        });

        // ──── Seed from existing static constants ────
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

        $now = now();
        $rembushRows = array_map(fn($name, $idx) => [
            'name' => $name,
            'type' => 'rembush',
            'sort_order' => $idx,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rembushCategories, array_keys($rembushCategories));

        $pengajuanRows = array_map(fn($name, $idx) => [
            'name' => $name,
            'type' => 'pengajuan',
            'sort_order' => $idx,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $pengajuanCategories, array_keys($pengajuanCategories));

        DB::table('transaction_categories')->insert(array_merge($rembushRows, $pengajuanRows));
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_categories');
    }
};
