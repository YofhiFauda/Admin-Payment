<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ═══════════════════════════════════════════════════════════════
 *  Tabel: payment_discrepancy_audits
 *
 *  Menyimpan setiap kejadian selisih (mismatch) antara ekspektasi
 *  bayar dan aktual bayar yang diterima oleh OCR AI.
 *  Digunakan untuk laporan kebocoran bulanan.
 * ═══════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_discrepancy_audits', function (Blueprint $table) {
            $table->id();

            // Relasi ke transaksi yang mengalami selisih
            $table->unsignedBigInteger('transaction_id');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');

            $table->string('invoice_number', 50)->index();

            // Detail selisih
            $table->decimal('expected_total', 15, 2)->default(0);
            $table->decimal('actual_total',   15, 2)->default(0);
            $table->decimal('selisih',        15, 2)->default(0); // bisa negatif = kurang bayar
            $table->string('ocr_result', 20)->nullable();         // MATCH, MISMATCH
            $table->decimal('ocr_confidence', 5, 2)->nullable();

            // Alasan flag dari AI
            $table->text('flag_reason')->nullable();

            // Apakah sudah di-resolusi (Force Approve atau ditolak)
            $table->enum('resolution', ['pending', 'force_approved', 'rejected'])->default('pending');
            $table->text('resolution_reason')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            // Informasi pengirim & metode bayar
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->string('payment_method', 50)->nullable();

            $table->timestamps();

            // Index untuk laporan bulanan
            $table->index(['created_at']);
            $table->index(['resolution']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_discrepancy_audits');
    }
};
