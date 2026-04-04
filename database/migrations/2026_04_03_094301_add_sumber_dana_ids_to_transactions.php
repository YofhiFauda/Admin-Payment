<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom JSON sumber_dana_data untuk mendukung
     * multi sumber dana dengan nominal pembayaran per cabang.
     * 
     * Format: [{"branch_id": 1, "amount": 800000}, {"branch_id": 2, "amount": 200000}]
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->json('sumber_dana_data')->nullable()->after('sumber_dana_branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('sumber_dana_data');
        });
    }
};
