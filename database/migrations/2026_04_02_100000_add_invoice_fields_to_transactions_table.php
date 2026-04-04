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
        Schema::table('transactions', function (Blueprint $blueprint) {
            $blueprint->string('invoice_file_path')->nullable()->after('bukti_transfer');
            $blueprint->bigInteger('diskon_pengiriman')->default(0)->after('invoice_file_path');
            $blueprint->bigInteger('ongkir')->default(0)->after('diskon_pengiriman');
            $blueprint->bigInteger('biaya_layanan_1')->default(0)->after('ongkir');
            $blueprint->bigInteger('biaya_layanan_2')->default(0)->after('biaya_layanan_1');
            $blueprint->bigInteger('voucher_diskon')->default(0)->after('biaya_layanan_2');
            $blueprint->unsignedBigInteger('sumber_dana_branch_id')->nullable()->after('voucher_diskon');

            $blueprint->foreign('sumber_dana_branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['sumber_dana_branch_id']);
            $blueprint->dropColumn([
                'invoice_file_path',
                'diskon_pengiriman',
                'ongkir',
                'biaya_layanan_1',
                'biaya_layanan_2',
                'voucher_diskon',
                'sumber_dana_branch_id',
            ]);
        });
    }
};
