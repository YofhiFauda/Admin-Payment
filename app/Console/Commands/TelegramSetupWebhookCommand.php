<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetupWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:setup-webhook {--info : Show current webhook info}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Telegram bot webhook';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $botToken = config('services.telegram.bot_token');

        if (!$botToken) {
            $this->error('❌ TELEGRAM_BOT_TOKEN tidak ditemukan di .env');
            $this->info('   Tambahkan: TELEGRAM_BOT_TOKEN=your_token_here');
            return 1;
        }

        // ─── Show webhook info only ─────────────────────────────
        if ($this->option('info')) {
            return $this->showWebhookInfo($botToken);
        }

        // ─── Setup webhook ──────────────────────────────────────
        $appUrl = config('app.url');

        if (!$appUrl || $appUrl === 'http://localhost') {
            $this->error('❌ APP_URL tidak valid atau masih localhost');
            $this->info('   Update APP_URL di .env dengan HTTPS URL (dari Cloudflare Tunnel atau ngrok)');
            return 1;
        }

        if (!str_starts_with($appUrl, 'https://')) {
            $this->error('❌ APP_URL harus menggunakan HTTPS');
            $this->info('   Current: ' . $appUrl);
            $this->info('   Telegram webhook wajib HTTPS!');
            return 1;
        }

        $webhookUrl = rtrim($appUrl, '/') . '/api/telegram/webhook';

        $this->info('🔄 Mendaftarkan webhook ke Telegram...');
        $this->info('   URL: ' . $webhookUrl);

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'callback_query'],
                'drop_pending_updates' => true, // Clear pending updates
            ]);

            $result = $response->json();

            if ($result['ok'] ?? false) {
                $this->info('');
                $this->info('✅ Webhook berhasil didaftarkan!');
                $this->info('   URL: ' . $webhookUrl);
                $this->info('   Allowed updates: message, callback_query');
                $this->info('');
                $this->info('📌 NEXT STEPS:');
                $this->info('   1. Test bot dengan mengetik /start di Telegram');
                $this->info('   2. Monitor log: tail -f storage/logs/laravel.log');
                $this->info('');
                
                // Show bot info
                $this->showBotInfo($botToken);
                
                return 0;
            } else {
                $this->error('❌ Gagal mendaftarkan webhook');
                $this->error('   Error: ' . ($result['description'] ?? 'Unknown error'));
                
                if (isset($result['description']) && str_contains($result['description'], 'HTTPS')) {
                    $this->info('');
                    $this->info('💡 TIP: Pastikan APP_URL menggunakan HTTPS');
                    $this->info('   Gunakan Cloudflare Tunnel: cloudflared tunnel --url http://localhost:8000');
                }
                
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show current webhook info
     */
    protected function showWebhookInfo(string $botToken): int
    {
        $this->info('🔍 Mengecek webhook info...');
        $this->info('');

        try {
            $response = Http::get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
            $result = $response->json();

            if ($result['ok'] ?? false) {
                $info = $result['result'];

                $this->table(
                    ['Property', 'Value'],
                    [
                        ['URL', $info['url'] ?: '(not set)'],
                        ['Has Custom Certificate', $info['has_custom_certificate'] ? 'Yes' : 'No'],
                        ['Pending Update Count', $info['pending_update_count'] ?? 0],
                        ['Last Error Date', $info['last_error_date'] ?? 'None'],
                        ['Last Error Message', $info['last_error_message'] ?? 'None'],
                        ['Max Connections', $info['max_connections'] ?? 'Default'],
                        ['Allowed Updates', implode(', ', $info['allowed_updates'] ?? [])],
                    ]
                );

                if (empty($info['url'])) {
                    $this->warn('');
                    $this->warn('⚠️  Webhook belum didaftarkan!');
                    $this->info('   Jalankan: php artisan telegram:setup-webhook');
                }

                if (!empty($info['last_error_message'])) {
                    $this->warn('');
                    $this->warn('⚠️  Ada error terakhir:');
                    $this->error('   ' . $info['last_error_message']);
                }

                return 0;
            } else {
                $this->error('❌ Gagal mendapatkan webhook info');
                $this->error('   Error: ' . ($result['description'] ?? 'Unknown error'));
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show bot info
     */
    protected function showBotInfo(string $botToken): void
    {
        try {
            $response = Http::get("https://api.telegram.org/bot{$botToken}/getMe");
            $result = $response->json();

            if ($result['ok'] ?? false) {
                $bot = $result['result'];
                
                $this->info('🤖 BOT INFO:');
                $this->info('   Name: ' . $bot['first_name']);
                $this->info('   Username: @' . $bot['username']);
                $this->info('   ID: ' . $bot['id']);
                $this->info('');
                $this->info('💬 Test bot Anda:');
                $this->info('   https://t.me/' . $bot['username']);
                $this->info('');
            }
        } catch (\Exception $e) {
            // Silently fail, this is just extra info
        }
    }
}