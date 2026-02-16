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
            $table->text('items')->nullable();
            $table->date('date')->nullable();
            $table->string('file_path')->nullable();
            $table->string('ai_status')->default('processing');
            $table->integer('confidence')->nullable();

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
