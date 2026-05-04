<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_anomalies', function (Blueprint $table) {
            $table->id();

            // Transaksi yang memicu anomali
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();

            // Data item yang anomali
            $table->string('item_name', 255);
            $table->decimal('input_price', 15, 2);
            $table->decimal('reference_max_price', 15, 2);
            $table->decimal('excess_amount', 15, 2);
            $table->decimal('excess_percentage', 7, 2);

            // Severity: low (<20%), medium (20-50%), critical (>50%)
            $table->enum('severity', ['low', 'medium', 'critical']);

            // Relasi ke price index yang dipakai sebagai referensi (nullable jika tidak ada)
            $table->foreignId('price_index_id')->nullable()->constrained('price_indexes')->nullOnDelete();

            // User yang submit (teknisi/admin)
            $table->foreignId('reported_by_user_id')->constrained('users')->cascadeOnDelete();

            // Notifikasi
            $table->timestamp('notification_sent_at')->nullable();

            // Review oleh Owner/Atasan
            $table->boolean('owner_reviewed')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('owner_notes')->nullable();

            // Status: pending → reviewed/approved/rejected
            $table->enum('status', ['pending', 'reviewed', 'approved', 'rejected'])->default('pending');

            $table->timestamps();

            $table->index('transaction_id');
            $table->index('status');
            $table->index('severity');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_anomalies');
    }
};