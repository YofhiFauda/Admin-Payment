<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_expenditures', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->enum('jenis', ['bayar_hutang', 'piutang_usaha', 'prive']);

            // Bukti transfer file
            $table->string('bukti_transfer')->nullable();

            // Tujuan transfer:
            // Bayar Hutang & Piutang Usaha: pilih cabang
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // Prive: bebas (rekening atau cash)
            $table->string('rekening_tujuan')->nullable();

            // Prive: dari cabang mana
            $table->foreignId('dari_cabang_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->date('tanggal');
            $table->bigInteger('nominal');
            $table->text('keterangan')->nullable();

            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            // Indexes
            $table->index(['jenis', 'status']);
            $table->index('submitted_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_expenditures');
    }
};
