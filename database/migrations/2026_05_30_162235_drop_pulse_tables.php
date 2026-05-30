<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus tabel-tabel Laravel Pulse dari database.
     *
     * Pulse dicopot dari project karena membebani I/O database MySQL
     * yang berjalan pada server yang sama dengan app container.
     * Total data yang dibersihkan: ~144 MB (pulse_entries: ~140 MB).
     */
    public function up(): void
    {
        Schema::dropIfExists('pulse_entries');
        Schema::dropIfExists('pulse_aggregates');
        Schema::dropIfExists('pulse_values');
    }

    /**
     * Reverse tidak tersedia — data Pulse tidak perlu dipulihkan.
     * Jika ingin mengaktifkan kembali Pulse, install ulang package
     * dan jalankan: php artisan vendor:publish --tag=pulse-migrations
     */
    public function down(): void
    {
        // Intentionally empty — data monitoring tidak perlu di-rollback
    }
};
