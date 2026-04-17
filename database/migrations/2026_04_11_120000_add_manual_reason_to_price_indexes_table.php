<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom manual_reason untuk audit trail Price Index.
     * Data historis yang ada TIDAK terpengaruh (kolom nullable).
     */
    public function up(): void
    {
        Schema::table('price_indexes', function (Blueprint $table) {
            // Alasan override manual oleh Owner/Atasan (audit trail)
            $table->text('manual_reason')->nullable()->after('manual_set_at');

            // Flag untuk item baru yang belum pernah direview owner (cold start)
            $table->boolean('needs_initial_review')->default(false)->after('manual_reason');
        });
    }

    public function down(): void
    {
        Schema::table('price_indexes', function (Blueprint $table) {
            $table->dropColumn(['manual_reason', 'needs_initial_review']);
        });
    }
};
