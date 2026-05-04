<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL specific: modify enum column to include 'gudang'
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('rembush', 'pengajuan', 'gudang') NOT NULL DEFAULT 'rembush'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: reversing might fail if 'gudang' data exists
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('rembush', 'pengajuan') NOT NULL DEFAULT 'rembush'");
    }
};
