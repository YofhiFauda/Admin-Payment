<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['rembush', 'pengajuan'])->default('rembush')->index();
            $table->string('invoice_number')->unique();
            $table->string('category')->nullable()->index();
            $table->string('customer')->nullable()->index();
            $table->text('description')->nullable();
            
            // Monetary & PR Fields
            $table->bigInteger('amount')->nullable();
            $table->string('vendor')->nullable()->index();
            $table->text('link')->nullable();
            $table->json('specs')->nullable();
            $table->integer('quantity')->nullable();
            $table->bigInteger('estimated_price')->nullable();
            
            // OCR Verification Fields
            $table->decimal('expected_total', 15, 2)->nullable();
            $table->decimal('actual_total', 15, 2)->nullable();
            $table->decimal('selisih', 15, 2)->nullable();
            
            // Invoice Breakdown (from actual invoice/nota)
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('discount_amount', 15, 2)->nullable();
            $table->decimal('shipping_amount', 15, 2)->nullable(); // ongkir
            $table->decimal('service_fee', 15, 2)->nullable(); // biaya_layanan_1
            $table->decimal('biaya_layanan_2', 15, 2)->nullable();
            $table->decimal('voucher_diskon', 15, 2)->nullable();
            
            // Status & Logic
            $table->string('status', 50)->default('pending')->index();
            $table->string('payment_method')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // ✅ Versioning / Dual-Version System
            $table->boolean('is_edited_by_management')->default(false);
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('edited_at')->nullable();
            $table->integer('revision_count')->default(0);
            $table->json('items_snapshot')->nullable();
            
            // Duplicate Detection
            $table->boolean('is_duplicate')->default(false);
            $table->foreignId('duplicate_of_id')->nullable()->constrained('transactions')->nullOnDelete();

            // Data & Tracking
            $table->json('items')->nullable();
            $table->date('date')->nullable()->index();
            $table->string('file_path')->nullable();
            $table->string('invoice_file_path')->nullable();
            $table->string('trace_id')->nullable()->index();
            $table->string('upload_id')->nullable()->unique();
            $table->text('payment_link')->nullable();
            
            // OCR & AI Fields
            $table->string('ai_status', 50)->default('queued')->index();
            $table->integer('confidence')->nullable()->index();
            $table->integer('overall_confidence')->nullable();
            $table->string('confidence_label', 10)->nullable()->index();
            $table->json('field_confidence')->nullable();
            
            $table->string('ocr_request_id')->nullable();
            $table->string('ocr_status', 50)->nullable();
            $table->timestamp('ocr_processed_at')->nullable();
            $table->string('ocr_result')->nullable(); // MATCH or MISMATCH
            $table->text('flag_reason')->nullable();
            $table->decimal('ocr_confidence', 5, 2)->nullable();
            
            // Payment Proofs
            $table->string('pembayaran_id')->nullable();
            $table->string('foto_penyerahan')->nullable();
            $table->string('bukti_transfer')->nullable();
            
            // Vendor Info (AI Extracted)
            $table->string('vendor_name')->nullable()->index();
            $table->text('vendor_address')->nullable();
            $table->string('vendor_phone')->nullable();

            // Relationships
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            
            $table->foreignId('konfirmasi_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('konfirmasi_at')->nullable();
            
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            
            // Multi-Branch Logic
            $table->foreignId('sumber_dana_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->json('sumber_dana_data')->nullable(); // JSON list for split allocation
            $table->foreignId('penerima_dana_id')->nullable()->constrained('branches')->nullOnDelete();
            
            $table->timestamps();

            // Composite indices
            $table->index(['type', 'status']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
