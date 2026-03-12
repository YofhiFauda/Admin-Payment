<?php

namespace App\Services\Telegram;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════
 *  TelegramBotService — Notifikasi Real-Time ke Telegram
 *
 *  ✅ notifyFlaggedTransaction() → Alert selisih nominal ke Owner
 *  ✅ notifyAutoReject()         → Alert nota auto-reject ke Admin
 *  ✅ notifyForceApproved()      → Alert force approve ke Owner
 * ═══════════════════════════════════════════════════════════════
 */
class TelegramBotService
{
    private string $botToken;
    private string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiUrl   = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Kirim notifikasi ke satu chat_id Telegram.
     */
    public function sendMessage(string $chatId, string $message, string $parseMode = 'HTML'): bool
    {
        if (!$this->botToken || $this->botToken === env('TELEGRAM_BOT_TOKEN')) {
            Log::channel('ai_autofill')->warning('⚠️ [TELEGRAM] Bot token not configured, skipping notification.');
            return false;
        }

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $message,
                'parse_mode' => $parseMode,
            ]);

            if ($response->successful()) {
                Log::channel('ai_autofill')->info('📨 [TELEGRAM] Message sent', [
                    'chat_id' => $chatId,
                ]);
                return true;
            }

            Log::channel('ai_autofill')->warning('⚠️ [TELEGRAM] Failed to send message', [
                'chat_id'  => $chatId,
                'response' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::channel('ai_autofill')->error('❌ [TELEGRAM] Exception sending message', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * ─────────────────────────────────────────────────────────
     *  Kirim notifikasi ke semua Owner saat transaksi "Flagged"
     *  (Selisih nominal antara bukti transfer dan expected)
     * ─────────────────────────────────────────────────────────
     */
    public function notifyFlaggedTransaction(Transaction $transaction): void
    {
        $owners = User::where('role', 'owner')
            ->whereNotNull('telegram_chat_id')
            ->get();

        if ($owners->isEmpty()) {
            Log::channel('ai_autofill')->warning('⚠️ [TELEGRAM] No owners with telegram_chat_id found.');
            return;
        }

        $invoiceNumber  = $transaction->invoice_number;
        $expectedTotal  = 'Rp ' . number_format($transaction->expected_total ?? 0, 0, ',', '.');
        $actualTotal    = 'Rp ' . number_format($transaction->actual_total   ?? 0, 0, ',', '.');
        $selisih        = 'Rp ' . number_format(abs($transaction->selisih   ?? 0), 0, ',', '.');
        $teknisiName    = $transaction->submitter?->name ?? 'Tidak diketahui';
        $flagReason     = $transaction->flag_reason ?? '-';
        $ocrConfidence  = ($transaction->ocr_confidence ?? 0) . '%';
        $timestamp      = now()->format('d/m/Y H:i');

        $message = <<<HTML
🚨 <b>ALERT: SELISIH NOMINAL TRANSFER</b> 🚨

📋 <b>Invoice:</b> <code>{$invoiceNumber}</code>
👤 <b>Teknisi:</b> {$teknisiName}
⏰ <b>Waktu:</b> {$timestamp}

💰 <b>Detail Selisih:</b>
┣ Expected : {$expectedTotal}
┣ Actual   : {$actualTotal}
┗ Selisih  : <b>{$selisih}</b>

🔍 <b>Alasan Flag:</b> {$flagReason}
🤖 <b>OCR Confidence:</b> {$ocrConfidence}

⚠️ <i>Transaksi ini terkunci. Diperlukan Force Approve oleh Owner/Atasan.</i>
HTML;

        foreach ($owners as $owner) {
            $this->sendMessage($owner->telegram_chat_id, $message);
        }

        Log::channel('ai_autofill')->info('📨 [TELEGRAM] Flagged notification sent to all owners', [
            'transaction_id' => $transaction->id,
            'invoice_number' => $invoiceNumber,
            'owners_count'   => $owners->count(),
        ]);
    }

    /**
     * ─────────────────────────────────────────────────────────
     *  Kirim notifikasi ke semua Admin/Owner saat Auto-Reject
     * ─────────────────────────────────────────────────────────
     */
    public function notifyAutoReject(Transaction $transaction): void
    {
        $admins = User::whereIn('role', ['admin', 'owner', 'atasan'])
            ->whereNotNull('telegram_chat_id')
            ->get();

        if ($admins->isEmpty()) {
            Log::channel('ai_autofill')->warning('⚠️ [TELEGRAM] No admins/owners with telegram_chat_id for auto-reject notification.');
            return;
        }

        $invoiceNumber = $transaction->invoice_number;
        $teknisiName   = $transaction->submitter?->name ?? 'Tidak diketahui';
        $reason        = $transaction->rejection_reason ?? '-';
        $timestamp     = now()->format('d/m/Y H:i');

        $message = <<<HTML
⛔ <b>AUTO-REJECT: Nota Ditolak Otomatis</b>

📋 <b>Invoice:</b> <code>{$invoiceNumber}</code>
👤 <b>Teknisi:</b> {$teknisiName}
⏰ <b>Waktu:</b> {$timestamp}
📝 <b>Alasan:</b> {$reason}

<i>Gunakan tombol "Request Override" jika ingin melanjutkan transaksi ini.</i>
HTML;

        foreach ($admins as $admin) {
            $this->sendMessage($admin->telegram_chat_id, $message);
        }
    }

    /**
     * ─────────────────────────────────────────────────────────
     *  Kirim notifikasi ke Owner saat Force Approve dilakukan
     * ─────────────────────────────────────────────────────────
     */
    public function notifyForceApproved(Transaction $transaction, User $approver, string $reason): void
    {
        $owners = User::where('role', 'owner')
            ->whereNotNull('telegram_chat_id')
            ->get();

        if ($owners->isEmpty()) {
            return;
        }

        $invoiceNumber = $transaction->invoice_number;
        $selisih       = 'Rp ' . number_format(abs($transaction->selisih ?? 0), 0, ',', '.');
        $approverName  = $approver->name;
        $approverRole  = ucfirst($approver->role);
        $timestamp     = now()->format('d/m/Y H:i');

        $message = <<<HTML
✅ <b>FORCE APPROVE DILAKUKAN</b>

📋 <b>Invoice:</b> <code>{$invoiceNumber}</code>
💰 <b>Selisih:</b> {$selisih}
⏰ <b>Waktu:</b> {$timestamp}

👤 <b>Disetujui oleh:</b> {$approverName} ({$approverRole})
📝 <b>Alasan:</b> <i>{$reason}</i>

<b>Status:</b> SELESAI ✅
HTML;

        foreach ($owners as $owner) {
            $this->sendMessage($owner->telegram_chat_id, $message);
        }
    }

    // ─── Setup Helpers ────────────────────────────────────────────────

    /**
     * Daftarkan URL webhook ke Telegram Server.
     * Dipanggil SEKALI saat setup awal via Artisan command.
     *
     * @param string $webhookUrl URL publik endpoint /api/telegram/webhook
     */
    public function setWebhook(string $webhookUrl): array
    {
        if (!$this->botToken || $this->botToken === env('TELEGRAM_BOT_TOKEN')) {
            return ['ok' => false, 'description' => 'Bot token not configured'];
        }

        $response = Http::timeout(15)->post("{$this->apiUrl}/setWebhook", [
            'url'              => $webhookUrl,
            'allowed_updates'  => ['message'],
            'drop_pending_updates' => true,
        ]);

        $result = $response->json();

        Log::channel('ai_autofill')->info('📡 [TELEGRAM] Webhook set', [
            'url'    => $webhookUrl,
            'result' => $result,
        ]);

        return $result ?? ['ok' => false, 'description' => 'No response'];
    }

    /**
     * Hapus webhook yang terdaftar (kembali ke polling mode).
     */
    public function deleteWebhook(): array
    {
        $response = Http::timeout(10)->post("{$this->apiUrl}/deleteWebhook", [
            'drop_pending_updates' => true,
        ]);
        return $response->json() ?? ['ok' => false];
    }

    /**
     * Cek info webhook yang sedang aktif.
     */
    public function getWebhookInfo(): array
    {
        $response = Http::timeout(10)->get("{$this->apiUrl}/getWebhookInfo");
        return $response->json() ?? [];
    }
}
