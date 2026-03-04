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
            $table->string('invoice_number')->unique();
            $table->string('customer')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->json('items')->nullable();
            $table->date('date')->nullable();
            $table->string('file_path')->nullable();


            // ✅ AI Status Columns
            $table->string('ai_status')->default('pending'); // processing, completed, error
            $table->integer('confidence')->nullable();        // 0-100
            $table->string('upload_id')->nullable()->unique(); // ID untuk tracking OCR

            // Business Status
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');

            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
