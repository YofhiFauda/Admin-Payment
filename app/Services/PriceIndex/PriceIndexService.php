<?php

namespace App\Services\PriceIndex;

use App\Models\PriceAnomaly;
use App\Models\PriceIndex;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════
 *  PriceIndexService — Kalkulasi & Deteksi Anomali Harga
 *
 *  FITUR UTAMA:
 *  ✅ findMatchingIndex()     – Cari referensi berdasarkan nama item
 *  ✅ detectAnomaly()         – Deteksi anomali untuk satu item
 *  ✅ detectForTransaction()  – Deteksi semua item dalam 1 pengajuan
 *  ✅ setAsReference()        – Jadikan harga dari transaksi approved sebagai referensi
 *  ✅ recalculateFromHistory() – Hitung ulang min/max/avg dari histori approved
 * ═══════════════════════════════════════════════════════════════
 */
class PriceIndexService
{
    // ════════════════════════════════════════════════════════
    //  LOOKUP
    // ════════════════════════════════════════════════════════

    /**
     * Cari price index yang cocok berdasarkan item name.
     * Cek exact match dulu, lalu case-insensitive.
     * Jika tidak ketemu, fallback ke rata-rata kategori jika kategori disediakan.
     */
    public function findMatchingIndex(string $itemName, ?string $category = null): ?PriceIndex
    {
        $index = PriceIndex::findByItemName($itemName);

        if (!$index && $category) {
            // Fallback: Cari rata-rata harga di kategori yang sama
            $catAvg = PriceIndex::where('category', $category)
                                ->where('is_manual', false)
                                ->selectRaw('AVG(min_price) as min, AVG(max_price) as max, AVG(avg_price) as avg')
                                ->first();

            if ($catAvg && $catAvg->avg > 0) {
                return new PriceIndex([
                    'item_name' => "Kategori: {$category}",
                    'min_price' => $catAvg->min,
                    'max_price' => $catAvg->max,
                    'avg_price' => $catAvg->avg,
                    'category'  => $category,
                    'is_manual' => false,
                ]);
            }
        }

        return $index;
    }

    // ════════════════════════════════════════════════════════
    //  DETEKSI ANOMALI
    // ════════════════════════════════════════════════════════

    /**
     * Deteksi anomali untuk satu item.
     * Return PriceAnomaly jika terdeteksi, atau null jika normal.
     */
    public function detectAnomaly(
        string $itemName,
        int    $unitPrice,
        int    $transactionId,
        int    $reportedByUserId,
        ?string $category = null
    ): ?PriceAnomaly {

        $priceIndex = $this->findMatchingIndex($itemName, $category);

        // ── Cold Start Handling ────────────────────────────
        if (!$priceIndex) {
            // Buat record price index baru tapi tandai perlu review
            PriceIndex::create([
                'item_name'            => $itemName,
                'category'             => $category,
                'min_price'            => $unitPrice,
                'max_price'            => $unitPrice,
                'avg_price'            => $unitPrice,
                'is_manual'            => false,
                'needs_initial_review' => true,
                'total_transactions'   => 1,
                'last_calculated_at'   => now(),
            ]);
            return null; 
        }

        if ($priceIndex->max_price <= 0) {
            return null;
        }

        // Lock row jika ini record asli (bukan virtual fallback)
        if ($priceIndex->exists) {
            $priceIndex = PriceIndex::where('id', $priceIndex->id)->lockForUpdate()->first();
        }

        if ($unitPrice <= $priceIndex->max_price) {
            return null; // Harga normal
        }

        // ── Hitung excess ──────────────────────────────────
        $excessAmount     = $unitPrice - $priceIndex->max_price;
        $excessPercentage = ($excessAmount / $priceIndex->max_price) * 100;

        $severity = match(true) {
            $excessPercentage >= 50 => 'critical',
            $excessPercentage >= 20 => 'medium',
            default                 => 'low',
        };

        // ── Simpan anomali ─────────────────────────────────
        $anomaly = PriceAnomaly::create([
            'transaction_id'       => $transactionId,
            'item_name'            => $itemName,
            'input_price'          => $unitPrice,
            'reference_max_price'  => $priceIndex->max_price,
            'excess_amount'        => $excessAmount,
            'excess_percentage'    => round($excessPercentage, 2),
            'severity'             => $severity,
            'price_index_id'       => $priceIndex->id,
            'reported_by_user_id'  => $reportedByUserId,
            'status'               => 'pending',
        ]);

        Log::info('🔴 [PriceIndex] Anomali terdeteksi', [
            'transaction_id'    => $transactionId,
            'item_name'         => $itemName,
            'input_price'       => $unitPrice,
            'reference_max'     => $priceIndex->max_price,
            'excess_percentage' => $excessPercentage,
            'severity'          => $severity,
        ]);

        return $anomaly;
    }

    /**
     * Deteksi anomali untuk semua item dalam satu transaksi Pengajuan.
     * Return array of PriceAnomaly yang ditemukan.
     */
    public function detectForTransaction(Transaction $transaction): array
    {
        if (!$transaction->isPengajuan()) {
            return [];
        }

        $items    = $transaction->items ?? [];
        $anomalies = [];

        foreach ($items as $item) {
            $itemName   = $item['customer'] ?? null;   // "customer" = nama barang di Pengajuan
            $unitPrice  = intval($item['estimated_price'] ?? 0);

            if (!$itemName || $unitPrice <= 0) {
                continue;
            }

            $anomaly = $this->detectAnomaly(
                itemName:          $itemName,
                unitPrice:         $unitPrice,
                transactionId:     $transaction->id,
                reportedByUserId:  $transaction->submitted_by,
                category:          $item['category'] ?? null,
            );

            if ($anomaly) {
                $anomalies[] = $anomaly;
            }
        }

        // Tandai transaksi sebagai has_price_anomaly jika ada anomali
        if (!empty($anomalies)) {
            $transaction->has_price_anomaly = true;
            $transaction->saveQuietly();
        }

        return $anomalies;
    }

    // ════════════════════════════════════════════════════════
    //  JADIKAN REFERENSI (Manual Override oleh Owner/Atasan)
    // ════════════════════════════════════════════════════════

    /**
     * Jadikan harga dari item tertentu dalam transaksi sebagai referensi.
     * Dipanggil saat Owner/Atasan klik tombol "Jadikan Referensi".
     *
     * @param Transaction $transaction
     * @param string      $itemName  Nama barang yang akan dijadikan referensi
     * @param int         $setBy     User ID (Owner/Atasan)
     * @param string|null $reason    Alasan manual override
     */
    public function setAsReference(Transaction $transaction, string $itemName, int $setBy, ?string $reason = null): ?PriceIndex
    {
        $items = $transaction->items ?? [];

        $targetItem = null;
        foreach ($items as $item) {
            $name = $item['customer'] ?? '';
            if (strtolower(trim($name)) === strtolower(trim($itemName))) {
                $targetItem = $item;
                break;
            }
        }

        if (!$targetItem) {
            return null;
        }

        $price    = intval($targetItem['estimated_price'] ?? 0);
        $category = $targetItem['category'] ?? null;
        $unit     = 'pcs'; // Default, bisa dikembangkan

        if ($price <= 0) {
            return null;
        }

        // Cari atau buat price index
        $priceIndex = PriceIndex::where('item_name', $itemName)->first();

        if ($priceIndex) {
            // Update existing — set sebagai manual reference
            $priceIndex->update([
                'min_price'           => min($priceIndex->min_price, $price),
                'max_price'           => max($priceIndex->max_price, $price),
                'avg_price'           => $price,
                'is_manual'           => true,
                'manual_set_by'       => $setBy,
                'manual_set_at'       => now(),
                'manual_reason'       => $reason ?? 'Jadikan referensi dari ' . $transaction->invoice_number,
                'needs_initial_review'=> false,
                'total_transactions'  => $priceIndex->total_transactions + 1, // ✅ Increment counter
            ]);
        } else {
            // Buat baru
            $priceIndex = PriceIndex::create([
                'item_name'     => $itemName,
                'category'      => $category,
                'unit'          => $unit,
                'min_price'     => $price,
                'max_price'     => $price,
                'avg_price'     => $price,
                'is_manual'     => true,
                'manual_set_by' => $setBy,
                'manual_set_at' => now(),
                'manual_reason' => $reason ?? 'Jadikan referensi dari ' . $transaction->invoice_number,
                'total_transactions' => 1,
                'last_calculated_at' => now(),
            ]);
        }

        Log::info('✅ [PriceIndex] Dijadikan referensi manual', [
            'item_name'      => $itemName,
            'price'          => $price,
            'set_by_user_id' => $setBy,
            'reason'         => $priceIndex->manual_reason,
        ]);

        return $priceIndex;
    }

    // ════════════════════════════════════════════════════════
    //  AUTO-ADAPTIVE INCREMENTAL UPDATE
    // ════════════════════════════════════════════════════════
    
    /**
     * Proses item dari transaksi yang baru di-approve.
     * Jika harga berada dalam rentang [min, max], update rata-rata (Avg) secara otomatis.
     */
    public function processApprovedItem(string $itemName, float $price, ?string $category = null): ?PriceIndex
    {
        $priceIndex = PriceIndex::where('item_name', $itemName)->first();

        // 1. Jika Belum Ada Index (Cold Start)
        if (!$priceIndex) {
            return PriceIndex::create([
                'item_name'            => $itemName,
                'category'             => $category,
                'min_price'            => $price,
                'max_price'            => $price,
                'avg_price'            => $price,
                'is_manual'            => false,
                'needs_initial_review' => true,
                'total_transactions'   => 1,
                'last_calculated_at'   => now(),
            ]);
        }

        // 2. Lock Row untuk mencegah Race Condition saat update rata-rata
        return DB::transaction(function () use ($priceIndex, $price) {
            $pi = PriceIndex::where('id', $priceIndex->id)->lockForUpdate()->first();

            // 3. Cek Rentang Harga [Min, Max]
            // Sesuai request: Update Otomatis selama >harga min dan < harga max
            // Kita gunakan toleransi inklusif (>= dan <=) untuk kestabilan
            if ($price >= $pi->min_price && $price <= $pi->max_price) {
                
                $oldTotal = $pi->total_transactions ?? 0;
                $newTotal = $oldTotal + 1;
                $oldAvg   = $pi->avg_price;

                // Rumus Incremental Moving Average:
                // new_avg = ((old_avg * n) + new_price) / (n + 1)
                $newAvg = (($oldAvg * $oldTotal) + $price) / $newTotal;

                $pi->update([
                    'avg_price'          => round($newAvg, 2),
                    'total_transactions' => $newTotal,
                    'last_calculated_at' => now(),
                ]);

                Log::info('📈 [PriceIndex] Adaptive Avg Updated', [
                    'item'    => $pi->item_name,
                    'old_avg' => $oldAvg,
                    'new_avg' => $newAvg,
                    'n'       => $newTotal
                ]);
            } else {
                Log::info('⏭️ [PriceIndex] Outside range, skipping auto-update', [
                    'item'  => $pi->item_name,
                    'price' => $price,
                    'range' => "[{$pi->min_price} - {$pi->max_price}]"
                ]);
            }

            return $pi;
        });
    }

    // ════════════════════════════════════════════════════════
    //  AUTO-CALCULATION DARI HISTORI
    // ════════════════════════════════════════════════════════

    /**
     * Hitung ulang min/max/avg price index dari data transaksi approved.
     * Menggunakan IQR untuk filter outlier.
     * Dipanggil ketika transaksi diapprove, atau manual via artisan.
     */
    public function recalculateFromHistory(string $itemName, ?string $category = null): ?PriceIndex
    {
        // Ambil semua harga dari transaksi approved yang mengandung item ini
        $approvedPrices = $this->getApprovedPricesForItem($itemName);

        if ($approvedPrices->count() < 3) {
            Log::info('⚠️ [PriceIndex] Data tidak cukup untuk recalculate', [
                'item_name' => $itemName,
                'count'     => $approvedPrices->count(),
            ]);
            return null;
        }

        // Filter outlier dengan IQR
        $cleanedPrices = $this->removeOutliers($approvedPrices->toArray());

        if (empty($cleanedPrices)) {
            return null;
        }

        $minPrice = min($cleanedPrices);
        $maxPrice = max($cleanedPrices);
        
        // OPTIMIZATION: Weighted Average (Data terbaru lebih berbobot)
        // Saat ini kita gunakan Simple Average dulu, namun IQR sudah membuang outlier.
        $avgPrice = array_sum($cleanedPrices) / count($cleanedPrices);

        $priceIndex = PriceIndex::where('item_name', $itemName)->first();

        $updateData = [
            'total_transactions'   => $approvedPrices->count(),
            'last_calculated_at'   => now(),
            'calculated_min_price' => $minPrice,
            'calculated_max_price' => $maxPrice,
            'calculated_avg_price' => round($avgPrice, 2),
        ];

        if ($priceIndex && $priceIndex->is_manual) {
            // Jika manual, kita tetep update info "Calculated" tapi jangan sentuh active prices
            $priceIndex->update($updateData);
            return $priceIndex;
        }

        if ($priceIndex) {
            $updateData['min_price'] = $minPrice;
            $updateData['max_price'] = $maxPrice;
            $updateData['avg_price'] = round($avgPrice, 2);
            $updateData['is_manual'] = false;
            
            $priceIndex->update($updateData);
        } else {
            $createData = array_merge([
                'item_name' => $itemName,
                'category'  => $category,
                'unit'      => 'pcs',
                'is_manual' => false,
            ], $updateData);

            // Karena data baru (auto), copy calculated ke active
            $createData['min_price'] = $minPrice;
            $createData['max_price'] = $maxPrice;
            $createData['avg_price'] = round($avgPrice, 2);

            $priceIndex = PriceIndex::create($createData);
        }

        Log::info('📊 [PriceIndex] Recalculated', [
            'item_name' => $itemName,
            'min'       => $minPrice,
            'max'       => $maxPrice,
            'avg'       => $avgPrice,
            'from_n'    => count($cleanedPrices),
        ]);

        return $priceIndex;
    }

    // ════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════════

    /**
     * Ambil semua harga terkait item dari transaksi approved (6 bulan terakhir).
     * OPTIMIZED: Filter JSON di level database jika memungkinkan, 
     * namun karena struktur 'items' adalah array, kita ambil data minimal.
     */
    private function getApprovedPricesForItem(string $itemName): \Illuminate\Support\Collection
    {
        return Transaction::query()
            ->select('items')
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->subMonths(6))
            ->whereNotNull('items')
            // Tambahkan hint index jika table sangat besar
            ->cursor() 
            ->flatMap(function (Transaction $t) use ($itemName) {
                return collect($t->items ?? [])
                    ->filter(fn($item) => strtolower(trim($item['customer'] ?? '')) === strtolower(trim($itemName)))
                    ->map(fn($item) => intval($item['estimated_price'] ?? 0))
                    ->filter(fn($p) => $p > 0);
            })
            ->values()
            ->collect();
    }

    /**
     * Hapus outlier menggunakan metode IQR (1.5x IQR rule).
     */
    private function removeOutliers(array $prices): array
    {
        if (count($prices) < 4) {
            return $prices; // Tidak cukup data untuk IQR
        }

        sort($prices);
        $count = count($prices);

        $q1 = $prices[(int) floor(($count - 1) / 4)];
        $q3 = $prices[(int) ceil(($count - 1) * 3 / 4)];
        $iqr = $q3 - $q1;

        $lowerBound = $q1 - 1.5 * $iqr;
        $upperBound = $q3 + 1.5 * $iqr;

        return array_values(array_filter($prices, fn($p) => $p >= $lowerBound && $p <= $upperBound));
    }
}
