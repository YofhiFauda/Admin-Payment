<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Artisan Command: telegram:stats
 *
 * Menampilkan statistik registrasi Telegram per role
 * Berguna untuk monitoring siapa saja yang sudah/belum daftar
 *
 * Usage:
 *   php artisan telegram:stats
 *   php artisan telegram:stats --details  (tampilkan nama user)
 */
class TelegramStatsCommand extends Command
{
    protected $signature = 'telegram:stats
                            {--details : Tampilkan detail user yang terdaftar}';

    protected $description = 'Tampilkan statistik registrasi Telegram per role';

    public function handle(): int
    {
        $this->info('📊 STATISTIK REGISTRASI TELEGRAM WHUSNET');
        $this->newLine();

        // Ambil stats per role
        $roles = ['owner', 'admin', 'atasan', 'teknisi'];
        $tableData = [];
        $totalRegistered = 0;
        $totalUsers = 0;

        foreach ($roles as $role) {
            $total      = User::where('role', $role)->count();
            $registered = User::where('role', $role)->whereNotNull('telegram_chat_id')->count();
            $percentage = $total > 0 ? round(($registered / $total) * 100, 1) : 0;

            $totalRegistered += $registered;
            $totalUsers += $total;

            $tableData[] = [
                ucfirst($role),
                $registered,
                $total,
                "{$percentage}%"
            ];
        }

        // Tampilkan tabel stats
        $this->table(
            ['Role', 'Terdaftar', 'Total User', 'Coverage'],
            $tableData
        );

        // Summary
        $totalPercentage = $totalUsers > 0 ? round(($totalRegistered / $totalUsers) * 100, 1) : 0;
        
        $this->newLine();
        $this->info("📈 TOTAL: {$totalRegistered}/{$totalUsers} user terdaftar ({$totalPercentage}%)");

        // Detail user yang terdaftar
        if ($this->option('details')) {
            $this->newLine();
            $this->info('👥 DETAIL USER YANG TERDAFTAR:');
            $this->newLine();

            foreach ($roles as $role) {
                $users = User::where('role', $role)
                    ->whereNotNull('telegram_chat_id')
                    ->get();

                if ($users->isNotEmpty()) {
                    $this->info("  <fg=cyan>" . strtoupper($role) . ":</>");
                    foreach ($users as $user) {
                        $this->line("    • {$user->name} ({$user->email})");
                    }
                    $this->newLine();
                }
            }

            // User yang BELUM daftar
            $this->info('⚠️  USER YANG BELUM DAFTAR:');
            $this->newLine();

            foreach ($roles as $role) {
                $users = User::where('role', $role)
                    ->whereNull('telegram_chat_id')
                    ->get();

                if ($users->isNotEmpty()) {
                    $this->warn("  <fg=yellow>" . strtoupper($role) . ":</>");
                    foreach ($users as $user) {
                        $this->line("    • {$user->name} ({$user->email})");
                    }
                    $this->newLine();
                }
            }
        }

        // Rekomendasi
        if ($totalPercentage < 50) {
            $this->newLine();
            $this->warn('💡 REKOMENDASI:');
            $this->line('   Ajak lebih banyak karyawan untuk daftar Telegram bot:');
            $this->line('   1. Buka bot di Telegram');
            $this->line('   2. Kirim: /daftar email@whusnet.com');
        }

        return self::SUCCESS;
    }
}