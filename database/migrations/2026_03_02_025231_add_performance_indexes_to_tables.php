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
        $transactionIndexes = [
            'invoice_number', 'status', 'type', 'category', 'submitted_by',
            'customer', 'vendor', 'date', 'created_at'
        ];

        foreach ($transactionIndexes as $col) {
            try {
                Schema::table('transactions', function (Blueprint $table) use ($col) {
                    $table->index($col);
                });
            } catch (\Exception $e) {
                // Ignore if index already exists
            }
        }

        // Composite index untuk kombinasi tipe dan status
        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['type', 'status']);
            });
        } catch (\Exception $e) {
            // Ignore
        }

        $notificationIndexes = ['user_id', 'is_read'];
        foreach ($notificationIndexes as $col) {
            try {
                Schema::table('notifications', function (Blueprint $table) use ($col) {
                    $table->index($col);
                });
            } catch (\Exception $e) {
                // Ignore if index already exists
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['invoice_number']);
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
            $table->dropIndex(['category']);
            $table->dropIndex(['submitted_by']);
            $table->dropIndex(['customer']);
            $table->dropIndex(['vendor']);
            $table->dropIndex(['date']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['is_read']);
        });
    }
};
