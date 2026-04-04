<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Merge `purchase_reason` into `category` column for Pengajuan transactions.
 *
 * Strategy:
 * - The `category` column (string) already exists and is used by Rembush.
 * - Pengajuan uses `purchase_reason` (string key like 'persediaan', 'peralatan').
 * - We migrate Pengajuan rows: set category = human-readable name from the old key map.
 * - Then drop `purchase_reason` column.
 *
 * Backward compatibility for items JSON is handled via Model Accessor (not here).
 */
return new class extends Migration
{
    // The old static mapping (keys → labels) from Transaction::PURCHASE_REASONS
    private array $purchaseReasonMap = [
        'persediaan'          => 'Persediaan',
        'peralatan'           => 'Peralatan',
        'perlengkapan'        => 'Perlengkapan',
        'cadangan'            => 'Cadangan',
        'kebutuhan_rutin'     => 'Kebutuhan Rutin',
        'perawatan'           => 'Perawatan',
        'ekspansi'            => 'Ekspansi',
        'perbaikan'           => 'Mengganti Barang Rusak/Perbaikan',
        'upgrade'             => 'Upgrade',
        'inventaris'          => 'Inventaris',
        'marketing'           => 'Marketing',
        'perizinan'           => 'Perizinan',
        'kelengkapan_teknisi' => 'Kelengkapan Teknisi/Penunjang Teknisi',
        'linsensi'            => 'Lisensi',
        'optimalisasi_sistem' => 'Optimalisasi Sistem',
        'vendor'              => 'Vendor',
        'efisien_kerja'       => 'Meningkatkan Efisien Kerja (AI)',
        'lainnya'             => 'Lainnya',
    ];

    // The old static mapping for Rembush CATEGORIES (keys → labels)
    private array $categoryMap = [
        'biaya_marketing'                    => 'Biaya Marketing',
        'beban_entertain'                    => 'Beban Entertain',
        'beban_komisi'                       => 'Beban Komisi',
        'beban_bensin_parkir_tol_kendaraan'  => 'Beban Bensin, Parkir, Tol Kendaraan',
        'beban_gaji_upah_honorar'            => 'Beban Gaji, Upah & Honorar',
        'beban_pertemuan'                    => 'Beban Pertemuan',
        'beban_konsumsi'                     => 'Beban Konsumsi',
        'beban_listrik'                      => 'Beban Listrik',
        'beban_perlengkapan_kantor'          => 'Beban Perlengkapan Kantor',
        'beban_perawatan_dan_perbaikan'      => 'Beban Perawatan dan Perbaikan',
        'beban_repeter'                      => 'Beban Repeter',
        'beban_lain_lain'                    => 'Beban Lain-lain',
        'beban_ai'                           => 'Beban AI',
        'beban_administrasi_bank'            => 'Beban Administrasi Bank',
        'beban_ekspedisi_pos_materai'        => 'Beban Ekspedisi, Pos & Materai',
        'beban_sewa'                         => 'Beban Sewa',
        'beban_tagihan_bpjs_ketenagakerjaan' => 'Beban Tagihan BPJS Ketenagakerjaan',
        'beban_pembayaran_bpjs_kesehatan'    => 'Beban Pembayaran BPJS Kesehatan',
        'beban_seragam_karyawan'             => 'Beban Seragam Karyawan',
        'beban_promosi_iklan'                => 'Beban Promosi/Iklan',
        'beban_kebersihan_dan_keamanan'      => 'Beban Kebersihan dan Keamanan',
        'beban_konsultan'                    => 'Beban Konsultan',
        'pph_final'                          => 'PPH Final',
        'pph_21'                             => 'PPH 21',
        'beban_sumbangan_amal'               => 'Beban Sumbangan / Amal',
        'beban_telekomunikasi'               => 'Beban Telekomunikasi',
        'pembelian_internet'                 => 'Pembelian Internet',
        'peralatan'                          => 'Peralatan',
        'persediaan'                         => 'Persediaan',
        'piutang_usaha'                      => 'Piutang Usaha',
        'piutang_karyawan'                   => 'Piutang Karyawan',
        'prive'                              => 'Prive',
        'diskon_penjualan'                   => 'Diskon Penjualan',
        'kendaraan'                          => 'Kendaraan',
        'retur_penjualan'                    => 'Retur Penjualan',
        'utang_usaha'                        => 'Utang Usaha',
        'perlengkapan'                       => 'Perlengkapan',
        'beban_operasional_lainnya'          => 'Beban Operasional Lainnya',
        'beban_vendor'                       => 'Beban Vendor',
        'bagi_hasil'                         => 'Bagi Hasil',
    ];

    public function up(): void
    {
        // ── 1. Migrate Pengajuan: purchase_reason (key) → category (label) ──
        $pengajuanRows = DB::table('transactions')
            ->where('type', 'pengajuan')
            ->whereNotNull('purchase_reason')
            ->get(['id', 'purchase_reason']);

        foreach ($pengajuanRows as $row) {
            $label = $this->purchaseReasonMap[$row->purchase_reason]
                ?? $row->purchase_reason; // fallback: keep as-is

            DB::table('transactions')
                ->where('id', $row->id)
                ->update(['category' => $label]);
        }

        // ── 2. Migrate Rembush: category (key) → category (label) ──
        // Rembush rows that still store short keys instead of labels
        $rembushRows = DB::table('transactions')
            ->where('type', 'rembush')
            ->whereNotNull('category')
            ->get(['id', 'category']);

        foreach ($rembushRows as $row) {
            // Only convert if the value looks like a snake_case key
            if (isset($this->categoryMap[$row->category])) {
                DB::table('transactions')
                    ->where('id', $row->id)
                    ->update(['category' => $this->categoryMap[$row->category]]);
            }
        }

        // ── 3. Drop purchase_reason column ──
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('purchase_reason');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('purchase_reason')->nullable()->after('estimated_price');
        });
        // Note: restored column will be empty; data cannot be fully restored.
    }
};
