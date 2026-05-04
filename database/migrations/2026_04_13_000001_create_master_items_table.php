<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_items', function (Blueprint $table) {
            $table->id();

            // Nama terstandarisasi (lowercase, trimmed) — digunakan untuk matching
            $table->string('canonical_name', 255)->unique();

            // Nama tampilan asli (diambil dari input pertama kali)
            $table->string('display_name', 255);

            // SKU opsional — untuk item yang memiliki kode produk
            $table->string('sku', 100)->nullable()->unique();

            // Kategori — referensi ke string kategori sesuai skema PriceIndex saat ini
            $table->string('category', 255)->nullable()->index();

            // Spesifikasi tambahan (brand, type, dsb) — JSON opsional
            $table->json('specifications')->nullable();

            // Alias / typo yang diketahui — JSON array of strings
            // Contoh: ["kabel nym 3x2.5", "kable nym", "KBL NYM 3X2.5"]
            $table->json('aliases')->nullable();

            // Status item
            $table->enum('status', ['active', 'discontinued', 'pending_approval'])
                  ->default('active')
                  ->index();

            // Audit: siapa yang membuat & approve
            $table->foreignId('created_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->foreignId('approved_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index reguler untuk category + status
            $table->index(['category', 'status']);
        });

        // FULLTEXT index untuk pencarian string cepat (>5x lebih cepat dari LIKE '%...%')
        // Harus dibuat via raw statement karena Blueprint tidak mendukung FULLTEXT
        DB::statement('ALTER TABLE master_items ADD FULLTEXT INDEX ft_master_items_canonical (canonical_name)');

        // ── Tambah kolom master_item_id ke price_indexes ──────────────────────
        Schema::table('price_indexes', function (Blueprint $table) {
            $table->foreignId('master_item_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('master_items')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Lepas foreign key dulu sebelum drop tabel
        Schema::table('price_indexes', function (Blueprint $table) {
            $table->dropForeign(['master_item_id']);
            $table->dropColumn('master_item_id');
        });

        Schema::dropIfExists('master_items');
    }
};
