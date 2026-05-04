<?php

namespace App\Console\Commands;

use App\Models\MasterItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Artisan Command: price-index:migrate-populate
 *
 * One-off script untuk menyalin data item dari histori pengajuan
 * ke tabel master_items (Cold Start).
 *
 * Strategi:
 *  1. Baca semua item_name + category unik dari JSON kolom 'items' di tabel transactions
 *  2. Normalisasi (lowercase + trim spasi)
 *  3. firstOrCreate ke master_items dengan status 'active'
 *  4. Tautkan price_indexes yang ada ke master_item_id yang cocok
 *
 * Jalankan SATU KALI setelah migration baru dijalankan:
 *   php artisan price-index:migrate-populate
 *
 * Aman untuk dijalankan ulang (idempotent via firstOrCreate).
 */
class PopulateMasterItemsCommand extends Command
{
    protected $signature   = 'price-index:migrate
                                {--dry-run : Tampilkan hasil tanpa menyimpan ke database}
                                {--chunk=200 : Jumlah transaksi per batch}';

    protected $description = 'Cold Start: Pindahkan data item dari histori transaksi ke tabel master_items';

    public function handle(): int
    {
        $isDryRun  = (bool) $this->option('dry-run');
        $chunkSize = (int)  $this->option('chunk');

        $this->info('🚀 Memulai migrasi V1 → V2 Master Items...');
        $isDryRun && $this->warn('   [DRY RUN] Tidak ada data yang akan disimpan.');

        // ── Step 1: Kumpulkan item unik dari price_indexes (sudah ada) ──────
        $this->info('📋 Step 1: Membaca dari tabel price_indexes...');

        $priceIndexes = DB::table('price_indexes')
            ->select('id', 'item_name', 'category')
            ->orderBy('id')
            ->get();

        $this->info("   Ditemukan {$priceIndexes->count()} price index.");

        $created = 0;
        $linked  = 0;
        $skipped = 0;

        foreach ($priceIndexes as $pi) {
            $canonical = MasterItem::normalize($pi->item_name);

            if (blank($canonical)) {
                $skipped++;
                continue;
            }

            if ($isDryRun) {
                $this->line("   [DRY] Akan buat/cari: \"{$canonical}\" (category: {$pi->category})");
                $created++;
                continue;
            }

            // firstOrCreate memastikan idempotent (aman dijalankan ulang)
            $masterItem = MasterItem::firstOrCreate(
                ['canonical_name' => $canonical],
                [
                    'display_name' => $pi->item_name, // Pertahankan kapitalisasi asli untuk tampilan
                    'category'     => $pi->category,
                    'status'       => 'active',
                ]
            );

            if ($masterItem->wasRecentlyCreated) {
                $created++;
            }

            // Tautkan price_index yang ada ke master_item_id
            $affected = DB::table('price_indexes')
                ->where('id', $pi->id)
                ->whereNull('master_item_id') // Hanya update yang belum tertaut
                ->update(['master_item_id' => $masterItem->id]);

            if ($affected > 0) {
                $linked++;
            }
        }

        // ── Step 2: Kumpulkan alias dari item_name yang mirip ────────────────
        if (!$isDryRun) {
            $this->info('🔗 Step 2: Mendeteksi alias (nama berbeda, barang sama per category)...');
            $this->detectAndRegisterAliases();
        }

        // ── Ringkasan ─────────────────────────────────────────────────────────
        $this->newLine();
        $this->info('✅ Selesai!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['MasterItems dibuat', $created],
                ['PriceIndexes ditautkan', $linked],
                ['Item dilewati (nama kosong)', $skipped],
            ]
        );

        if (!$isDryRun) {
            $total = MasterItem::count();
            $this->info("📊 Total master_items saat ini: {$total}");
        }

        return self::SUCCESS;
    }

    /**
     * Deteksi potensi alias: item_name dengan perbedaan tipis yang sudah ada di database.
     * Hanya mencatat ke log, tidak auto-merge (butuh konfirmasi Owner).
     */
    private function detectAndRegisterAliases(): void
    {
        // Ambil semua item_name lama yang BELUM tertaut ke master_item (jika ada yang terlewat)
        $unlinked = DB::table('price_indexes')
            ->whereNull('master_item_id')
            ->pluck('item_name');

        if ($unlinked->isEmpty()) {
            $this->line('   Semua price_indexes sudah tertaut ke master_items. ✅');
            return;
        }

        $this->warn("   ⚠️  {$unlinked->count()} price_indexes belum tertaut. Jalankan ulang command ini.");
        foreach ($unlinked->take(10) as $name) {
            $this->line("      - \"{$name}\"");
        }
    }
}
