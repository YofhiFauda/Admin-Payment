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
        public readonly float   $price = 0,
        public readonly ?string $category = null
    ) {
        // ✅ TAMBAH: Set queue menggunakan method dari trait
        $this->onQueue('default');
    }

    public function handle(PriceIndexService $service): void
    {
        // Guard: Jika harga 0, lakukan full recalculation saja (fallback)
        if ($this->price <= 0) {
            $service->recalculateFromHistory($this->itemName, $this->category);
            return;
        }

        // Panggil logika Auto-Adaptive Update
        $result = $service->processApprovedItem($this->itemName, $this->price, $this->category);

        if ($result) {
            Log::info('✅ [CalculatePriceIndex] Adaptive Update Done', [
                'item_name' => $this->itemName,
                'price'     => $this->price,
                'new_avg'   => $result->avg_price,
                'total'     => $result->total_transactions,
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