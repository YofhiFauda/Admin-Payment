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
        Schema::table('branch_debts', function (Blueprint $table) {
            $table->unsignedBigInteger('sender_bank_account_id')->nullable()->after('bank_account_id');

            $table->foreign('sender_bank_account_id')
                  ->references('id')
                  ->on('branch_bank_accounts')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_debts', function (Blueprint $table) {
            $table->dropForeign(['sender_bank_account_id']);
            $table->dropColumn('sender_bank_account_id');
        });
    }
};
