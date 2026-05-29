<?php

namespace App\Jobs;

use App\Events\ExportStatusUpdated;
use App\Models\TransactionExportJob as ExportJobModel;
use App\Services\Export\TransactionExportWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ExportTransactionsJob
 *
 * Background job untuk generate Excel export dengan progress tracking + Reverb broadcast.
 *
 * NB: Tidak pakai `ShouldBeUnique` karena uniqueness sudah di-handle di
 * controller (cek `TransactionExportJob` aktif sebelum dispatch). Ini
 * menghindari potential cache lock issue saat Redis bermasalah.
 */
class ExportTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 600; // 10 menit — cukup untuk 100k+ rows

    public function __construct(
        public readonly string $exportId,
        public readonly int    $userId,
        public readonly array  $filters,
        public readonly ?int   $forceUserId = null,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $export = ExportJobModel::find($this->exportId);

        if (!$export) {
            Log::warning('[ExportJob] Record tidak ditemukan, skip job', [
                'export_id' => $this->exportId,
            ]);
            return;
        }

        $export->markAsProcessing();
        $this->safeBroadcast('processing', [
            'progress_percent' => 0,
            'processed'        => 0,
        ]);

        Log::info('[ExportJob] START', [
            'export_id' => $this->exportId,
            'user_id'   => $this->userId,
            'filters'   => $this->filters,
        ]);

        try {
            $writer = new TransactionExportWriter(
                filters: $this->filters,
                forceUserId: $this->forceUserId,
                progressCallback: function (int $processed, int $total) use ($export) {
                    $export->updateProgress($processed, $total);
                    $this->safeBroadcast('processing', [
                        'progress_percent' => $export->progress_percent,
                        'processed'        => $processed,
                        'total'            => $total,
                    ]);
                },
            );

            // Pre-count untuk progress bar akurat
            $totalTx = $writer->countTransactions();
            $export->update(['total_transactions' => $totalTx]);

            // ── Persiapkan path penyimpanan ──
            $filename     = $writer->buildFilename();
            $relativePath = "exports/{$this->userId}/{$this->exportId}.xlsx";

            $disk = Storage::disk('local');
            $disk->makeDirectory("exports/{$this->userId}");
            $absolutePath = $disk->path($relativePath);

            // ── PENTING: Permission fix ──
            // Horizon worker jalan sebagai root, tapi PHP-FPM worker jalan
            // sebagai www-data. Tanpa fix ini, www-data tidak bisa baca file
            // → download endpoint gagal 500.
            //
            // Set permission 0755 untuk folder dan 0644 untuk file (default
            // umask root = 0022 yang result jadi 0755/0644 — tapi makeDirectory
            // di-create dengan umask shell yang variatif).
            $userDir   = $disk->path("exports/{$this->userId}");
            $exportDir = $disk->path("exports");
            @chmod($exportDir, 0755);
            @chmod($userDir, 0755);

            // ── Generate file ──
            $stats = $writer->writeToFile($absolutePath);

            // ── Verify file benar-benar tersimpan ──
            if (!$disk->exists($relativePath)) {
                throw new \RuntimeException("File tidak tersimpan di disk: {$relativePath} (absolute: {$absolutePath})");
            }

            // Set file permission readable by everyone
            @chmod($absolutePath, 0644);

            $fileSize = $disk->size($relativePath);

            Log::info('[ExportJob] File written', [
                'export_id'     => $this->exportId,
                'relative_path' => $relativePath,
                'absolute_path' => $absolutePath,
                'file_size'     => $fileSize,
                'rows'          => $stats['rows'],
            ]);

            $export->update(['filename' => $filename]);
            $export->markAsCompleted($relativePath, $fileSize, $stats['duration_ms']);

            // ── Generate download URL ──
            $downloadUrl = $this->buildDownloadUrl();

            $this->safeBroadcast('completed', [
                'progress_percent' => 100,
                'processed'        => $stats['transactions'],
                'total'            => $stats['transactions'],
                'filename'         => $filename,
                'file_size'        => $fileSize,
                'download_url'     => $downloadUrl,
            ]);

            Log::info('[ExportJob] SUCCESS', [
                'export_id'    => $this->exportId,
                'rows'         => $stats['rows'],
                'transactions' => $stats['transactions'],
                'duration_ms'  => $stats['duration_ms'],
                'file_size'    => $fileSize,
            ]);
        } catch (\Throwable $e) {
            $export->markAsFailed($e->getMessage());

            $this->safeBroadcast('failed', [
                'error_message' => 'Gagal membuat laporan: ' . $e->getMessage(),
            ]);

            Log::error('[ExportJob] FAILED', [
                'export_id' => $this->exportId,
                'error'     => $e->getMessage(),
                'file'      => $e->getFile() . ':' . $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw agar Horizon retry
        }
    }

    public function failed(\Throwable $e): void
    {
        $export = ExportJobModel::find($this->exportId);
        if ($export && !$export->isDone()) {
            $export->markAsFailed($e->getMessage());
        }

        $this->safeBroadcast('failed', [
            'error_message' => 'Export gagal setelah retry: ' . $e->getMessage(),
        ]);

        Log::error('[ExportJob] FAILED PERMANENTLY', [
            'export_id' => $this->exportId,
            'error'     => $e->getMessage(),
        ]);
    }

    /**
     * Broadcast yang aman — tidak meledak kalau Reverb down.
     * Failover: skip broadcast, biar polling yang handle.
     */
    private function safeBroadcast(string $status, array $extra = []): void
    {
        try {
            broadcast(new ExportStatusUpdated($this->userId, array_merge([
                'export_id' => $this->exportId,
                'status'    => $status,
            ], $extra)));
        } catch (\Throwable $e) {
            Log::warning('[ExportJob] Broadcast failed (non-fatal)', [
                'export_id' => $this->exportId,
                'status'    => $status,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate download URL — pakai PLAIN URL (tanpa signed signature).
     *
     * RATIONALE: Signed URL tidak reliable saat hit lewat reverse proxy /
     * Cloudflare tunnel karena signature di-compute pakai APP_URL host,
     * sementara user akses via host yang berbeda. Hash mismatch = 403.
     *
     * Kita authorize via session (Auth::id() check di controller download()),
     * yang lebih robust dan otomatis kompatibel dengan proxy chain.
     */
    private function buildDownloadUrl(): string
    {
        return route('transactions.export.download', ['exportId' => $this->exportId]);
    }

    public function backoff(): array
    {
        return [30, 120]; // 30s, 2min
    }
}
