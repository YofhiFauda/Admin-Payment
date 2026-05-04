<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Cek dulu apakah kolom sudah ada
            if (!Schema::hasColumn('transactions', 'has_price_anomaly')) {
                $table->boolean('has_price_anomaly')->default(false)->after('items_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'has_price_anomaly')) {
                $table->dropColumn('has_price_anomaly');
            }
        });
    }
};