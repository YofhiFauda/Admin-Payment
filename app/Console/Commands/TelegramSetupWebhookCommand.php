<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;

/**
 * Artisan Command: telegram:setup-webhook
 *
 * Mendaftarkan URL webhook ke Telegram Server secara otomatis.
 * Jalankan SEKALI setelah deploy atau setelah mengubah APP_URL.
 *
 * Usage:
 *   php artisan telegram:setup-webhook
 *   php artisan telegram:setup-webhook --info
 *   php artisan telegram:setup-webhook --delete
 */
class TelegramSetupWebhookCommand extends Command
{
    protected $signature   = 'telegram:setup-webhook
                                {--info   : Tampilkan info webhook saat ini}
                                {--delete : Hapus webhook yang sudah terdaftar}';

    protected $description = 'Setup / manage Telegram Bot webhook URL';

    public function handle(TelegramBotService $telegram): int
    {
        if ($this->option('info')) {
            $info = $telegram->getWebhookInfo();
            $this->info('📡 Telegram Webhook Info:');
            $this->line(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        if ($this->option('delete')) {
            $result = $telegram->deleteWebhook();
            if ($result['ok'] ?? false) {
                $this->info('✅ Webhook berhasil dihapus.');
            } else {
                $this->error('❌ Gagal menghapus webhook: ' . ($result['description'] ?? 'Unknown error'));
            }
            return self::SUCCESS;
        }

        // ── Default: Set webhook ──
        $appUrl     = rtrim(config('app.url'), '/');
        $webhookUrl = $appUrl . '/api/telegram/webhook';

        $this->info("📡 Mendaftarkan webhook ke Telegram...");
        $this->line("   URL: <comment>{$webhookUrl}</comment>");

        $result = $telegram->setWebhook($webhookUrl);

        if ($result['ok'] ?? false) {
            $this->info('');
            $this->info('✅ Webhook berhasil didaftarkan!');
            $this->info("   URL: {$webhookUrl}");
            $this->info('');
            $this->info('📌 Langkah berikutnya:');
            $this->line('   1. Bagikan link bot ke Owner / Admin Anda');
            $this->line('   2. Mereka cukup buka bot dan ketik /daftar <email>');
            $this->line('   3. chat_id akan tersimpan otomatis ke database');
        } else {
            $this->error('');
            $this->error('❌ Gagal mendaftarkan webhook!');
            $this->error('   ' . ($result['description'] ?? 'Unknown error'));
            $this->info('');
            $this->warn('💡 Pastikan:');
            $this->line('   1. TELEGRAM_BOT_TOKEN sudah diisi di .env');
            $this->line('   2. APP_URL menggunakan HTTPS (Telegram wajib HTTPS)');
            $this->line('   3. URL publik bisa diakses dari internet');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
