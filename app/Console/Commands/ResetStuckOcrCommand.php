<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Events\TransactionUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Reset nota yang stuck di ai_status = queued/processing.
 *
 * Usage:
 *   php artisan ocr:reset-stuck          → dry-run, lihat daftar stuck
 *   php artisan ocr:reset-stuck --fix    → benar-benar reset ke 'error'
 *   php artisan ocr:reset-stuck --id=42  → reset satu transaksi tertentu
 *   php artisan ocr:reset-stuck --minutes=5 --fix → stuck > 5 menit saja
 */
class ResetStuckOcrCommand extends Command
{
    protected $signature = 'ocr:reset-stuck
                            {--fix        : Jalankan reset (tanpa ini = dry-run saja)}
                            {--id=        : Reset satu transaction ID spesifik}
                            {--minutes=10 : Minimum menit dianggap "stuck" (default: 10)}
                            {--status=    : Filter ai_status tertentu (queued/processing)}';

    protected $description = 'Reset nota yang stuck di antrian OCR (queued/processing) menjadi error agar bisa diisi manual';

    public function handle(): int
    {
        $isDryRun  = ! $this->option('fix');
        $specificId = $this->option('id');
        $minutes   = (int) $this->option('minutes');
        $filterStatus = $this->option('status');

        if ($isDryRun) {
            $this->warn('🔍 DRY-RUN MODE — tidak ada yang diubah. Tambahkan --fix untuk eksekusi.');
        }

        // ── Build Query ──
        $query = Transaction::whereIn('ai_status', $filterStatus
            ? [$filterStatus]
            : ['queued', 'processing']
        );

        if ($specificId) {
            $query->where('id', $specificId);
        } else {
            $query->where('updated_at', '<=', now()->subMinutes($minutes));
        }

        $stuck = $query->orderBy('updated_at')->get();

        if ($stuck->isEmpty()) {
            $this->info('✅ Tidak ada transaksi yang stuck.');
            return self::SUCCESS;
        }

        // ── Tampilkan tabel ──
        $this->table(
            ['ID', 'Invoice', 'Type', 'ai_status', 'Status', 'Stuck sejak'],
            $stuck->map(fn($t) => [
                $t->id,
                $t->invoice_number ?? '-',
                $t->type,
                $t->ai_status,
                $t->status,
                $t->updated_at->diffForHumans(),
            ])->toArray()
        );

        $this->line('');
        $this->line("Total ditemukan: <comment>{$stuck->count()} transaksi</comment>");

        if ($isDryRun) {
            $this->line('');
            $this->warn('Jalankan dengan --fix untuk mereset semua di atas ke ai_status=error.');
            return self::SUCCESS;
        }

        // ── Konfirmasi sebelum eksekusi ──
        if (! $this->confirm("Reset {$stuck->count()} transaksi ke ai_status=error?", true)) {
            $this->info('Dibatalkan.');
            return self::SUCCESS;
        }

        // ── Eksekusi ──
        $resetAt = now();
        $count   = 0;

        foreach ($stuck as $transaction) {
            // Hapus cache Redis yang mungkin masih bergantung
            Cache::forget("ai_autofill:{$transaction->upload_id}");
            Cache::forget("lock:ai_callback:{$transaction->upload_id}");

            $transaction->update([
                'ai_status'   => 'error',
                'status'      => $transaction->status === 'pending' ? 'pending' : $transaction->status,
                'updated_at'  => $resetAt,
            ]);

            // Broadcast ke frontend agar loading screen berhenti
            try {
                broadcast(new TransactionUpdated($transaction->fresh()));
            } catch (\Exception $e) {
                // Tidak critical jika broadcast gagal
            }

            Log::channel('ai_autofill')->warning('🔧 [MANUAL RESET] Stuck OCR transaction reset', [
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'old_ai_status'  => 'queued/processing',
                'new_ai_status'  => 'error',
                'reset_by'       => 'artisan ocr:reset-stuck',
            ]);

            $this->line("  ✔ [{$transaction->id}] {$transaction->invoice_number} → <info>error</info>");
            $count++;
        }

        $this->line('');
        $this->info("✅ {$count} transaksi berhasil direset ke ai_status=error.");
        $this->line('Pengguna dapat mengisi data nota secara manual dari halaman transaksi.');

        return self::SUCCESS;
    }
}
