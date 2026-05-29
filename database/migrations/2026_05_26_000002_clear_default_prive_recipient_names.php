<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('other_expenditures', 'recipient_name')) {
            return;
        }

        DB::table('other_expenditures')
            ->where('jenis', 'prive')
            ->whereRaw('LOWER(TRIM(recipient_name)) = ?', ['penerima default'])
            ->update(['recipient_name' => null]);
    }

    public function down(): void
    {
        // Data cleanup only; intentionally irreversible.
    }
};
