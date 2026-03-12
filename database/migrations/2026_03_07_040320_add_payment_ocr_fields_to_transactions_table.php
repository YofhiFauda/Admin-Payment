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
            $table->decimal('expected_total', 15, 2)->nullable()->after('amount');
            $table->decimal('actual_total', 15, 2)->nullable()->after('expected_total');
            $table->decimal('selisih', 15, 2)->nullable()->after('actual_total');
            $table->string('ocr_result')->nullable()->after('selisih'); // MATCH or MISMATCH
            $table->text('flag_reason')->nullable()->after('ocr_result');
            $table->decimal('ocr_confidence', 5, 2)->nullable()->after('flag_reason');
            
            $table->unsignedBigInteger('konfirmasi_by')->nullable()->after('ocr_confidence');
            $table->foreign('konfirmasi_by')->references('id')->on('users')->nullOnDelete();
            
            $table->timestamp('konfirmasi_at')->nullable()->after('konfirmasi_by');
            
            $table->string('pembayaran_id')->nullable()->after('konfirmasi_at');
            $table->string('foto_penyerahan')->nullable()->after('pembayaran_id');
            $table->string('bukti_transfer')->nullable()->after('foto_penyerahan');

            // ✅ NEW: Confidence fields untuk UI badge
            $table->string('ai_status', 50)->default('queued')->after('status');
            $table->integer('confidence')->nullable()->after('ai_status');
            $table->integer('overall_confidence')->nullable()->after('confidence');
            $table->string('confidence_label', 10)->nullable()->after('overall_confidence');
            $table->json('field_confidence')->nullable()->after('confidence_label');

            // Index untuk query performance
            $table->index('ai_status');
            $table->index('confidence');
            $table->index('confidence_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['konfirmasi_by']);
            $table->dropColumn([
                'expected_total',
                'actual_total',
                'selisih',
                'ocr_result',
                'flag_reason',
                'ocr_confidence',
                'konfirmasi_by',
                'konfirmasi_at',
                'pembayaran_id',
                'foto_penyerahan',
                'bukti_transfer',
                'ai_status',
                'confidence',
                'overall_confidence',
                'confidence_label',
                'field_confidence'
            ]);
        });
    }
};
