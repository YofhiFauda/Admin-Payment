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
            $blueprint->unsignedBigInteger('paid_by')->nullable()->after('reviewed_at');
            $blueprint->dateTime('paid_at')->nullable()->after('paid_by');
            
            $blueprint->foreign('paid_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['paid_by']);
            $blueprint->dropColumn(['paid_by', 'paid_at']);
        });
    }
};
