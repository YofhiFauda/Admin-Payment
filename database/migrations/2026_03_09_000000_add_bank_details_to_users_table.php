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
        Schema::table('users', function (Blueprint $table) {
            $table->string('rekening_bank')->nullable()->after('role');
            $table->string('rekening_nama')->nullable()->after('rekening_bank');
            $table->string('rekening_nomor')->nullable()->after('rekening_nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rekening_bank', 'rekening_nama', 'rekening_nomor']);
        });
    }
};
