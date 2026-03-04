<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add trace_id to transactions
 *
 * trace_id is a short, human-readable reference code for each transaction
 * used for support/logging purposes (e.g. TRX-8DK29XQZ).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Human-readable trace ref (e.g. TRX-8DK29XQZ) — for support/debugging
            $table->string('trace_id', 20)->nullable()->unique()->after('upload_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('trace_id');
        });
    }
};
