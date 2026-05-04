<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_records', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();

            // Karyawan yang menerima gaji
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Periode gaji (mis: "Maret 2026")
            $table->string('periode');

            // ─── Komponen Gaji ────────────────────────────────
            $table->bigInteger('gaji_pokok')->default(0);
            $table->bigInteger('bonus_1')->default(0);
            $table->bigInteger('bonus_2')->default(0);
            $table->bigInteger('tunjangan')->default(0);
            $table->bigInteger('lembur')->default(0);
            $table->bigInteger('bensin')->default(0);
            $table->bigInteger('lebih_hari')->default(0);

            // ─── Potongan ─────────────────────────────────────
            // Kategori 1: Absen, Cuti, Mangkir, Telat
            $table->bigInteger('potongan_absen')->default(0);
            // Kategori 2: Bon atau angsuran karyawan
            $table->bigInteger('potongan_bon')->default(0);

            // ─── Total (di-hitung ulang di backend saat save) ─
            $table->bigInteger('total_gaji')->default(0);

            // Catatan dari atasan
            $table->text('catatan_atasan')->nullable();

            // ─── Workflow ─────────────────────────────────────
            // draft → approved → paid
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');

            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'periode']);
            $table->index('status');
            $table->index('submitted_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_records');
    }
};
