<?php

namespace App\Console\Commands;

use App\Jobs\PriceIndex\CalculatePriceIndexJob;
use App\Models\PriceIndex;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * php artisan price-index:recalculate [--mode=incremental|full] [--item=STRING]
 *
 * incremental (default): Hanya recalculate item yang ada transaksi approved baru
 *                        dalam 24 jam terakhir. Ringan, cocok untuk cron harian.
 * full:                  Semua item non-manual. Cocok untuk weekly safety net.
 * item:                  Recalculate satu item spesifik (dev/debug).
 */
class RecalculatePriceIndexes extends Command
{
    protected $signature = 'price-index:recalculate
                           {--mode=incremental : Mode: incremental | full | item}
                           {--item= : Nama item spesifik (hanya untuk mode=item)}
                           {--force : Force recalculate meskipun is_manual=true}';

    protected $description = 'Recalculate price index dari histori transaksi approved';

    public function handle(): int
    {
        $mode  = $this->option('mode');
        $force = (bool) $this->option('force');

        match ($mode) {
            'incremental' => $this->runIncremental($force),
            'full'        => $this->runFull($force),
            'item'        => $this->runSingleItem($force),
            default       => $this->error("Mode tidak dikenal: {$mode}. Gunakan incremental, full, atau item."),
        };

        return self::SUCCESS;
    }

    // ────────────────────────────────────────────────
    //  INCREMENTAL — Hanya item dengan transaksi baru
    // ────────────────────────────────────────────────

    private function runIncremental(bool $force): void
    {
        $this->info('🔄 Mode: Incremental (transaksi approved dalam 24 jam terakhir)');

        dispatch(new \App\Jobs\PriceIndex\BatchCalculatePriceIndexJob())
            ->onQueue('default');

        $this->info('✅ Batch incremental recalculation job dispatched.');
    }

    // ────────────────────────────────────────────────
    //  FULL — Semua item non-manual
    // ────────────────────────────────────────────────

    private function runFull(bool $force): void
    {
        $query = PriceIndex::query();

        if (!$force) {
            $query->where('is_manual', false);
        }

        $total = $query->count();
        $this->info("🔄 Mode: Full recalculation untuk {$total} items...");

        if ($total === 0) {
            $this->info('✅ Tidak ada item untuk diproses.');
            return;
        }

        $bar = $this->output->createProgressBar($total);

        $query->orderBy('id')->chunk(100, function ($priceIndexes) use ($bar) {
            foreach ($priceIndexes as $pi) {
                dispatch(new CalculatePriceIndexJob($pi->item_name, $pi->category))
                    ->onQueue('default')
                    ->delay(now()->addSeconds(rand(1, 30))); // Staggered untuk hindari spike
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('✅ Full recalculation jobs dispatched.');
    }

    // ────────────────────────────────────────────────
    //  SINGLE ITEM
    // ────────────────────────────────────────────────

    private function runSingleItem(bool $force): void
    {
        $itemName = $this->option('item');

        if (!$itemName) {
            $this->error('Gunakan --item="Nama Barang" untuk mode item.');
            return;
        }

        $existing = PriceIndex::where('item_name', $itemName)->first();

        if ($existing?->is_manual && !$force) {
            $this->warn("⚠️ Item '{$itemName}' berstatus manual. Gunakan --force untuk override.");
            return;
        }

        dispatch(new CalculatePriceIndexJob($itemName, $existing?->category));
        $this->info("✅ Recalculation job dispatched untuk: {$itemName}");
    }
}
