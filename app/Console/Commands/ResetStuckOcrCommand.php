<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Events\TransactionUpdated;
use App\Events\OcrStatusUpdated;
use App\Notifications\OcrStatusNotification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Reset atau bypass nota yang stuck di ai_status = queued/processing.
 *
 * ── Mode Error (default) ──────────────────────────────────────────────
 *   php artisan ocr:reset-stuck                  → dry-run
 *   php artisan ocr:reset-stuck --fix            → reset ke ai_status=error
 *   php artisan ocr:reset-stuck --id=42 --fix    → reset satu transaksi
 *
 * ── Mode Complete (bypass ke completed) ───────────────────────────────
 *   php artisan ocr:reset-stuck --id=42 --complete
 *       → force ai_status=completed dari data yang sudah ada di DB
 *
 *   php artisan ocr:reset-stuck --id=42 --complete --from-cache
 *       → apply data dari Redis cache (jika n8n sudah callback, tapi DB belum terupdate)
 *
 *   php artisan ocr:reset-stuck --id=42 --complete --vendor="Toko ABC" --amount=150000
 *       → isi data manual via opsi command
 */
class ResetStuckOcrCommand extends Command
{
    protected $signature = 'ocr:reset-stuck
                            {--fix           : Jalankan reset ke error (tanpa ini = dry-run)}
                            {--complete      : Bypass ke ai_status=completed (status=pending), gunakan data existing/cache/manual}
                            {--from-cache    : Ambil data OCR dari Redis cache (pakai bersama --complete)}
                            {--id=           : Target transaction ID spesifik}
                            {--minutes=10    : Minimum menit dianggap "stuck" (default: 10)}
                            {--status=       : Filter ai_status tertentu (queued/processing)}
                            {--vendor=       : Isi vendor/toko secara manual (pakai bersama --complete)}
                            {--amount=       : Isi total belanja secara manual (pakai bersama --complete)}
                            {--date=         : Isi tanggal nota Y-m-d secara manual (pakai bersama --complete)}';

    protected $description = 'Reset nota stuck di OCR ke error, atau bypass langsung ke completed dengan data OCR';

    public function handle(): int
    {
        $wantComplete = $this->option('complete');
        $isDryRun     = ! $this->option('fix') && ! $wantComplete;
        $specificId   = $this->option('id');
        $minutes      = (int) $this->option('minutes');
        $filterStatus = $this->option('status');

        // ── Validasi: --complete wajib pakai --id ──
        if ($wantComplete && ! $specificId) {
            $this->error('--complete harus disertai --id=<transaction_id>');
            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->warn('🔍 DRY-RUN MODE — tidak ada yang diubah. Tambahkan --fix atau --complete untuk eksekusi.');
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
            $this->info('✅ Tidak ada transaksi stuck yang ditemukan.');
            return self::SUCCESS;
        }

        // ── Tampilkan tabel ──
        $this->table(
            ['ID', 'Invoice', 'Type', 'ai_status', 'Status', 'Vendor', 'Amount', 'Stuck sejak'],
            $stuck->map(fn($t) => [
                $t->id,
                $t->invoice_number ?? '-',
                $t->type,
                $t->ai_status,
                $t->status,
                $t->vendor ?? $t->customer ?? '(kosong)',
                $t->amount ? number_format($t->amount, 0, ',', '.') : '(kosong)',
                $t->updated_at->diffForHumans(),
            ])->toArray()
        );

        $this->line('');
        $this->line("Total ditemukan: <comment>{$stuck->count()} transaksi</comment>");

        if ($isDryRun) {
            $this->line('');
            $this->warn('Pilihan eksekusi:');
            $this->line('  --fix                     → reset ke ai_status=error (isi manual dari UI)');
            $this->line('  --id=X --complete         → bypass ke completed dengan data di DB saat ini');
            $this->line('  --id=X --complete --from-cache → apply data dari Redis cache terlebih dahulu');
            return self::SUCCESS;
        }

        // ═══════════════════════════════════════════════════════════
        //  MODE: COMPLETE (bypass ke completed)
        // ═══════════════════════════════════════════════════════════
        if ($wantComplete) {
            return $this->handleComplete($stuck->first());
        }

        // ═══════════════════════════════════════════════════════════
        //  MODE: ERROR (reset ke error)
        // ═══════════════════════════════════════════════════════════
        return $this->handleError($stuck);
    }

    // ─────────────────────────────────────────────────────────────
    //  Reset ke ai_status = error (bisa diisi manual dari UI)
    // ─────────────────────────────────────────────────────────────
    private function handleError($stuck): int
    {
        if (! $this->confirm("Reset {$stuck->count()} transaksi ke ai_status=error?", true)) {
            $this->info('Dibatalkan.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($stuck as $transaction) {
            Cache::forget("ai_autofill:{$transaction->upload_id}");
            Cache::forget("lock:ai_callback:{$transaction->upload_id}");

            $transaction->update(['ai_status' => 'error']);

            try { broadcast(new TransactionUpdated($transaction->fresh())); } catch (\Exception) {}

            Log::channel('ai_autofill')->warning('🔧 [MANUAL RESET] OCR reset to error', [
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'reset_by'       => 'artisan ocr:reset-stuck --fix',
            ]);

            $this->line("  ✔ [{$transaction->id}] {$transaction->invoice_number} → <info>error</info>");
            $count++;
        }

        $this->line('');
        $this->info("✅ {$count} transaksi direset ke error. Isi data manual dari halaman transaksi.");
        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────
    //  Bypass ke ai_status = completed, status = pending
    //  Prioritas data: --from-cache > opsi manual > data existing DB
    // ─────────────────────────────────────────────────────────────
    private function handleComplete(Transaction $transaction): int
    {
        $this->line('');
        $this->line("Target: <comment>[{$transaction->id}] {$transaction->invoice_number}</comment>");

        // ── Langkah 1: Coba ambil dari Redis cache ──
        $cacheData = null;
        if ($this->option('from-cache')) {
            $cacheKey  = "ai_autofill:{$transaction->upload_id}";
            $cacheData = Cache::get($cacheKey);

            if ($cacheData) {
                $this->info('📦 Data ditemukan di Redis cache:');
                $this->table(
                    ['Field', 'Nilai'],
                    [
                        ['Vendor',      $cacheData['vendor']       ?? '-'],
                        ['Amount',      number_format($cacheData['amount'] ?? 0, 0, ',', '.')],
                        ['Date',        $cacheData['date']         ?? '-'],
                        ['Confidence',  ($cacheData['confidence']  ?? 0) . '%'],
                        ['Items count', count($cacheData['items'] ?? [])],
                    ]
                );
            } else {
                $this->warn("⚠️ Cache '{$cacheKey}' tidak ditemukan di Redis.");
                $this->warn('   n8n mungkin belum mengirim callback, atau cache sudah expired.');

                if (! $this->confirm('Lanjutkan dengan data yang sudah ada di DB?', false)) {
                    return self::SUCCESS;
                }
            }
        }

        // ── Langkah 2: Susun data final ──
        // Prioritas: cache > opsi CLI manual > data existing di DB
        $vendor = $this->option('vendor')
            ?? $cacheData['vendor']
            ?? $transaction->vendor
            ?? $transaction->customer
            ?? null;

        $amount = $this->option('amount')
            ? (int) preg_replace('/\D/', '', $this->option('amount'))
            : ($cacheData['amount'] ?? $transaction->amount ?? null);

        $date = $this->option('date')
            ?? $cacheData['date']
            ?? optional($transaction->date)->format('Y-m-d')
            ?? null;

        $items           = $cacheData['items']           ?? $transaction->items           ?? [];
        $confidence      = $cacheData['confidence']      ?? $transaction->confidence      ?? 0;
        $confidenceLabel = $cacheData['confidence_label']?? $transaction->confidence_label ?? ($confidence > 70 ? 'HIGH' : 'LOW');
        $dppLainnya      = $cacheData['dpp_lainnya']     ?? $transaction->dpp_lainnya     ?? 0;
        $taxAmount       = $cacheData['tax_amount']      ?? $transaction->tax_amount      ?? 0;

        // ── Langkah 3: Preview & Konfirmasi ──
        $this->line('');
        $this->line('📋 <comment>Data yang akan diterapkan:</comment>');
        $this->table(
            ['Field', 'Nilai', 'Sumber'],
            [
                ['Vendor',       $vendor  ?? '(tidak diisi)',  $cacheData && $vendor === ($cacheData['vendor'] ?? null) ? 'cache' : ($this->option('vendor') ? 'manual' : 'DB')],
                ['Amount',       $amount ? 'Rp ' . number_format($amount, 0, ',', '.') : '(tidak diisi)', $cacheData && $amount === ($cacheData['amount'] ?? null) ? 'cache' : ($this->option('amount') ? 'manual' : 'DB')],
                ['Date',         $date    ?? '(tidak diisi)',  $cacheData && $date === ($cacheData['date'] ?? null) ? 'cache' : ($this->option('date') ? 'manual' : 'DB')],
                ['Items',        count($items) . ' item',      $cacheData ? 'cache' : 'DB'],
                ['Confidence',   $confidence . '% (' . $confidenceLabel . ')', $cacheData ? 'cache' : 'DB'],
                ['ai_status',    'completed',                 'force'],
                ['status',       'pending',                   'force'],
            ]
        );

        if (! $this->confirm('Terapkan perubahan ini?', true)) {
            $this->info('Dibatalkan.');
            return self::SUCCESS;
        }

        // ── Langkah 4: Update Transaction ──
        $updateData = [
            'ai_status'          => 'completed',
            'status'             => 'pending',
            'confidence'         => $confidence,
            'overall_confidence' => $confidence,
            'confidence_label'   => $confidenceLabel,
        ];

        if ($vendor)      $updateData['vendor']      = $vendor;
        if ($vendor)      $updateData['customer']    = $vendor;
        if ($amount)      $updateData['amount']      = $amount;
        if ($date)        $updateData['date']        = $date;
        if ($items)       $updateData['items']       = $items;
        if ($dppLainnya)  $updateData['dpp_lainnya'] = $dppLainnya;
        if ($taxAmount)   $updateData['tax_amount']  = $taxAmount;

        $transaction->update($updateData);

        // ── Langkah 5: Update cache agar polling endpoint juga tahu ──
        if ($cacheData) {
            $cacheData['status'] = 'completed';
            Cache::put("ai_autofill:{$transaction->upload_id}", $cacheData, now()->addMinutes(30));
        }

        Cache::forget("lock:ai_callback:{$transaction->upload_id}");

        // ── Langkah 6: Broadcast update ke frontend ──
        try {
            broadcast(new TransactionUpdated($transaction->fresh()));

            $submitter = User::find($transaction->submitted_by);
            if ($submitter) {
                $submitter->notify(new OcrStatusNotification(
                    transaction: $transaction,
                    aiStatus: 'completed',
                    confidence: $confidence,
                ));
                broadcast(new OcrStatusUpdated($submitter->id, [
                    'transaction_id'   => $transaction->id,
                    'invoice_number'   => $transaction->invoice_number,
                    'ai_status'        => 'completed',
                    'confidence'       => $confidence,
                    'confidence_label' => $confidenceLabel,
                    'message'          => 'Auto-fill selesai (bypass manual).',
                    'transaction'      => $transaction->fresh()->toSearchArray(),
                ]));
            }
        } catch (\Exception $e) {
            $this->warn('⚠️ Broadcast gagal (non-critical): ' . $e->getMessage());
        }

        Log::channel('ai_autofill')->info('🔧 [MANUAL BYPASS] OCR bypassed to completed', [
            'transaction_id' => $transaction->id,
            'invoice_number' => $transaction->invoice_number,
            'vendor'         => $vendor,
            'amount'         => $amount,
            'source'         => $cacheData ? 'cache' : 'existing_db',
            'bypass_by'      => 'artisan ocr:reset-stuck --complete',
        ]);

        $this->line('');
        $this->info("✅ [{$transaction->id}] {$transaction->invoice_number} → ai_status=<info>completed</info>, status=<info>pending</info>");
        $this->line('Loading screen di browser akan berhenti otomatis dan form auto-fill akan terisi.');

        return self::SUCCESS;
    }
}
