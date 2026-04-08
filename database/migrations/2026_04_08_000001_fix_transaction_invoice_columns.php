<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Rename columns to match the code usage (ongkir & biaya_layanan_1)
            $table->renameColumn('shipping_amount', 'ongkir');
            $table->renameColumn('service_fee', 'biaya_layanan_1');
            
            // Add missing column diskon_pengiriman
            $table->decimal('diskon_pengiriman', 15, 2)->nullable()->after('voucher_diskon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('ongkir', 'shipping_amount');
            $table->renameColumn('biaya_layanan_1', 'service_fee');
            $table->dropColumn('diskon_pengiriman');
        });
    }
};
