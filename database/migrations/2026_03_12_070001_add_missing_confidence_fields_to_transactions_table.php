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
            if (!Schema::hasColumn('transactions', 'overall_confidence')) {
                $table->integer('overall_confidence')->nullable()->after('confidence');
            }
            if (!Schema::hasColumn('transactions', 'confidence_label')) {
                $table->string('confidence_label', 10)->nullable()->after('overall_confidence');
            }
            if (!Schema::hasColumn('transactions', 'field_confidence')) {
                $table->json('field_confidence')->nullable()->after('confidence_label');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['overall_confidence', 'confidence_label', 'field_confidence']);
        });
    }
};
