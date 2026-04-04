<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds versioning columns to support dual-version system for Pengajuan:
     *  - items_snapshot     : Immutable original data from Teknisi
     *  - is_edited_by_management : Flag whether Management has ever edited
     *  - edited_by          : FK to users who last edited
     *  - edited_at          : Timestamp of last management edit
     *  - revision_count     : Number of management revisions
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Only add if columns don't exist yet (idempotent)
            if (!Schema::hasColumn('transactions', 'items_snapshot')) {
                $table->json('items_snapshot')->nullable()->after('items')
                      ->comment('Immutable original items from teknisi (frozen on first management edit)');
            }
            if (!Schema::hasColumn('transactions', 'is_edited_by_management')) {
                $table->boolean('is_edited_by_management')->default(false)->after('items_snapshot')
                      ->comment('True if management has ever edited this pengajuan');
            }
            if (!Schema::hasColumn('transactions', 'edited_by')) {
                $table->unsignedBigInteger('edited_by')->nullable()->after('is_edited_by_management')
                      ->comment('User ID of last management editor');
                $table->foreign('edited_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('transactions', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('edited_by')
                      ->comment('Timestamp of last management edit');
            }
            if (!Schema::hasColumn('transactions', 'revision_count')) {
                $table->unsignedInteger('revision_count')->default(0)->after('edited_at')
                      ->comment('Counter: how many times management has revised this pengajuan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop FK first
            if (Schema::hasColumn('transactions', 'edited_by')) {
                $table->dropForeign(['edited_by']);
            }

            $columns = ['items_snapshot', 'is_edited_by_management', 'edited_by', 'edited_at', 'revision_count'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
