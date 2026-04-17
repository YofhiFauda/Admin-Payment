<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Composite index untuk search optimization
            $table->index(['status', 'type', 'created_at'], 'idx_status_type_date');
            $table->index(['submitted_by', 'status'], 'idx_submitter_status');
            $table->index('invoice_number', 'idx_invoice_number');
            $table->index('customer', 'idx_customer');
        });

        // FULLTEXT index untuk advanced search (MySQL 5.7+)
        DB::statement('ALTER TABLE transactions ADD FULLTEXT idx_search (invoice_number, customer, vendor, description)');
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_status_type_date');
            $table->dropIndex('idx_submitter_status');
            $table->dropIndex('idx_invoice_number');
            $table->dropIndex('idx_customer');
        });
        
        DB::statement('ALTER TABLE transactions DROP INDEX idx_search');
    }
};