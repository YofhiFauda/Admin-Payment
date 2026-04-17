<?php

namespace App\Jobs\PriceIndex;

use App\Models\PriceIndex;
use App\Models\Transaction;
use App\Services\PriceIndex\PriceIndexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable as QueueableTrait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
class BatchCalculatePriceIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(PriceIndexService $service): void
    {
        Log::info('🔄 [BatchRecalc] Starting incremental batch recalculation...');

        // Query optimal: Ambil item_name dari transaksi 'completed' yang updatenya > last_calculated_at
        // Kita gunakan subquery atau join untuk performa bila data sangat besar, 
        // tapi untuk tahap ini pluck + unique sudah cukup optimal dengan filter updated_at.
        
        $itemsToRecalc = Transaction::where('status', 'completed')
            ->whereNotNull('items')
            ->where(function($q) {
                $q->whereNull('last_calculated_at')
                  ->orWhereColumn('updated_at', '>', 'last_calculated_at');
            })
            ->select('items')
            ->cursor() // Menggunakan cursor agar memori hemat saat data besar
            ->flatMap(function ($trx) {
                return collect($transaction_items = $trx->items ?? [])
                    ->pluck('customer')
                    ->filter()
                    ->unique();
            })
            ->unique()
            ->values();

        $count = 0;
        foreach ($itemsToRecalc as $itemName) {
            // Dispatch individual jobs agar bisa diproses parallel oleh multiple workers
            // dan menghormati interface ShouldBeUnique pada CalculatePriceIndexJob
            dispatch(new CalculatePriceIndexJob($itemName))
                ->onQueue('default');
            $count++;
        }

        Log::info("✅ [BatchRecalc] Queued {$count} items for recalculation.");
    }
}
