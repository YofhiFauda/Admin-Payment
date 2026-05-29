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
        Schema::table('other_expenditures', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('bukti_transfer');
            $table->foreignId('bank_account_id')->nullable()->after('payment_method')->constrained('branch_bank_accounts')->nullOnDelete();
            $table->foreignId('sender_bank_account_id')->nullable()->after('bank_account_id')->constrained('branch_bank_accounts')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->after('sender_bank_account_id')->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->after('paid_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('other_expenditures', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['sender_bank_account_id']);
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['payment_method', 'bank_account_id', 'sender_bank_account_id', 'paid_by', 'paid_at']);
        });
    }
};
