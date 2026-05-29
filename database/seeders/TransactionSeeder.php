<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchBankAccount;
use App\Models\BranchDebt;
use App\Models\PriceIndex;
use App\Models\PriceAnomaly;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBankAccount;
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
        ini_set('memory_limit', '1024M');
        set_time_limit(1800); // 30 minutes
        DB::connection()->disableQueryLog();

        // ─── SETUP USERS ────────────────────────────────
        $this->command->info('⏳ Setting up users...');
        
        $owner = User::where('role', 'owner')->first() ?? User::updateOrCreate(
            ['email' => 'superadmin@whusnet.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('superadmin'),
                'role'     => 'owner'
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@whusnet.com'],
            [
                'name'     => 'Admin Whusnet',
                'password' => Hash::make('password'),
                'role'     => 'admin'
            ]
        );

        $atasan = User::updateOrCreate(
            ['email' => 'atasan@whusnet.com'],
            [
                'name'     => 'Atasan Whusnet',
                'password' => Hash::make('password'),
                'role'     => 'atasan'
            ]
        );

        $teknisi = User::updateOrCreate(
            ['email' => 'teknisi@whusnet.com'],
            [
                'name'     => 'Teknisi Whusnet',
                'password' => Hash::make('password'),
                'role'     => 'teknisi'
            ]
        );

        $branches = Branch::all();
        $priceIndexes = PriceIndex::all();

        if ($priceIndexes->isEmpty()) {
            $this->command->error('❌ Jalankan PriceIndexSeeder terlebih dahulu!');
            return;
        }

        if ($branches->isEmpty()) {
            $this->command->error('❌ Jalankan BranchSeeder atau pastikan ada data cabang!');
            return;
        }

        // ─── SETUP BRANCH & USER BANK ACCOUNTS ──────────
        $this->command->info('⏳ Setting up bank accounts...');
        
        foreach ($branches as $branch) {
            BranchBankAccount::firstOrCreate(
                ['branch_id' => $branch->id, 'bank_name' => 'MANDIRI'],
                [
                    'account_number' => rand(1000000000, 9999999999),
                    'account_name'   => 'OPERASIONAL ' . strtoupper($branch->name)
                ]
            );
            BranchBankAccount::firstOrCreate(
                ['branch_id' => $branch->id, 'bank_name' => 'BCA'],
                [
                    'account_number' => rand(1000000000, 9999999999),
                    'account_name'   => 'KAS ' . strtoupper($branch->name)
                ]
            );
        }

        UserBankAccount::firstOrCreate(
            ['user_id' => $teknisi->id, 'bank_name' => 'BCA'],
            [
                'account_number' => '1234567890',
                'account_name'   => 'TEKNISI WHUSNET'
            ]
        );

        $bankAccountsByBranch = BranchBankAccount::all()->groupBy('branch_id')->map(fn($accounts) => $accounts->first()->id)->toArray();

        // ─── CLEANUP OLD DATA ───────────────────────────
        $this->command->info('🧹 Cleaning up previous dummy transactions, branch debts, anomalies...');
        
        // Deleting transactions submitted by our dummy users (this cascades to debts and anomalies)
        Transaction::whereIn('submitted_by', [$teknisi->id, $admin->id, $atasan->id, $owner->id])->delete();

        // Arrays to hold bulk insert data
        $pivotRows = [];
        $debtRows = [];
        $priceAnomalyRows = [];

        // Helper to generate BranchDebt records
        $prepareBranchDebts = function ($transactionId, $amount, $allocatedBranches, $sumberDanaData, $status, $paymentMethod, $bankAccountsByBranch, $payerUserId) {
            $paidMap = collect($sumberDanaData)->keyBy('branch_id')->map(fn($sd) => (int) $sd['amount']);
            $creditors = [];
            $debtors = [];

            foreach ($allocatedBranches as $ab) {
                $allocation = (int) round(($amount * $ab['percent']) / 100);
                $paid = $paidMap->get($ab['branch_id'], 0);

                if ($paid > $allocation) {
                    $creditors[$ab['branch_id']] = $paid - $allocation;
                } elseif ($paid < $allocation) {
                    $debtors[$ab['branch_id']] = $allocation - $paid;
                }
            }

            if (empty($creditors) || empty($debtors)) return [];

            $totalExcess = array_sum($creditors);
            $debts = [];

            foreach ($debtors as $debtorId => $debtAmount) {
                foreach ($creditors as $creditorId => $creditorExcess) {
                    $proportion = $creditorExcess / $totalExcess;
                    $debtToCreditor = (int) round($debtAmount * $proportion);

                    if ($debtToCreditor > 0) {
                        $isPaid = ($status === 'completed');
                        $paidAt = $isPaid ? Carbon::now()->subMinutes(rand(1, 1000)) : null;
                        $debtPaymentMethod = $isPaid ? $paymentMethod : 'transfer';
                        
                        $bankId = null;
                        $senderBankId = null;
                        
                        if ($isPaid && $debtPaymentMethod === 'transfer') {
                            $bankId = $bankAccountsByBranch[$creditorId] ?? null;
                            $senderBankId = $bankAccountsByBranch[$debtorId] ?? null;
                        }

                        $debts[] = [
                            'transaction_id'         => $transactionId,
                            'debtor_branch_id'       => $debtorId,
                            'creditor_branch_id'     => $creditorId,
                            'amount'                 => $debtToCreditor,
                            'status'                 => $isPaid ? 'paid' : 'pending',
                            'paid_at'                => $paidAt,
                            'notes'                  => 'Simulasi hutang antar cabang dari seeder.',
                            'payment_method'         => $debtPaymentMethod,
                            'paid_by_id'             => $isPaid ? $payerUserId : null,
                            'bank_account_id'        => $bankId,
                            'sender_bank_account_id' => $senderBankId,
                            'payment_proof'          => $isPaid ? 'payment_proofs/debts/dummy_proof.jpg' : null,
                            'created_at'             => Carbon::now(),
                            'updated_at'             => Carbon::now(),
                        ];
                    }
                }   
            }

            return $debts;
        };

        // ─── 1. SEED REMBUSH (14,000 RECORDS) ────────────
        $this->command->info('⏳ Seeding 14,000 Rembush...');
        Transaction::withoutEvents(function () use ($priceIndexes, $branches, $teknisi, $atasan, &$pivotRows) {
            DB::beginTransaction();
            for ($i = 1; $i <= 14000; $i++) {
                $pi = $priceIndexes->random();
                $qty = rand(1, 5);
                $maxPossiblePrice = floor(1500000 / $qty);
                $price = min($pi->avg_price * (rand(95, 105) / 100), $maxPossiblePrice);
                $total = floor($price * $qty);

                $date = Carbon::now()->subDays(rand(0, 30));
                $invoiceNum = IdGeneratorService::buildUploadId($i, $date->format('Ymd'));
                $traceId = 'TRX-' . strtoupper(Str::random(8));

                $sourceBranch = $branches->random();

                $trx = Transaction::create([
                    'type'                  => Transaction::TYPE_REMBUSH,
                    'invoice_number'        => $invoiceNum,
                    'upload_id'             => $invoiceNum,
                    'trace_id'              => $traceId,
                    'customer'              => 'Toko ' . $pi->category . ' Sejahtera',
                    'category'              => $pi->category,
                    'description'           => "Pembelian {$qty} unit {$pi->item_name} untuk maintenance cabang.",
                    'payment_method'        => collect(array_keys(Transaction::PAYMENT_METHODS))->random(),
                    'amount'                => $total,
                    'items'                 => [[
                        'name'     => $pi->item_name,
                        'price'    => $price,
                        'quantity' => $qty,
                        'total'    => $total,
                        'category' => $pi->category,
                    ]],
                    'date'                  => $date,
                    'status'                => 'completed',
                    'submitted_by'          => $teknisi->id,
                    'reviewed_by'           => $atasan->id,
                    'reviewed_at'           => $date->copy()->addHours(2),
                    'ai_status'             => 'completed',
                    'confidence'            => rand(85, 100),
                    'actual_total'          => $total,
                    'expected_total'        => $total,
                    'selisih'               => 0,
                    'file_path'             => 'receipts/dummy_rembush_' . $i . '.jpg',
                    'sumber_dana_branch_id' => $sourceBranch->id,
                    'sumber_dana_data'      => [['branch_id' => $sourceBranch->id, 'amount' => (int) $total]],
                    'ocr_result'            => [
                        'merchant' => 'Toko ' . $pi->category . ' Sejahtera',
                        'items'    => [['desc' => $pi->item_name, 'qty' => $qty, 'price' => $price, 'total' => $total]],
                        'total'    => $total,
                        'date'     => $date->format('Y-m-d')
                    ],
                    'ocr_confidence'        => rand(90, 99),
                    'overall_confidence'    => rand(88, 98),
                    'confidence_label'      => 'high',
                    'field_confidence'      => [
                        'merchant' => 95,
                        'total'    => 99,
                        'date'     => 98,
                        'items'    => 90
                    ]
                ]);

                // Allocation Pivot setup
                if (rand(1, 10) > 8) {
                    $b1 = $branches->random();
                    $b2 = $branches->where('id', '!=', $b1->id)->random();
                    $pivotRows[] = [
                        'transaction_id'     => $trx->id,
                        'branch_id'          => $b1->id,
                        'allocation_percent' => 60,
                        'allocation_amount'  => floor($total * 0.6),
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                    $pivotRows[] = [
                        'transaction_id'     => $trx->id,
                        'branch_id'          => $b2->id,
                        'allocation_percent' => 40,
                        'allocation_amount'  => ceil($total * 0.4),
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                } else {
                    $pivotRows[] = [
                        'transaction_id'     => $trx->id,
                        'branch_id'          => $branches->random()->id,
                        'allocation_percent' => 100,
                        'allocation_amount'  => $total,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                }
            }
            DB::commit();
        });

        // ─── 2. SEED PENGAJUAN (16,000 RECORDS) ──────────
        $this->command->info('⏳ Seeding 16,000 Pengajuan with Status Flow & branch debts...');
        Transaction::withoutEvents(function () use ($priceIndexes, $branches, $teknisi, $owner, $atasan, $admin, &$pivotRows, &$debtRows, &$priceAnomalyRows, $prepareBranchDebts, $bankAccountsByBranch) {
            DB::beginTransaction();
            for ($i = 1; $i <= 16000; $i++) {
                $pi = $priceIndexes->random();
                $qty = rand(1, 5);

                $date = Carbon::now()->subDays(rand(0, 90));
                $seq = 100000 + $i;
                $uploadId = IdGeneratorService::buildUploadId($seq, $date->format('Ymd'));
                $invoiceNum = IdGeneratorService::buildInvoiceNumber($seq, $date->format('Ymd'));

                // Calculate base and monetary details
                $maxPossiblePrice = floor(1500000 / $qty);
                $price = min($pi->avg_price * (rand(98, 102) / 100), $maxPossiblePrice);
                
                // Anomaly simulation
                $hasAnomaly = (rand(1, 100) > 85);
                if ($hasAnomaly) {
                    $price = min($pi->max_price * 1.3, $maxPossiblePrice);
                }

                $baseAmount = floor($price * $qty);
                $ongkir = (rand(1, 10) > 7) ? rand(10, 50) * 1000 : 0;
                $layanan1 = (rand(1, 10) > 7) ? 2000 : 0;
                $layanan2 = (rand(1, 10) > 8) ? 1000 : 0;
                $taxAmount = (rand(1, 10) > 8) ? (int)round($baseAmount * 0.11) : 0;
                $dppLainnya = (rand(1, 10) > 8) ? rand(5, 20) * 1000 : 0;
                $diskonOngkir = ($ongkir > 0 && rand(1, 10) > 7) ? 10000 : 0;
                $voucher = (rand(1, 10) > 8) ? rand(5, 15) * 1000 : 0;
                
                $totalTransaksi = $baseAmount + $ongkir + $layanan1 + $layanan2 + $taxAmount + $dppLainnya - $diskonOngkir - $voucher;

                // Status distribution
                $rand = rand(1, 100);
                if ($rand <= 15) {
                    $status = 'pending';
                } elseif ($rand <= 30) {
                    $status = 'approved';
                } elseif ($rand <= 60) {
                    $status = 'waiting_payment';
                } elseif ($rand <= 90) {
                    $status = 'completed';
                } else {
                    $status = 'rejected';
                }

                $isEdited = ($status !== 'pending' && rand(1, 10) > 9);

                $items = [[
                    'customer'        => $pi->item_name,
                    'category'        => $pi->category,
                    'vendor'          => 'Vendor ' . $pi->category . ' Jaya',
                    'description'     => 'Kebutuhan sparepart rutin unit.',
                    'link'            => 'https://tokopedia.com/search?q=' . urlencode($pi->item_name),
                    'estimated_price' => (int) $price,
                    'quantity'        => (int) $qty,
                    'specs'           => ['Brand' => 'Original', 'Garansi' => '1 Tahun', 'Kondisi' => 'Baru']
                ]];

                $itemsSnapshot = $items;
                $finalAmount = $totalTransaksi;

                if ($isEdited) {
                    $newQty = max(1, $qty - 1);
                    $newPrice = $price * 0.98;
                    $items = [[
                        'customer'        => $pi->item_name,
                        'category'        => $pi->category,
                        'vendor'          => 'Vendor ' . $pi->category . ' Jaya',
                        'description'     => 'Kebutuhan sparepart rutin unit. (Direvisi Management)',
                        'link'            => 'https://tokopedia.com/search?q=' . urlencode($pi->item_name),
                        'estimated_price' => (int) $newPrice,
                        'quantity'        => (int) $newQty,
                        'specs'           => ['Brand' => 'Original', 'Garansi' => '1 Tahun', 'Kondisi' => 'Baru']
                    ]];
                    $baseAmount = floor($newPrice * $newQty);
                    $finalAmount = $baseAmount + $ongkir + $layanan1 + $layanan2 + $taxAmount + $dppLainnya - $diskonOngkir - $voucher;
                }

                // Determine invoice proof files for waiting_payment (Settlement phase vs Payment Wait phase)
                $invoiceFilePath = null;
                $buktiTransfer = null;
                $fotoPenyerahan = null;
                $paidBy = null;
                $paidAt = null;

                if ($status === 'waiting_payment') {
                    // 50% chance it is in the "Menunggu Pelunasan" phase (invoice uploaded)
                    if (rand(1, 100) > 50) {
                        $invoiceFilePath = 'invoices/dummy_invoice_' . $i . '.jpg';
                        $paidBy = $admin->id;
                        $paidAt = $date->copy()->addHours(4);
                    }
                } elseif ($status === 'completed') {
                    $invoiceFilePath = 'invoices/dummy_invoice_' . $i . '.jpg';
                    $buktiTransfer = 'transfers/dummy_tf_pengajuan_' . $i . '.jpg';
                    $fotoPenyerahan = 'deliveries/dummy_delivery_' . $i . '.jpg';
                    $paidBy = $owner->id;
                    $paidAt = $date->copy()->addHours(5);
                }

                $trxData = [
                    'type'                    => Transaction::TYPE_PENGAJUAN,
                    'invoice_number'          => $invoiceNum,
                    'upload_id'               => $uploadId,
                    'trace_id'                => 'TRX-' . strtoupper(Str::random(8)),
                    'customer'                => $pi->item_name,
                    'vendor'                  => 'Vendor ' . $pi->category . ' Jaya',
                    'category'                => $pi->category,
                    'amount'                  => $finalAmount,
                    'estimated_price'         => $isEdited ? $items[0]['estimated_price'] : (int) $price,
                    'quantity'                => $isEdited ? $items[0]['quantity'] : (int) $qty,
                    'link'                    => 'https://tokopedia.com/search?q=' . urlencode($pi->item_name),
                    'specs'                   => ['Brand' => 'Original', 'Garansi' => '1 Tahun', 'Kondisi' => 'Baru'],
                    'description'             => 'Pengajuan rutin untuk operasional lapangan.',
                    'items'                   => $items,
                    'date'                    => $date,
                    'status'                  => $status,
                    'submitted_by'            => $teknisi->id,
                    'has_price_anomaly'       => $hasAnomaly,
                    'items_snapshot'          => $itemsSnapshot,
                    'is_edited_by_management' => $isEdited,
                    'edited_by'               => $isEdited ? $owner->id : null,
                    'edited_at'               => $isEdited ? $date->copy()->addHour() : null,
                    'revision_count'          => $isEdited ? 1 : 0,
                    // Financial details
                    'tax_amount'              => $taxAmount,
                    'discount_amount'         => $diskonOngkir,
                    'ongkir'                  => $ongkir,
                    'biaya_layanan_1'         => $layanan1,
                    'biaya_layanan_2'         => $layanan2,
                    'voucher_diskon'          => $voucher,
                    'diskon_pengiriman'       => $diskonOngkir,
                    'dpp_lainnya'             => $dppLainnya,
                    // Proofs
                    'invoice_file_path'       => $invoiceFilePath,
                    'bukti_transfer'          => $buktiTransfer,
                    'foto_penyerahan'         => $fotoPenyerahan,
                    'payment_method'          => 'transfer_penjual',
                    'expected_total'          => $finalAmount,
                    'actual_total'            => ($status === 'completed' || $invoiceFilePath) ? $finalAmount : null,
                    'selisih'                 => 0,
                ];

                if (in_array($status, ['approved', 'completed', 'rejected'])) {
                    $trxData['reviewed_by'] = $owner->id;
                    $trxData['reviewed_at'] = $date->copy()->addHours(3);
                }

                if ($status === 'completed') {
                    $trxData['paid_by'] = $paidBy;
                    $trxData['paid_at'] = $paidAt;
                    $trxData['konfirmasi_by'] = $atasan->id;
                    $trxData['konfirmasi_at'] = $date->copy()->addHours(6);
                }

                if ($status === 'rejected') {
                    $trxData['rejection_reason'] = 'Harga melebihi anggaran pasar, disarankan mencari vendor alternatif.';
                }

                try {
                    $trx = Transaction::create($trxData);
                } catch (\Exception $e) {
                    continue;
                }

                // Branch Allocation
                $allocatedBranches = [];
                $isSplit = (rand(1, 100) > 80); // 20% chance of split allocation
                if ($isSplit) {
                    $b1 = $branches->random();
                    $b2 = $branches->where('id', '!=', $b1->id)->random();
                    
                    $pivotRows[] = [
                        'transaction_id'     => $trx->id,
                        'branch_id'          => $b1->id,
                        'allocation_percent' => 60,
                        'allocation_amount'  => floor($finalAmount * 0.6),
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                    $pivotRows[] = [
                        'transaction_id'     => $trx->id,
                        'branch_id'          => $b2->id,
                        'allocation_percent' => 40,
                        'allocation_amount'  => ceil($finalAmount * 0.4),
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];

                    $allocatedBranches = [
                        ['branch_id' => $b1->id, 'percent' => 60],
                        ['branch_id' => $b2->id, 'percent' => 40]
                    ];
                } else {
                    $b1 = $branches->random();
                    $pivotRows[] = [
                        'transaction_id'     => $trx->id,
                        'branch_id'          => $b1->id,
                        'allocation_percent' => 100,
                        'allocation_amount'  => $finalAmount,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];

                    $allocatedBranches = [
                        ['branch_id' => $b1->id, 'percent' => 100]
                    ];
                }

                // Branch Debt Setup (For split allocations in completed or waiting_payment with invoice uploaded)
                if ($isSplit && ($status === 'completed' || ($status === 'waiting_payment' && $invoiceFilePath))) {
                    // Let debtor branch be creditor or debtor depending on source branch
                    // Suppose creditor (funding source) is b1, debtor is b2
                    $sumberDanaData = [['branch_id' => $b1->id, 'amount' => $finalAmount]];
                    
                    // Update main transaction source branch
                    $trx->update([
                        'sumber_dana_branch_id' => $b1->id,
                        'sumber_dana_data'      => $sumberDanaData
                    ]);

                    $debts = $prepareBranchDebts(
                        $trx->id,
                        $finalAmount,
                        $allocatedBranches,
                        $sumberDanaData,
                        $status,
                        rand(1, 10) > 5 ? 'transfer' : 'cash',
                        $bankAccountsByBranch,
                        $admin->id
                    );

                    foreach ($debts as $d) {
                        $debtRows[] = $d;
                    }
                } else {
                    // Single branch pays its own allocation
                    $trx->update([
                        'sumber_dana_branch_id' => $b1->id,
                        'sumber_dana_data'      => [['branch_id' => $b1->id, 'amount' => $finalAmount]]
                    ]);
                }

                // Price Anomaly seeding
                if ($hasAnomaly) {
                    $excessPercent = (($price - $pi->max_price) / $pi->max_price) * 100;
                    $severity = 'low';
                    if ($excessPercent > 50) {
                        $severity = 'critical';
                    } elseif ($excessPercent > 20) {
                        $severity = 'medium';
                    }

                    $priceAnomalyRows[] = [
                        'transaction_id'      => $trx->id,
                        'item_name'           => $pi->item_name,
                        'input_price'         => $price,
                        'reference_max_price' => $pi->max_price,
                        'excess_amount'       => $price - $pi->max_price,
                        'excess_percentage'   => round($excessPercent, 2),
                        'severity'            => $severity,
                        'price_index_id'      => $pi->id,
                        'reported_by_user_id' => $teknisi->id,
                        'status'              => $status === 'completed' ? 'approved' : 'pending',
                        'created_at'          => $date,
                        'updated_at'          => $date,
                    ];
                }
            }
            DB::commit();
        });

        // ─── 3. SEED GUDANG / PEMBELIAN (10,000 RECORDS) ──
        $this->command->info('⏳ Seeding 10,000 Gudang (Pembelian) transactions...');
        Transaction::withoutEvents(function () use ($priceIndexes, $branches, $teknisi, $owner, $atasan, $admin, &$pivotRows, &$debtRows, &$priceAnomalyRows, $prepareBranchDebts, $bankAccountsByBranch) {
            DB::beginTransaction();
            for ($i = 1; $i <= 10000; $i++) {
                $pi = $priceIndexes->random();
                $qty = rand(1, 5);

                $date = Carbon::now()->subDays(rand(0, 90));
                $seq = 200000 + $i;
                $uploadId = IdGeneratorService::buildUploadId($seq, $date->format('Ymd'));
                $invoiceNum = IdGeneratorService::buildInvoiceNumber($seq, $date->format('Ymd'));

                // Calculate base and monetary details
                $maxPossiblePrice = floor(1500000 / $qty);
                $price = min($pi->avg_price * (rand(98, 102) / 100), $maxPossiblePrice);
                
                $hasAnomaly = (rand(1, 100) > 90);
                if ($hasAnomaly) {
                    $price = min($pi->max_price * 1.2, $maxPossiblePrice);
                }

                $baseAmount = floor($price * $qty);
                $ongkir = (rand(1, 10) > 7) ? rand(10, 50) * 1000 : 0;
                $layanan1 = (rand(1, 10) > 7) ? 2000 : 0;
                $layanan2 = 0;
                $taxAmount = 0;
                $dppLainnya = 0;
                
                $totalTransaksi = $baseAmount + $ongkir + $layanan1;

                // Status distribution
                $rand = rand(1, 100);
                if ($rand <= 10) {
                    $status = 'pending';
                } elseif ($rand <= 20) {
                    $status = 'approved';
                } elseif ($rand <= 50) {
                    $status = 'waiting_payment';
                } elseif ($rand <= 90) {
                    $status = 'completed';
                } else {
                    $status = 'rejected';
                }

                $items = [[
                    'customer'        => $pi->item_name,
                    'category'        => $pi->category,
                    'vendor'          => 'Gudang Supplier ' . $pi->category,
                    'description'     => 'Stok perlengkapan gudang internal.',
                    'link'            => null,
                    'estimated_price' => (int) $price,
                    'quantity'        => (int) $qty,
                    'specs'           => ['Kondisi' => 'Baru']
                ]];

                $invoiceFilePath = null;
                $buktiTransfer = null;
                $paidBy = null;
                $paidAt = null;

                if ($status === 'waiting_payment') {
                    if (rand(1, 100) > 40) {
                        $invoiceFilePath = 'invoices/dummy_gudang_' . $i . '.jpg';
                        $paidBy = $admin->id;
                        $paidAt = $date->copy()->addHours(3);
                    }
                } elseif ($status === 'completed') {
                    $invoiceFilePath = 'invoices/dummy_gudang_' . $i . '.jpg';
                    $buktiTransfer = 'transfers/dummy_tf_gudang_' . $i . '.jpg';
                    $paidBy = $owner->id;
                    $paidAt = $date->copy()->addHours(4);
                }

                $trxData = [
                    'type'                    => Transaction::TYPE_GUDANG,
                    'invoice_number'          => $invoiceNum,
                    'upload_id'               => $uploadId,
                    'trace_id'                => 'TRX-' . strtoupper(Str::random(8)),
                    'customer'                => 'Warehouse Whusnet',
                    'vendor'                  => 'Gudang Supplier ' . $pi->category,
                    'category'                => $pi->category,
                    'amount'                  => $totalTransaksi,
                    'estimated_price'         => (int) $price,
                    'quantity'                => (int) $qty,
                    'link'                    => null,
                    'specs'                   => ['Kondisi' => 'Baru'],
                    'description'             => 'Pembelian stok internal Gudang.',
                    'items'                   => $items,
                    'date'                    => $date,
                    'status'                  => $status,
                    'submitted_by'            => $admin->id, // Gudang submitted by admin or owner
                    'has_price_anomaly'       => $hasAnomaly,
                    'items_snapshot'          => $items,
                    'is_edited_by_management' => false,
                    'tax_amount'              => $taxAmount,
                    'discount_amount'         => 0,
                    'ongkir'                  => $ongkir,
                    'biaya_layanan_1'         => $layanan1,
                    'biaya_layanan_2'         => $layanan2,
                    'voucher_diskon'          => 0,
                    'diskon_pengiriman'       => 0,
                    'dpp_lainnya'             => $dppLainnya,
                    'invoice_file_path'       => $invoiceFilePath,
                    'bukti_transfer'          => $buktiTransfer,
                    'payment_method'          => 'transfer_penjual',
                    'expected_total'          => $totalTransaksi,
                    'actual_total'            => ($status === 'completed' || $invoiceFilePath) ? $totalTransaksi : null,
                    'selisih'                 => 0,
                ];

                if (in_array($status, ['approved', 'completed', 'rejected'])) {
                    $trxData['reviewed_by'] = $atasan->id;
                    $trxData['reviewed_at'] = $date->copy()->addHours(2);
                }

                if ($status === 'completed') {
                    $trxData['paid_by'] = $paidBy;
                    $trxData['paid_at'] = $paidAt;
                    $trxData['konfirmasi_by'] = $owner->id;
                    $trxData['konfirmasi_at'] = $date->copy()->addHours(5);
                }

                try {
                    $trx = Transaction::create($trxData);
                } catch (\Exception $e) {
                    continue;
                }

                // Branch Allocation
                $allocatedBranches = [];
                $isSplit = (rand(1, 100) > 85);
                if ($isSplit) {
                    $b1 = $branches->random();
                    $b2 = $branches->where('id', '!=', $b1->id)->random();
                    
                    $pivotRows[] = [
                        'transaction_id'     => $trx->id,
                        'branch_id'          => $b1->id,
                        'allocation_percent' => 50,
                        'allocation_amount'  => floor($totalTransaksi * 0.5),
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                    $pivotRows[] = [
                        'transaction_id'     => $trx->id,
                        'branch_id'          => $b2->id,
                        'allocation_percent' => 50,
                        'allocation_amount'  => ceil($totalTransaksi * 0.5),
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];

                    $allocatedBranches = [
                        ['branch_id' => $b1->id, 'percent' => 50],
                        ['branch_id' => $b2->id, 'percent' => 50]
                    ];
                } else {
                    $b1 = $branches->random();
                    $pivotRows[] = [
                        'transaction_id'     => $trx->id,
                        'branch_id'          => $b1->id,
                        'allocation_percent' => 100,
                        'allocation_amount'  => $totalTransaksi,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];

                    $allocatedBranches = [
                        ['branch_id' => $b1->id, 'percent' => 100]
                    ];
                }

                // Branch Debt Setup
                if ($isSplit && ($status === 'completed' || ($status === 'waiting_payment' && $invoiceFilePath))) {
                    $sumberDanaData = [['branch_id' => $b1->id, 'amount' => $totalTransaksi]];
                    
                    $trx->update([
                        'sumber_dana_branch_id' => $b1->id,
                        'sumber_dana_data'      => $sumberDanaData
                    ]);

                    $debts = $prepareBranchDebts(
                        $trx->id,
                        $totalTransaksi,
                        $allocatedBranches,
                        $sumberDanaData,
                        $status,
                        'transfer',
                        $bankAccountsByBranch,
                        $admin->id
                    );

                    foreach ($debts as $d) {
                        $debtRows[] = $d;
                    }
                } else {
                    $trx->update([
                        'sumber_dana_branch_id' => $b1->id,
                        'sumber_dana_data'      => [['branch_id' => $b1->id, 'amount' => $totalTransaksi]]
                    ]);
                }

                // Price Anomaly Setup
                if ($hasAnomaly) {
                    $excessPercent = (($price - $pi->max_price) / $pi->max_price) * 100;
                    $severity = 'low';
                    if ($excessPercent > 50) {
                        $severity = 'critical';
                    } elseif ($excessPercent > 20) {
                        $severity = 'medium';
                    }

                    $priceAnomalyRows[] = [
                        'transaction_id'      => $trx->id,
                        'item_name'           => $pi->item_name,
                        'input_price'         => $price,
                        'reference_max_price' => $pi->max_price,
                        'excess_amount'       => $price - $pi->max_price,
                        'excess_percentage'   => round($excessPercent, 2),
                        'severity'            => $severity,
                        'price_index_id'      => $pi->id,
                        'reported_by_user_id' => $admin->id,
                        'status'              => $status === 'completed' ? 'approved' : 'pending',
                        'created_at'          => $date,
                        'updated_at'          => $date,
                    ];
                }
            }
            DB::commit();
        });

        // ─── BULK INSERT RELATION DATA (PERFORMANCE OPTIMIZATION) ───
        $this->command->info('⏳ Performing bulk insertions of relationship tables for performance...');
        
        if (!empty($pivotRows)) {
            $this->command->info('   - Inserting ' . count($pivotRows) . ' transaction branches...');
            foreach (array_chunk($pivotRows, 1000) as $chunk) {
                DB::table('transaction_branches')->insert($chunk);
            }
        }

        if (!empty($debtRows)) {
            $this->command->info('   - Inserting ' . count($debtRows) . ' branch debts...');
            foreach (array_chunk($debtRows, 500) as $chunk) {
                DB::table('branch_debts')->insert($chunk);
            }
        }

        if (!empty($priceAnomalyRows)) {
            $this->command->info('   - Inserting ' . count($priceAnomalyRows) . ' price anomalies...');
            foreach (array_chunk($priceAnomalyRows, 500) as $chunk) {
                DB::table('price_anomalies')->insert($chunk);
            }
        }

        $this->command->info('✅ Transaction Seeder selesai: 14,000 Rembush, 16,000 Pengajuan, 10,000 Gudang.');
        $this->command->info('✅ Semua data terisi lengkap dengan branch debts dan price anomalies.');
    }
}
