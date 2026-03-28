<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mengubah foreign key 'submitted_by' dari CASCADE menjadi SET NULL
     * agar data transaksi tidak ikut terhapus saat akun pengguna dihapus.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // 1. Hapus foreign key constraint lama
            $table->dropForeign(['submitted_by']);

            // 2. Ubah kolom menjadi nullable (agar bisa SET NULL)
            $table->unsignedBigInteger('submitted_by')->nullable()->change();

            // 3. Buat ulang foreign key dengan ON DELETE SET NULL
            $table->foreign('submitted_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Kembalikan ke CASCADE (rollback)
            $table->dropForeign(['submitted_by']);

            $table->unsignedBigInteger('submitted_by')->nullable(false)->change();

            $table->foreign('submitted_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }
};
