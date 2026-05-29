<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Layer 3 optimization: composite indexes untuk export query.
     *
     * Export query pattern:
     *   WHERE submitted_by = ? AND type = ? AND status = ?
     *     AND YEAR(created_at) = ? AND MONTH(created_at) = ?
     *   ORDER BY id ASC (untuk lazyById)
     *
     * Index yang sudah ada (dari 2026_04_14_045400_optimize_transactions_search.php):
     *   - idx_status_type_date (status, type, created_at)
     *   - idx_submitter_status (submitted_by, status)
     *
     * Index baru:
     *   - idx_export_teknisi (submitted_by, type, created_at, id)
     *     → untuk teknisi export (WHERE submitted_by = X)
     *   - idx_export_global (type, status, created_at, id)
     *     → untuk admin/owner export (tanpa submitted_by filter)
     *
     * Covering index: include `id` di akhir untuk keyset pagination (lazyById).
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Teknisi export: filter by submitted_by + type + date range
            $table->index(['submitted_by', 'type', 'created_at', 'id'], 'idx_export_teknisi');

            // Admin/Owner export: filter by type + status + date range
            $table->index(['type', 'status', 'created_at', 'id'], 'idx_export_global');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_export_teknisi');
            $table->dropIndex('idx_export_global');
        });
    }
};
