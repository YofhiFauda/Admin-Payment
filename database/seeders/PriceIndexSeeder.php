<?php

namespace Database\Seeders;

use App\Models\PriceIndex;
use App\Models\User;
use Illuminate\Database\Seeder;

class PriceIndexSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::where('role', 'owner')->first();

        $masterData = [
            ['item_name' => 'Kabel UTP Cat6 305m', 'category' => 'Persediaan', 'avg' => 1200000, 'unit' => 'roll'],
            ['item_name' => 'Konektor RJ45 (Isi 100)', 'category' => 'Persediaan', 'avg' => 150000, 'unit' => 'box'],
            ['item_name' => 'MikroTik CCR1009', 'category' => 'Peralatan', 'avg' => 6500000, 'unit' => 'unit'],
            ['item_name' => 'MikroTik RB1100AHx4', 'category' => 'Peralatan', 'avg' => 4500000, 'unit' => 'unit'],
            ['item_name' => 'Isolasi Listrik Nitto', 'category' => 'Perlengkapan', 'avg' => 12000, 'unit' => 'pcs'],
            ['item_name' => 'Kabel Ties 20cm (Isi 100)', 'category' => 'Perlengkapan', 'avg' => 35000, 'unit' => 'pack'],
            ['item_name' => 'Hardisk 4TB WD Purple', 'category' => 'Cadangan', 'avg' => 1800000, 'unit' => 'unit'],
            ['item_name' => 'RAM 16GB DDR4 Server', 'category' => 'Cadangan', 'avg' => 1200000, 'unit' => 'pcs'],
            ['item_name' => 'Token Listrik Server', 'category' => 'Kebutuhan Rutin', 'avg' => 2000000, 'unit' => 'bulan'],
            ['item_name' => 'Biaya Internet Biznet', 'category' => 'Kebutuhan Rutin', 'avg' => 3500000, 'unit' => 'bulan'],
            ['item_name' => 'Jasa Cleaning AC Server', 'category' => 'Perawatan', 'avg' => 150000, 'unit' => 'unit'],
            ['item_name' => 'Pemeliharaan Genset', 'category' => 'Perawatan', 'avg' => 850000, 'unit' => 'kunjungan'],
            ['item_name' => 'Kabel Fiber Optic 1000m', 'category' => 'Ekspansi', 'avg' => 3200000, 'unit' => 'haspel'],
            ['item_name' => 'Tiang Telkom 7 Meter', 'category' => 'Ekspansi', 'avg' => 950000, 'unit' => 'batang'],
            ['item_name' => 'Splicer Fujikura (Ganti)', 'category' => 'Mengganti Barang Rusak/Perbaikan', 'avg' => 15000000, 'unit' => 'unit'],
            ['item_name' => 'Switch Hub 24 Port Gigabit', 'category' => 'Mengganti Barang Rusak/Perbaikan', 'avg' => 1800000, 'unit' => 'unit'],
            ['item_name' => 'Server Dell R740', 'category' => 'Upgrade', 'avg' => 35000000, 'unit' => 'unit'],
            ['item_name' => 'UPS APC 3000VA', 'category' => 'Upgrade', 'avg' => 8500000, 'unit' => 'unit'],
            ['item_name' => 'Kursi Fiber Teknisi', 'category' => 'Inventaris', 'avg' => 350000, 'unit' => 'pcs'],
            ['item_name' => 'Meja Kerja Staff', 'category' => 'Inventaris', 'avg' => 750000, 'unit' => 'pcs'],
            ['item_name' => 'Brosur Paket Internet', 'category' => 'Marketing', 'avg' => 250000, 'unit' => 'rim'],
            ['item_name' => 'Spanduk Banner 3x1', 'category' => 'Marketing', 'avg' => 90000, 'unit' => 'pcs'],
            ['item_name' => 'Izin Galian FO', 'category' => 'Perizinan', 'avg' => 5000000, 'unit' => 'paket'],
            ['item_name' => 'Retribusi BTS Tahunan', 'category' => 'Perizinan', 'avg' => 12000000, 'unit' => 'tahun'],
            ['item_name' => 'Tang Potong Proskit', 'category' => 'Kelengkapan Teknisi/Penunjang Teknisi', 'avg' => 95000, 'unit' => 'pcs'],
            ['item_name' => 'Helm Proyek Teknisi', 'category' => 'Kelengkapan Teknisi/Penunjang Teknisi', 'avg' => 60000, 'unit' => 'pcs'],
            ['item_name' => 'Lisensi CPanel', 'category' => 'Lisensi', 'avg' => 450000, 'unit' => 'bulan'],
            ['item_name' => 'Lisensi WHMCS', 'category' => 'Lisensi', 'avg' => 280000, 'unit' => 'bulan'],
            ['item_name' => 'API Gateway Premium', 'category' => 'Optimalisasi Sistem', 'avg' => 900000, 'unit' => 'bulan'],
            ['item_name' => 'Vendor Pemasangan Tiang', 'category' => 'Vendor', 'avg' => 150000, 'unit' => 'titik'],
            ['item_name' => 'Langganan n8n Cloud', 'category' => 'Meningkatkan Efisien Kerja (AI)', 'avg' => 350000, 'unit' => 'bulan'],
            ['item_name' => 'Langganan ChatGPT Plus', 'category' => 'Meningkatkan Efisien Kerja (AI)', 'avg' => 320000, 'unit' => 'bulan'],
        ];

        foreach ($masterData as $data) {
            PriceIndex::create([
                'master_item_id'     => \App\Models\MasterItem::firstOrCreate([
                    'canonical_name' => \App\Models\MasterItem::normalize($data['item_name'])
                ], [
                    'display_name'   => $data['item_name'],
                    'category'       => $data['category'],
                    'status'         => 'active'
                ])->id,
                'item_name'          => $data['item_name'],
                'category'           => $data['category'],
                'unit'               => $data['unit'],
                'min_price'          => $data['avg'] * 0.95, // 5% di bawah rata-rata
                'avg_price'          => $data['avg'],
                'max_price'          => $data['avg'] * 1.10, // 10% di atas rata-rata (Threshold Anomali)
                'is_manual'          => true,
                'manual_set_by'      => $owner ? $owner->id : null,
                'manual_set_at'      => now(),
                'manual_reason'      => 'Standarisasi harga pusat 2026',
                'needs_initial_review'=> false,
                'total_transactions' => rand(10, 50),
                'last_calculated_at' => now(),
            ]);
        }

        $this->command->info('✅ Master Price Index berhasil dibuat sebagai referensi pasar.');
    }
}