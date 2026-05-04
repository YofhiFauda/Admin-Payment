<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan kolom avg_price_manual untuk override manual harga rata-rata.
     * Logika:
     * - Jika avg_price_manual IS NOT NULL → gunakan nilai manual
     * - Jika avg_price_manual IS NULL → gunakan avg_price (otomatis)
     */
    public function up(): void
    {
        Schema::table('price_indexes', function (Blueprint $table) {
            $table->decimal('avg_price_manual', 15, 2)->nullable()->after('avg_price')
                  ->comment('Harga rata-rata manual (override). Jika NULL, gunakan avg_price otomatis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_indexes', function (Blueprint $table) {
            $table->dropColumn('avg_price_manual');
        });
    }
};
