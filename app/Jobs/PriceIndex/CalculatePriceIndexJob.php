<?php

namespace App\Jobs\PriceIndex;

use App\Models\PriceIndex;
use App\Services\PriceIndex\PriceIndexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CalculatePriceIndexJob
 *
 * Recalculate min/max/avg price index dari histori transaksi approved
 * menggunakan IQR outlier filtering.
 */
class CalculatePriceIndexJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // ❌ HAPUS: public $queue = 'default';
    public int    $tries = 3;
    public int    $timeout = 120;

    /**
     * Unique ID berdasarkan nama item agar tidak terjadi double calculation
     * di waktu yang bersamaan (Race Condition).
     */
    public function uniqueId(): string
    {
        return 'price_recalc_' . md5(strtolower(trim($this->itemName)));
    }

    /**
     * Masa berlaku lock: 10 menit
     */
    public int $uniqueFor = 600;

    public function __construct(
        public readonly string  $itemName,
        public readonly ?string $category = null
    ) {
        // ✅ TAMBAH: Set queue menggunakan method dari trait
        $this->onQueue('default');
    }

    public function handle(PriceIndexService $service): void
    {
        // Guard: skip jika sudah di-set manual oleh Owner/Atasan
        $existing = PriceIndex::where('item_name', $this->itemName)->first();
        if ($existing?->is_manual) {
            Log::info('⏭️ [CalculatePriceIndex] Skipped — manual override active', [
                'item_name'     => $this->itemName,
                'manual_set_by' => $existing->manual_set_by,
                'manual_set_at' => $existing->manual_set_at,
            ]);
            return;
        }

        $result = $service->recalculateFromHistory($this->itemName, $this->category);

        if ($result) {
            Log::info('✅ [CalculatePriceIndex] Done', [
                'item_name' => $this->itemName,
                'min'       => $result->min_price,
                'max'       => $result->max_price,
                'avg'       => $result->avg_price,
                'n'         => $result->total_transactions,
            ]);
        } else {
            Log::info('⚠️ [CalculatePriceIndex] Insufficient data, skipped', [
                'item_name' => $this->itemName,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('❌ [CalculatePriceIndex] Failed', [
            'item_name' => $this->itemName,
            'error'     => $e->getMessage(),
        ]);
    }

    /**
     * Exponential backoff: 30s, 2min, 10min
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }
}