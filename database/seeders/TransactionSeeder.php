<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'teknisi')->get();
        if ($users->isEmpty()) {
            $this->command->error('Tolong buat User dengan role teknisi terlebih dahulu sebelum menjalankan seeder ini.');
            return;
        }

        $branches = Branch::all();
        if ($branches->isEmpty()) {
            $this->command->error('Tolong buat Branch terlebih dahulu sebelum menjalankan seeder ini.');
            return;
        }

        $vendors = ['Toko Komputer Abadi', 'Mitra Elektrik', 'Global Tech', 'Bhinneka', 'Cahaya Utama'];
        
        // Fetch dynamic categories
        $categoriesRembush = TransactionCategory::forRembush()->pluck('name')->toArray();
        $reasonsPengajuan = TransactionCategory::forPengajuan()->pluck('name')->toArray();

        // Fallback for demo/seeder if table is empty
        if (empty($categoriesRembush)) {
            $categoriesRembush = ['Beban Operasional', 'Beban Gaji', 'Beban Listrik'];
        }
        if (empty($reasonsPengajuan)) {
            $reasonsPengajuan = ['Persediaan', 'Peralatan', 'Perawatan'];
        }

        // ─── 1. SEED 10 REMBUSH (AI_STATUS = COMPLETED) ───────────────────
        for ($i = 1; $i <= 100; $i++) {
            $amount = rand(50000, 2000000);
            $user = $users->random();

            $transaction = Transaction::create([
                'type' => Transaction::TYPE_REMBUSH,
                'invoice_number' => Transaction::generateInvoiceNumber(),
                'customer' => 'Customer/Toko Rembush ' . $i,
                'category' => $categoriesRembush[array_rand($categoriesRembush)],
                'description' => 'Pembelian dummy seeder ke-' . $i,
                'payment_method' => 'cash',
                'amount' => $amount,
                'items' => [
                    ['name' => 'Barang Dummy 1', 'price' => $amount * 0.4, 'quantity' => 1, 'total' => $amount * 0.4],
                    ['name' => 'Barang Dummy 2', 'price' => $amount * 0.6, 'quantity' => 1, 'total' => $amount * 0.6],
                    ['name' => 'Barang Dummy 2', 'price' => $amount * 0.6, 'quantity' => 1, 'total' => $amount * 0.6],
                    ['name' => 'Barang Dummy 2', 'price' => $amount * 0.6, 'quantity' => 1, 'total' => $amount * 0.6],
                    ['name' => 'Barang Dummy 2', 'price' => $amount * 0.6, 'quantity' => 1, 'total' => $amount * 0.6],
                ],
                'date' => Carbon::now()->subDays(rand(1, 30)),
                'file_path' => 'dummy/receipt.jpg', // Dummy file path
                'status' => 'pending',  // Siap di-confirm di dashboard
                'submitted_by' => $user->id,
                'ai_status' => 'completed', // ✅ TRIGGER PENTING: Melewati OCR
                'confidence' => rand(85, 99),
            ]);

            // Attach random branch
            $branch = $branches->random();
            $transaction->branches()->attach($branch->id, [
                'allocation_percent' => 100,
                'allocation_amount' => $amount,
            ]);
        }

        $this->command->info('✅ Berhasil membuat 100 data Rembush (siap konfirmasi, tanpa OCR).');

        // ─── 2. SEED 10 PENGAJUAN ──────────────────────────────────────────
        for ($i = 1; $i <= 100; $i++) {
            $estimatedPrice = rand(100000, 5000000);
            $qty = rand(1, 5);
            $totalEstimate = $estimatedPrice * $qty;
            $user = $users->random();

            $transaction = Transaction::create([
                'type' => Transaction::TYPE_PENGAJUAN,
                'invoice_number' => Transaction::generateInvoiceNumber(),
                'customer' => 'Nama Barang Pengajuan ' . $i,
                'vendor' => $vendors[array_rand($vendors)],
                'specs' => [
                    'merk' => 'Merek ' . Str::random(3),
                    'tipe' => 'Tipe ' . rand(100, 999),
                    'ukuran' => 'Standard',
                    'warna' => 'Hitam',
                ],
                'quantity' => $qty,
                'estimated_price' => $estimatedPrice,
                'category' => $reasonsPengajuan[array_rand($reasonsPengajuan)],
                'date' => Carbon::now(),
                'file_path' => null, // Pengajuan tidak wajib punya file gambar di awal
                'status' => 'pending', // Siap di view/approve admin
                'submitted_by' => $user->id,
            ]);

            // Attach random branches (Bagi rata ke 2 cabang)
            $selectedBranches = $branches->random(min(2, $branches->count()));
            $amountPerBranch = $totalEstimate / $selectedBranches->count();
            $percentPerBranch = 100 / $selectedBranches->count();

            foreach ($selectedBranches as $branch) {
                $transaction->branches()->attach($branch->id, [
                    'allocation_percent' => $percentPerBranch,
                    'allocation_amount' => $amountPerBranch,
                ]);
            }
        }

        $this->command->info('✅ Berhasil membuat 100 data Pengajuan (siap di-review admin).');
    }
}
