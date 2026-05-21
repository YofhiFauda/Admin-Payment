<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ✅ STEP 1: Clean up existing duplicates before adding unique constraint
        // Find and remove duplicate entries, keeping only the most recent one
        DB::statement("
            DELETE t1 FROM transaction_branches t1
            INNER JOIN transaction_branches t2 
            WHERE t1.id < t2.id 
            AND t1.transaction_id = t2.transaction_id 
            AND t1.branch_id = t2.branch_id
        ");

        // ✅ STEP 2: Add unique constraint to prevent future duplicates
        Schema::table('transaction_branches', function (Blueprint $table) {
            $table->unique(['transaction_id', 'branch_id'], 'unique_transaction_branch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_branches', function (Blueprint $table) {
            $table->dropUnique('unique_transaction_branch');
        });
    }
};
