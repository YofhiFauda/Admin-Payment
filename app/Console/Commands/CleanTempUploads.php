<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanTempUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-temp-uploads {--hours=24 : Umur file dalam jam sebelum dihapus}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan file sampah (orphaned) di direktori temp-uploads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $this->info("Memulai pembersihan temp-uploads yang lebih lama dari {$hours} jam...");

        $directory = storage_path('app/public/temp-uploads');

        if (!File::exists($directory)) {
            $this->warn("Direktori {$directory} tidak ditemukan.");
            return;
        }

        $files = File::files($directory);
        $now = Carbon::now();
        $deletedCount = 0;

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp(File::lastModified($file->getPathname()));
            
            if ($now->diffInHours($lastModified) >= $hours) {
                File::delete($file->getPathname());
                $deletedCount++;
                Log::info("CleanTempUploads: Berhasil menghapus file sampah: {$file->getFilename()}");
                $this->line("Dihapus: {$file->getFilename()}");
            }
        }

        $this->info("Pembersihan selesai. {$deletedCount} file telah dihapus.");
    }
}
