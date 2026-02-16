<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('draft','pending','approved','completed','rejected') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("UPDATE transactions SET status = 'pending' WHERE status = 'draft'");
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending','approved','completed','rejected') NOT NULL DEFAULT 'pending'");
    }
};
