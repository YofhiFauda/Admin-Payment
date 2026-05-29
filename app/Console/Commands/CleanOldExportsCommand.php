<?php

namespace App\Console\Commands;

use App\Models\TransactionExportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * CleanOldExportsCommand
 *
 * Hapus file export yang sudah lebih dari 24 jam untuk hemat disk space.
 * Dijadwalkan via app/Console/Kernel.php.
 *
 * Usage:
 *   php artisan exports:clean
 */
class CleanOldExportsCommand extends Command
{
    protected $signature = 'exports:clean {--hours=24 : Delete exports older than X hours}';
    protected $description = 'Clean up old export files and database records';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $this->info("Cleaning exports older than {$hours} hours (before {$cutoff})...");

        $exports = TransactionExportJob::where('completed_at', '<', $cutoff)
            ->whereIn('status', ['completed', 'failed'])
            ->get();

        $deletedFiles = 0;
        $deletedRecords = 0;

        foreach ($exports as $export) {
            // Delete file if exists
            if ($export->file_path && Storage::disk('local')->exists($export->file_path)) {
                Storage::disk('local')->delete($export->file_path);
                $deletedFiles++;
            }

            // Delete record
            $export->delete();
            $deletedRecords++;
        }

        $this->info("✅ Deleted {$deletedFiles} files and {$deletedRecords} records.");

        return self::SUCCESS;
    }
}
