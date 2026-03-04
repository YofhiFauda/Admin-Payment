<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tambah kolom tracking OCR ke tabel transactions
 * Diperlukan untuk anti-429 retry tracking
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Tracking berapa kali OCR di-attempt (untuk logging)
            $table->unsignedTinyInteger('ocr_attempts')->default(0)->after('confidence');

            // Kapan job OCR terakhir dikirim ke n8n
            $table->timestamp('ocr_sent_at')->nullable()->after('ocr_attempts');

            // Error terakhir dari OCR (untuk debugging admin)
            $table->string('last_ocr_error', 500)->nullable()->after('ocr_sent_at');

            // Index untuk query performa
            $table->index('ai_status');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['ocr_attempts', 'ocr_sent_at', 'last_ocr_error']);
            $table->dropIndex(['ai_status']);
        });
    }
};