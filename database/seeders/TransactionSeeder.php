<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\PriceIndex;
use App\Models\Transaction;
use App\Models\User;
use App\Models\PriceAnomaly;
use App\Services\IdGeneratorService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        // ─── SETUP USERS ────────────────────────────────
        $teknisi = User::updateOrCreate(
            ['email' => 'teknisi@whusnet.com'],
            [
                'name'     => 'Teknisi',
                'password' => Hash::make('password'),
                'role'     => 'teknisi'
            ]
        );

        $owner = User::where('role', 'owner')->first() ?? User::factory()->create(['role' => 'owner', 'email' => 'owner@whusnet.com', 'name' => 'Owner']);
        $atasan = User::where('role', 'atasan')->first() ?? User::factory()->create(['role' => 'atasan', 'email' => 'atasan@whusnet.com', 'name' => 'Atasan']);

        $branches = Branch::all();
        $priceIndexes = PriceIndex::all();

        if ($priceIndexes->isEmpty()) {
            $this->command->error('Jalankan PriceIndexSeeder terlebih dahulu!');
            return;
        }

        if ($branches->isEmpty()) {
            $this->command->error('Jalankan BranchSeeder atau pastikan ada data cabang!');
            return;
        }

        // ─── CLEANUP DUMMY DATA ─────────────────────────
        $this->command->info('🧹 Cleaning up previous dummy transactions...');
        Transaction::where('submitted_by', $teknisi->id)->delete();

        // ─── 1. SEED REMBUSH (MAKS 1.5JT) ───
        $this->command->info('⏳ Seeding 6,000 Rembush (Maks 1.500.000)...');
        Transaction::withoutEvents(function () use ($priceIndexes, $branches, $teknisi, $atasan) {
            DB::beginTransaction();
            for ($i = 1; $i <= 6000; $i++) {
            $pi = $priceIndexes->random();
            $qty = rand(1, 10);
            
            // Hitung harga satuan agar total tidak melebihi 1.5JT
            $maxPossiblePrice = floor(1500000 / $qty);
            $price = min($pi->avg_price * (rand(95, 105) / 100), $maxPossiblePrice);
            $total = floor($price * $qty);

            $date = Carbon::now()->subDays(rand(0, 30));
            $seq = $i; // 1 to 2000
            $invoiceNum = IdGeneratorService::buildUploadId($seq, $date->format('Ymd'));
            $traceId = 'TRX-' . strtoupper(Str::random(8));

            $sourceBranch = $branches->random();

            $trx = Transaction::create([
                'type'           => Transaction::TYPE_REMBUSH,
                'invoice_number' => $invoiceNum,
                'upload_id'      => $invoiceNum,
                'trace_id'       => $traceId,
                'customer'       => 'Toko ' . $pi->category . ' Sejahtera',
                'category'       => $pi->category,
                'description'    => "Pembelian " . $qty . " unit " . $pi->item_name . " untuk maintenance rutin unit di cabang.",
                'payment_method' => collect(array_keys(Transaction::PAYMENT_METHODS))->random(),
                'amount'         => $total,
                'items'          => [[
                    'name'     => $pi->item_name,
                    'price'    => $price,
                    'quantity' => $qty,
                    'total'    => $total,
                    'category' => $pi->category,
                ]],
                'date'           => $date,
                'status'         => 'completed',
                'submitted_by'   => $teknisi->id,
                'reviewed_by'    => $atasan->id,
                'reviewed_at'    => $date->copy()->addHours(2),
                'ai_status'      => 'completed',
                'confidence'     => rand(85, 100),
                'actual_total'   => $total,
                'expected_total' => $total,
                'selisih'        => 0,
                'file_path'      => 'receipts/dummy_rembush_' . $i . '.jpg',
                'sumber_dana_branch_id' => $sourceBranch->id,
                'sumber_dana_data'      => [['branch_id' => $sourceBranch->id, 'amount' => (int) $total]],
                'ocr_result'     => [
                    'merchant' => 'Toko ' . $pi->category . ' Sejahtera',
                    'items'    => [['desc' => $pi->item_name, 'qty' => $qty, 'price' => $price, 'total' => $total]],
                    'total'    => $total,
                    'date'     => $date->format('Y-m-d')
                ],
                'ocr_confidence'     => rand(90, 99),
                'overall_confidence' => rand(88, 98),
                'confidence_label'   => 'high',
                'field_confidence'   => [
                    'merchant' => 95,
                    'total'    => 99,
                    'date'     => 98,
                    'items'    => 90
                ]
            ]);

            // Alokasi Cabang (100% atau Split)
            if (rand(1, 10) > 8) {
                // Split ke 2 cabang
                $b1 = $branches->random();
                $b2 = $branches->where('id', '!=', $b1->id)->random();
                $trx->branches()->attach($b1->id, ['allocation_percent' => 60, 'allocation_amount' => floor($total * 0.6)]);
                $trx->branches()->attach($b2->id, ['allocation_percent' => 40, 'allocation_amount' => ceil($total * 0.4)]);
            } else {
                $trx->branches()->attach($branches->random()->id, [
                    'allocation_percent' => 100,
                    'allocation_amount'  => $total
                ]);
            }
            }
            DB::commit();
        });

        // ─── 2. SEED PENGAJUAN (MAKS 1.5JT & PRICE INDEX) ───
        $this->command->info('⏳ Seeding 12,000 Pengajuan (Lengkap dengan Berbagai Status)...');
        Transaction::withoutEvents(function () use ($priceIndexes, $branches, $teknisi, $owner, $atasan) {
            DB::beginTransaction();
            for ($i = 1; $i <= 12000; $i++) {
                $pi = $priceIndexes->random();
                $qty = rand(1, 5);

                $date = Carbon::now()->subDays(rand(0, 90));
                $seq = 10000 + $i; // 10001 to 22000 (Prevents collision with Rembush)
            $uploadId = IdGeneratorService::buildUploadId($seq, $date->format('Ymd'));

            // Simulasi Anomali
            $maxPossiblePrice = floor(1500000 / $qty);
            $price = min($pi->avg_price * (rand(98, 102) / 100), $maxPossiblePrice);
            
            // Randomly make it an anomaly (15%)
            if (rand(1, 100) > 85) {
                $price = min($pi->max_price * 1.3, $maxPossiblePrice);
            }
            
            $total = floor($price * $qty);
            
            // Status random: pending, approved, completed, rejected
            $statusList = ['pending', 'approved', 'completed', 'rejected'];
            $status = $statusList[array_rand($statusList)];

            // Simulasi Management Edit (Dual-Version): 10% dari data non-pending
            $isEdited = ($status !== 'pending' && rand(1, 10) > 9);
            
            $items = [[
                'customer'        => $pi->item_name,
                'category'        => $pi->category,
                'vendor'          => 'Vendor ' . $pi->category . ' Jaya',
                'description'     => 'Kebutuhan sparepart rutin unit dan pemeliharaan infrastruktur cabang.',
                'link'            => 'https://tokopedia.com/search?q=' . urlencode($pi->item_name),
                'estimated_price' => (int) $price,
                'quantity'        => (int) $qty,
                'specs'           => ['Brand' => 'Original', 'Garansi' => '1 Tahun', 'Kondisi' => 'Baru']
            ]];

            $itemsSnapshot = $items;
            $finalAmount = $total;

            if ($isEdited) {
                // Management kurangi qty atau harga
                $newQty = max(1, $qty - 1);
                $newPrice = $price * 0.98;
                $finalAmount = floor($newPrice * $newQty);
                
                $items = [[
                    'customer'        => $pi->item_name,
                    'category'        => $pi->category,
                    'vendor'          => 'Vendor ' . $pi->category . ' Jaya',
                    'description'     => 'Kebutuhan sparepart rutin unit. (Direvisi Management untuk efisiensi)',
                    'link'            => 'https://tokopedia.com/search?q=' . urlencode($pi->item_name),
                    'estimated_price' => (int) $newPrice,
                    'quantity'        => (int) $newQty,
                    'specs'           => ['Brand' => 'Original', 'Garansi' => '1 Tahun', 'Kondisi' => 'Baru']
                ]];
            }

            $trxData = [
                'type'              => Transaction::TYPE_PENGAJUAN,
                'invoice_number'    => IdGeneratorService::buildInvoiceNumber($seq, $date->format('Ymd')),
                'upload_id'         => $uploadId,
                'trace_id'          => 'TRX-' . strtoupper(Str::random(8)),
                'customer'          => $pi->item_name,
                'vendor'            => 'Vendor ' . $pi->category . ' Jaya',
                'category'          => $pi->category,
                'amount'            => $finalAmount,
                'estimated_price'   => $isEdited ? $items[0]['estimated_price'] : (int) $price,
                'quantity'          => $isEdited ? $items[0]['quantity'] : (int) $qty,
                'link'              => 'https://tokopedia.com/search?q=' . urlencode($pi->item_name),
                'specs'             => ['Brand' => 'Original', 'Garansi' => '1 Tahun', 'Kondisi' => 'Baru'],
                'description'       => 'Pengajuan rutin untuk menunjang operasional teknis di lapangan.',
                'items'             => $items,
                'date'              => $date,
                'status'            => $status,
                'submitted_by'      => $teknisi->id,
                'has_price_anomaly' => $price > $pi->max_price,
                'items_snapshot'          => $itemsSnapshot,
                'is_edited_by_management' => $isEdited,
                'edited_by'               => $isEdited ? $owner->id : null,
                'edited_at'               => $isEdited ? $date->copy()->addHour() : null,
                'revision_count'          => $isEdited ? 1 : 0,
            ];

            // Tambahkan field khusus berdasarkan status
            if (in_array($status, ['approved', 'completed', 'rejected'])) {
                $trxData['reviewed_by'] = $owner->id;
                $trxData['reviewed_at'] = $date->copy()->addHours(3);
            }

            if ($status === 'completed') {
                $trxData['paid_by'] = $owner->id;   
                $trxData['paid_at'] = $date->copy()->addHours(5);
                $trxData['konfirmasi_by'] = $atasan->id;
                $trxData['konfirmasi_at'] = $date->copy()->addHours(6);
                $trxData['payment_method'] = 'transfer_penjual';
                
                // OCR & actual amounts
                $trxData['actual_total'] = $finalAmount;
                $trxData['expected_total'] = $finalAmount;
                $trxData['selisih'] = 0;
                
                // Dummy proofs
                $trxData['file_path'] = 'receipts/dummy_pengajuan_' . $i . '.jpg';
                $trxData['bukti_transfer'] = 'transfers/dummy_tf_pengajuan_' . $i . '.jpg';
                $trxData['foto_penyerahan'] = 'deliveries/dummy_delivery_' . $i . '.jpg';

                // Breakdown (Dummy)
                $trxData['ongkir'] = rand(10, 50) * 1000;
                $trxData['biaya_layanan_1'] = 2000;
                
                // Sumber Dana
                $sourceBranch = $branches->random();
                $trxData['sumber_dana_branch_id'] = $sourceBranch->id;
                $trxData['sumber_dana_data'] = [
                    ['branch_id' => $sourceBranch->id, 'amount' => (int) $finalAmount]
                ];
            }

            if ($status === 'rejected') {
                $trxData['rejection_reason'] = 'Harga di atas rata-rata pasar, mohon cari vendor alternatif.';
            }

            try {
                $trx = Transaction::create($trxData);
            } catch (\Exception $e) {
                continue; // Lanjut ke iterasi berikutnya
            }

            // Jika anomali, buat record PriceAnomaly (hanya jika harga benar-benar melebihi max_price)
            if ($price > $pi->max_price) {
                $excessPercent = (($price - $pi->max_price) / $pi->max_price) * 100;
                
                $severity = 'low';
                if ($excessPercent > 50) {
                    $severity = 'critical';
                } elseif ($excessPercent > 20) {
                    $severity = 'medium';
                }

                PriceAnomaly::create([
                    'transaction_id'      => $trx->id,
                    'item_name'           => $pi->item_name,
                    'input_price'         => $price,
                    'reference_max_price' => $pi->max_price,
                    'excess_amount'       => $price - $pi->max_price,
                    'excess_percentage'   => round($excessPercent, 2),
                    'severity'            => $severity,
                    'price_index_id'      => $pi->id,
                    'reported_by_user_id' => $teknisi->id,
                    'status'              => $status === 'completed' ? 'approved' : 'pending'
                ]);
            }

            // Alokasi Cabang (Selalu isi untuk simulasi Price Index per Cabang)
            $trx->branches()->attach($branches->random()->id, [
                'allocation_percent' => 100,
                'allocation_amount'  => $finalAmount
            ]);
            }
            DB::commit();
        });

        $this->command->info('✅ Transaction Seeder selesai: 6,000 Rembush, 12,000 Pengajuan.');
        $this->command->info('✅ Semua data terisi lengkap untuk simulasi Price Index.');
    }
}
