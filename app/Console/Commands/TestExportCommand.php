<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use App\Services\Export\TransactionExportWriter;
use Illuminate\Console\Command;

/**
 * TestExportCommand
 *
 * Diagnostic command untuk test export tanpa lewat HTTP.
 * Berguna untuk debug error 500 yang sulit ditangkap dari log.
 *
 * Usage:
 *   php artisan export:test
 *   php artisan export:test --type=pengajuan
 *   php artisan export:test --type=rembush --month=5 --year=2026
 *   php artisan export:test --user-id=1 --limit=10
 */
class TestExportCommand extends Command
{
    protected $signature = 'export:test
        {--type=rembush : pengajuan|rembush|gudang|all}
        {--month= : Bulan (1-12)}
        {--year= : Tahun}
        {--status= : Status filter}
        {--branch-id= : Branch ID filter}
        {--user-id= : Force user ID (simulate teknisi)}
        {--limit= : Limit jumlah row untuk test cepat}
        {--output= : Output path (default: storage/app/exports/test.xlsx)}';

    protected $description = 'Test export Excel — diagnostic tool';

    public function handle(): int
    {
        $this->info('=== Export Diagnostic Tool ===');
        $this->newLine();

        // ── Step 1: Check OpenSpout ──
        $this->line('Step 1: Check OpenSpout class loadable...');
        if (!class_exists(\OpenSpout\Writer\XLSX\Writer::class)) {
            $this->error('❌ OpenSpout\\Writer\\XLSX\\Writer NOT FOUND');
            $this->error('   Run: composer install --no-dev');
            return self::FAILURE;
        }
        $this->info('✅ OpenSpout loadable');

        // ── Step 2: Check ext-zip ──
        $this->line('Step 2: Check ext-zip...');
        if (!extension_loaded('zip')) {
            $this->error('❌ PHP zip extension NOT INSTALLED');
            $this->error('   Run: docker-php-ext-install zip');
            return self::FAILURE;
        }
        $this->info('✅ ext-zip available');

        // ── Step 3: Check storage path ──
        $this->line('Step 3: Check storage path writable...');
        $storagePath = storage_path('app/private');
        if (!is_writable($storagePath) && !is_writable(storage_path('app'))) {
            $this->error("❌ Storage path NOT WRITABLE: {$storagePath}");
            $this->error('   Run: chmod -R 775 storage/app');
            return self::FAILURE;
        }
        $this->info('✅ Storage writable');

        // ── Step 4: Check DB connection ──
        $this->line('Step 4: Check DB connection...');
        try {
            $count = Transaction::count();
            $this->info("✅ DB connected. Total transactions: {$count}");
        } catch (\Throwable $e) {
            $this->error("❌ DB error: {$e->getMessage()}");
            return self::FAILURE;
        }

        // ── Step 5: Build filters ──
        $filters = array_filter([
            'type'      => $this->option('type'),
            'month'     => $this->option('month'),
            'year'      => $this->option('year') ?? now()->year,
            'status'    => $this->option('status'),
            'branch_id' => $this->option('branch-id'),
        ], fn ($v) => $v !== null && $v !== '');

        $this->line('Step 5: Filters:');
        $this->table(
            ['Key', 'Value'],
            collect($filters)->map(fn ($v, $k) => [$k, $v])->values()->toArray()
        );

        // ── Step 6: Build writer & test count ──
        $this->line('Step 6: Build writer & count transactions...');
        $forceUserId = null;
        if ($userId = $this->option('user-id')) {
            $forceUserId = (int) $userId;
            $this->warn("Force user_id = {$forceUserId} (simulate teknisi)");
        }

        try {
            $writer = new TransactionExportWriter($filters, $forceUserId);
            $totalTx = $writer->countTransactions();
            $this->info("✅ Will export {$totalTx} transactions");

            if ($totalTx === 0) {
                $this->warn('⚠️  No transactions match filter — file akan kosong');
            }
        } catch (\Throwable $e) {
            $this->error("❌ Writer build failed: {$e->getMessage()}");
            $this->error("   File: {$e->getFile()}:{$e->getLine()}");
            return self::FAILURE;
        }

        // ── Step 7: Generate file ──
        $output = $this->option('output')
            ?? storage_path('app/exports/test_' . now()->format('Ymd_His') . '.xlsx');

        $dir = dirname($output);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->line("Step 7: Writing to {$output}...");
        $start = microtime(true);

        try {
            $stats = $writer->writeToFile($output);
            $duration = round(microtime(true) - $start, 2);
            $size = file_exists($output) ? filesize($output) : 0;

            $this->newLine();
            $this->info('✅ EXPORT SUCCESS');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Output file',     $output],
                    ['File size',       number_format($size) . ' bytes (' . round($size / 1024, 2) . ' KB)'],
                    ['Transactions',    $stats['transactions']],
                    ['Rows written',    $stats['rows']],
                    ['Duration (ms)',   $stats['duration_ms']],
                    ['Wall clock (s)',  $duration],
                    ['Memory peak (MB)', round(memory_get_peak_usage() / 1024 / 1024, 2)],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('❌ EXPORT FAILED');
            $this->error("   Message: {$e->getMessage()}");
            $this->error("   File:    {$e->getFile()}:{$e->getLine()}");
            $this->error("   Class:   " . get_class($e));
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());

            // Cleanup
            if (file_exists($output)) {
                @unlink($output);
            }

            return self::FAILURE;
        }
    }
}
