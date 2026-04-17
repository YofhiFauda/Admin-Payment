<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('price_indexes', function (Blueprint $table) {
            $table->decimal('calculated_min_price', 15, 2)->default(0)->after('avg_price');
            $table->decimal('calculated_max_price', 15, 2)->default(0)->after('calculated_min_price');
            $table->decimal('calculated_avg_price', 15, 2)->default(0)->after('calculated_max_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_indexes', function (Blueprint $table) {
            $table->dropColumn(['calculated_min_price', 'calculated_max_price', 'calculated_avg_price']);
        });
    }
};
