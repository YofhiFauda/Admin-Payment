<?php

namespace App\Console\Commands;

use App\Models\TransactionExportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * DiagnoseDownloadCommand
 *
 * Diagnose kenapa download endpoint return 404.
 * Test semua faktor: route, storage, file existence, permission.
 *
 * Usage:
 *   php artisan export:diagnose-download <export-id>
 */
class DiagnoseDownloadCommand extends Command
{
    protected $signature = 'export:diagnose-download {exportId}';
    protected $description = 'Diagnose 404 error on export download';

    public function handle(): int
    {
        $exportId = $this->argument('exportId');
        $this->info("=== Diagnosing download for export {$exportId} ===");
        $this->newLine();

        // ── Step 1: Check route registered ──
        $this->line('Step 1: Check route registered...');
        $routes = Route::getRoutes();
        $downloadRoute = collect($routes)->first(
            fn ($r) => $r->getName() === 'transactions.export.download'
        );

        if (!$downloadRoute) {
            $this->error('❌ Route "transactions.export.download" NOT REGISTERED');
            $this->error('   Run: php artisan route:clear');
            return self::FAILURE;
        }
        $this->info('✅ Route registered');
        $this->line('   URI: ' . $downloadRoute->uri());
        $this->line('   Methods: ' . implode(',', $downloadRoute->methods()));
        $this->line('   Middleware: ' . implode(',', $downloadRoute->gatherMiddleware()));

        // ── Step 2: Find export record ──
        $this->newLine();
        $this->line('Step 2: Find export record in DB...');
        $export = TransactionExportJob::find($exportId);

        if (!$export) {
            $this->error('❌ Export record NOT FOUND in transaction_export_jobs table');
            return self::FAILURE;
        }
        $this->info('✅ Export found');
        $this->table(['Field', 'Value'], [
            ['ID',         $export->id],
            ['User ID',    $export->user_id],
            ['Status',     $export->status],
            ['Filename',   $export->filename],
            ['File path',  $export->file_path],
            ['File size',  number_format($export->file_size ?? 0) . ' bytes'],
            ['Started',    (string) $export->started_at],
            ['Completed',  (string) $export->completed_at],
            ['Duration',   ($export->duration_ms ?? 0) . ' ms'],
            ['Error',      $export->error_message ?? '(none)'],
        ]);

        // ── Step 3: Check file_path is set ──
        $this->newLine();
        $this->line('Step 3: Check file_path is set...');
        if (empty($export->file_path)) {
            $this->error('❌ file_path is empty — file not registered in DB');
            return self::FAILURE;
        }
        $this->info('✅ file_path: ' . $export->file_path);

        // ── Step 4: Check Storage disk resolution ──
        $this->newLine();
        $this->line('Step 4: Check Storage disk resolution...');
        $disk = Storage::disk('local');
        $diskRoot = $disk->path('');
        $absolutePath = $disk->path($export->file_path);

        $this->line('   Disk root: ' . $diskRoot);
        $this->line('   Absolute path: ' . $absolutePath);

        // ── Step 5: Check via Storage::exists() ──
        $this->newLine();
        $this->line('Step 5: Check via Storage::exists()...');
        $existsViaStorage = $disk->exists($export->file_path);
        if ($existsViaStorage) {
            $this->info('✅ Storage::exists() returns TRUE');
            $this->line('   Storage::size() = ' . number_format($disk->size($export->file_path)) . ' bytes');
        } else {
            $this->error('❌ Storage::exists() returns FALSE');
        }

        // ── Step 6: Check via native PHP ──
        $this->newLine();
        $this->line('Step 6: Check via native PHP...');
        $existsNative = file_exists($absolutePath);
        if ($existsNative) {
            $this->info('✅ file_exists() returns TRUE');
            $this->line('   filesize() = ' . number_format(filesize($absolutePath)) . ' bytes');
            $this->line('   is_readable() = ' . (is_readable($absolutePath) ? 'TRUE' : 'FALSE'));
            $this->line('   permissions: ' . substr(sprintf('%o', fileperms($absolutePath)), -4));
        } else {
            $this->error('❌ file_exists() returns FALSE');
            $this->line('   Parent dir exists: ' . (is_dir(dirname($absolutePath)) ? 'YES' : 'NO'));
            $this->line('   Parent listable: ' . (is_readable(dirname($absolutePath)) ? 'YES' : 'NO'));

            // List parent dir
            if (is_dir(dirname($absolutePath))) {
                $this->line('   Parent contents:');
                foreach (glob(dirname($absolutePath) . '/*') as $f) {
                    $this->line('     - ' . basename($f) . ' (' . filesize($f) . ' bytes)');
                }
            }
        }

        // ── Step 7: Generate URL ──
        $this->newLine();
        $this->line('Step 7: Generate download URL...');
        try {
            $url = route('transactions.export.download', ['exportId' => $export->id]);
            $this->info('✅ URL: ' . $url);
        } catch (\Throwable $e) {
            $this->error('❌ Failed to generate URL: ' . $e->getMessage());
        }

        // ── Step 8: Check APP_URL ──
        $this->newLine();
        $this->line('Step 8: Check config...');
        $this->line('   APP_URL: ' . config('app.url'));
        $this->line('   APP_ENV: ' . config('app.env'));
        $this->line('   APP_DEBUG: ' . (config('app.debug') ? 'true' : 'false'));
        $this->line('   FILESYSTEM_DISK: ' . config('filesystems.default'));

        // ── Final verdict ──
        $this->newLine();
        if ($existsViaStorage && $existsNative) {
            $this->info('═══════════════════════════════════════════════');
            $this->info('✅ FILE READY TO DOWNLOAD');
            $this->info('═══════════════════════════════════════════════');
            $this->newLine();
            $this->warn('Jika masih dapat 404 di browser, kemungkinan:');
            $this->line('  1. Session expired → user di-redirect ke login');
            $this->line('     Fix: login ulang, lalu coba download via URL langsung');
            $this->line('  2. Route cache lama → run: php artisan route:clear');
            $this->line('  3. Reverse proxy strip path → cek nginx config');
            return self::SUCCESS;
        }

        $this->error('═══════════════════════════════════════════════');
        $this->error('❌ FILE TIDAK BISA DI-AKSES');
        $this->error('═══════════════════════════════════════════════');
        return self::FAILURE;
    }
}
