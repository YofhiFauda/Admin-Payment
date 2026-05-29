<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_export_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Filter parameters (JSON untuk fleksibilitas)
            $table->json('filters')->nullable();
            
            // Status tracking
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued')->index();
            
            // Progress tracking
            $table->unsignedInteger('total_transactions')->default(0);
            $table->unsignedInteger('processed_transactions')->default(0);
            
            // File info
            $table->string('filename')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            
            // Error tracking
            $table->text('error_message')->nullable();
            
            // Performance metrics
            $table->unsignedInteger('duration_ms')->nullable();
            
            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Composite index untuk query user exports
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_export_jobs');
    }
};
