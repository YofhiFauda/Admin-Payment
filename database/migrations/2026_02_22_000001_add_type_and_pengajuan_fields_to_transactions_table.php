<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Type: rembush or pengajuan (default rembush for backward compatibility)
            $table->string('type', 20)->default('rembush')->after('id');

            // Rembush-specific fields
            $table->text('description')->nullable()->after('category');
            $table->string('payment_method')->nullable()->after('description');

            // Pengajuan-specific fields
            $table->string('vendor')->nullable()->after('payment_method');
            $table->json('specs')->nullable()->after('vendor');
            $table->integer('quantity')->nullable()->after('specs');
            $table->bigInteger('estimated_price')->nullable()->after('quantity');
            $table->string('purchase_reason')->nullable()->after('estimated_price');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'description',
                'payment_method',
                'vendor',
                'specs',
                'quantity',
                'estimated_price',
                'purchase_reason',
            ]);
        });
    }
};
