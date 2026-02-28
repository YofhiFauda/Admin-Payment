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
            // Menambahkan index untuk kolom yang sering digunakan di pencarian & filter
            $table->index('status');
            $table->index('type');
            $table->index('category');
            $table->index('date');
            
            // Composite index jika tipe sering dikombinasikan dengan status
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
            $table->dropIndex(['category']);
            $table->dropIndex(['date']);
            $table->dropIndex(['type', 'status']);
        });
    }
};
