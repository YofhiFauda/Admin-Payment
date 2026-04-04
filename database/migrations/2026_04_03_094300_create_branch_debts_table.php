<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ═══════════════════════════════════════════════════════════════
     *  Tabel: branch_debts
     *  
     *  Mencatat hutang antar cabang yang dihasilkan dari pembayaran
     *  Pengajuan. Ketika cabang sumber dana membayar lebih dari 
     *  alokasinya, cabang lain yang tidak membayar memiliki hutang
     *  proporsional ke cabang kreditor.
     * ═══════════════════════════════════════════════════════════════
     */
    public function up(): void
    {
        Schema::create('branch_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('debtor_branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('creditor_branch_id')->constrained('branches')->onDelete('cascade');
            $table->bigInteger('amount')->default(0); // Nominal hutang
            $table->string('status')->default('pending'); // pending | paid
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index for common queries
            $table->index(['debtor_branch_id', 'status']);
            $table->index(['creditor_branch_id', 'status']);
            $table->index(['transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_debts');
    }
};
