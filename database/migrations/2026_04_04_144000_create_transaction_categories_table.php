<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->index();
            $table->enum('type', ['rembush', 'pengajuan']);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'type']);
            $table->unique(['code', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_categories');
    }
};
