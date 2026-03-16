<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;

/**
 * Artisan Command: telegram:broadcast
 *
 * Kirim broadcast message ke SEMUA karyawan yang terdaftar di Telegram
 * atau ke role tertentu (owner, admin, atasan, teknisi)
 *
 * Usage:
 *   php artisan telegram:broadcast "Pengumuman: Sistem maintenance malam ini"
 *   php artisan telegram:broadcast "Bonus Lebaran cair!" --role=teknisi
 *   php artisan telegram:broadcast "Meeting Owner jam 2 siang" --role=owner
 *   php artisan telegram:broadcast "Test notifikasi" --role=owner,admin
 */
class TelegramBroadcastCommand extends Command
{
    protected $signature = 'telegram:broadcast
                            {message : Pesan yang akan dikirim}
                            {--role=* : Filter berdasarkan role (owner,admin,atasan,teknisi)}
                            {--test : Mode test (kirim ke 1 user saja)}';

    protected $description = 'Broadcast message ke semua karyawan atau role tertentu';

    public function handle(TelegramBotService $telegram): int
    {
        $message = $this->argument('message');
        $roles   = $this->option('role');
        $isTest  = $this->option('test');

        // Format pesan dengan header
        $formattedMessage = "📢 <b>PENGUMUMAN WHUSNET</b>\n\n{$message}\n\n" .
                           "⏰ " . now()->format('d/m/Y H:i');

        // Mode test
        if ($isTest) {
            $this->info('🧪 Mode TEST - Kirim ke 1 user saja...');
            
            $testUser = \App\Models\User::whereNotNull('telegram_chat_id')->first();
            
            if (!$testUser) {
                $this->error('❌ Tidak ada user dengan telegram_chat_id untuk testing');
                return self::FAILURE;
            }

            $sent = $telegram->sendMessage($testUser->telegram_chat_id, $formattedMessage);
            
            if ($sent) {
                $this->info("✅ Test message sent to {$testUser->name} ({$testUser->email})");
                return self::SUCCESS;
            } else {
                $this->error('❌ Failed to send test message');
                return self::FAILURE;
            }
        }

        // Broadcast normal
        $this->info('📤 Mengirim broadcast message...');

        if (!empty($roles)) {
            // Broadcast ke role tertentu
            $rolesArray = is_array($roles) ? $roles : explode(',', $roles);
            $this->info("   Target: Role " . implode(', ', $rolesArray));
            
            $stats = $telegram->broadcastByRole($rolesArray, $formattedMessage);
        } else {
            // Broadcast ke SEMUA karyawan
            $this->info("   Target: SEMUA karyawan");
            
            $stats = $telegram->broadcastToAllStaff($formattedMessage);
        }

        // Tampilkan hasil
        $this->newLine();
        $this->info('📊 Broadcast Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Users', $stats['total']],
                ['Success', $stats['success']],
                ['Failed', $stats['failed']],
            ]
        );

        if ($stats['success'] > 0) {
            $this->info('');
            $this->info("✅ Message sent to {$stats['success']} users");
            return self::SUCCESS;
        } else {
            $this->error('');
            $this->error('❌ No messages sent');
            return self::FAILURE;
        }
    }
}