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
        Schema::table('transactions', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE transactions MODIFY COLUMN status VARCHAR(255) DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending', 'approved', 'completed', 'rejected') DEFAULT 'pending'");
        });
    }
};
