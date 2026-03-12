<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan kolom telegram_chat_id ke tabel users
 * agar sistem dapat mengirim notifikasi Telegram secara langsung
 * ke Owner / Admin / Atasan yang mendaftarkan Chat ID-nya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('remember_token')
                ->comment('Chat ID Telegram untuk menerima notifikasi real-time');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_chat_id');
        });
    }
};
