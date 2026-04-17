<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_indexes', function (Blueprint $table) {
            $table->id();
            $table->string('item_name', 255)->index();
            $table->string('category', 255)->nullable();  // Kategori bebas (string)
            $table->string('unit', 50)->default('pcs');   // pcs, kg, meter, dll
            $table->decimal('min_price', 15, 2)->default(0);
            $table->decimal('max_price', 15, 2)->default(0);
            $table->decimal('avg_price', 15, 2)->default(0);
            $table->boolean('is_manual')->default(false); // true = set manual oleh Atasan/Owner
            $table->foreignId('manual_set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manual_set_at')->nullable();
            $table->integer('total_transactions')->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('last_calculated_at');
            // Unique komposit: item_name + category
            $table->unique(['item_name', 'category'], 'uq_price_index_item_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_indexes');
    }
};