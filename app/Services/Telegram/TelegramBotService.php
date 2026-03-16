<?php

namespace App\Services\Telegram;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════
 *  TelegramBotService — Notifikasi Real-Time ke Multiple Users
 *
 *  FITUR UTAMA:
 *  ✅ Kirim ke SEMUA user berdasarkan role (owner, admin, atasan)
 *  ✅ Kirim ke user SPESIFIK (teknisi tertentu)
 *  ✅ Kirim ke GROUP monitoring (optional)
 *  ✅ Inline keyboard (tombol interaktif)
 *  ✅ Error handling & logging lengkap
 * 
 *  NOTIFIKASI UNTUK OWNER/ADMIN/ATASAN:
 *  • notifyFlaggedTransaction() → Alert selisih nominal
 *  • notifyAutoReject() → Alert nota auto-reject
 *  • notifyForceApproved() → Alert force approve
 *
 *  NOTIFIKASI UNTUK TEKNISI:
 *  • notifyPaymentCash() → Alert cash siap + tombol konfirmasi
 *  • notifyPaymentComplete() → Alert transfer berhasil
 *  • notifyForceApprovedToTechnician() → Alert setelah force approve
 *  • notifyPaymentProcessing() → Alert pembayaran sedang diproses
 * 
 *  NOTIFIKASI BROADCAST:
 *  • broadcastToAllStaff() → Kirim ke SEMUA karyawan
 *  • broadcastByRole() → Kirim ke karyawan berdasarkan role
 * ═══════════════════════════════════════════════════════════════
 */
class TelegramBotService
{
    private string $botToken;
    private string $apiUrl;
    private ?string $groupMonitoringId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiUrl   = "https://api.telegram.org/bot{$this->botToken}";
        $this->groupMonitoringId = config('services.telegram.group_monitoring_id');
    }

    // ════════════════════════════════════════════════════════
    //  CORE METHODS: Kirim Pesan
    // ════════════════════════════════════════════════════════

    /**
     * Kirim pesan ke SATU chat_id
     */
    public function sendMessage(string $chatId, string $message, array $replyMarkup = []): bool
    {
        if (!$this->botToken) {
            Log::channel('ai_autofill')->warning('⚠️ [TELEGRAM] Bot token not configured');
            return false;
        }

        try {
            $payload = [
                'chat_id'    => $chatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ];

            if (!empty($replyMarkup)) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $response = Http::timeout(10)->post("{$this->apiUrl}/sendMessage", $payload);

            if ($response->successful()) {
                Log::channel('ai_autofill')->info('📨 [TELEGRAM] Message sent', [
                    'chat_id' => $chatId,
                ]);
                return true;
            }

            Log::channel('ai_autofill')->warning('⚠️ [TELEGRAM] Failed to send', [
                'chat_id'  => $chatId,
                'response' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::channel('ai_autofill')->error('❌ [TELEGRAM] Exception', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Kirim pesan ke MULTIPLE users berdasarkan query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $message
     * @param array $replyMarkup
     * @return array ['success' => int, 'failed' => int, 'total' => int]
     */
    public function sendToMultipleUsers($query, string $message, array $replyMarkup = []): array
    {
        $users = $query->whereNotNull('telegram_chat_id')->get();
        
        $stats = ['success' => 0, 'failed' => 0, 'total' => $users->count()];

        if ($users->isEmpty()) {
            Log::channel('ai_autofill')->warning('⚠️ [TELEGRAM] No users with chat_id found');
            return $stats;
        }

        foreach ($users as $user) {
            $sent = $this->sendMessage($user->telegram_chat_id, $message, $replyMarkup);
            
            if ($sent) {
                $stats['success']++;
            } else {
                $stats['failed']++;
            }
        }

        Log::channel('ai_autofill')->info('📊 [TELEGRAM] Bulk send completed', $stats);

        return $stats;
    }

    /**
     * Kirim pesan ke GROUP monitoring (optional)
     */
    public function sendToMonitoringGroup(string $message): bool
    {
        if (!$this->groupMonitoringId) {
            return false;
        }

        return $this->sendMessage($this->groupMonitoringId, $message);
    }

    // ════════════════════════════════════════════════════════
    //  NOTIFIKASI UNTUK OWNER/ADMIN/ATASAN
    // ════════════════════════════════════════════════════════

    /**
     * ─────────────────────────────────────────────────────────
     *  Kirim notifikasi ke SEMUA OWNER saat transaksi "Flagged"
     *  (Selisih nominal antara bukti transfer dan expected)
     * ─────────────────────────────────────────────────────────
     */
    public function notifyFlaggedTransaction(Transaction $transaction): void
    {
        $invoiceNumber  = $transaction->invoice_number;
        $expectedTotal  = 'Rp ' . number_format($transaction->estimated_price ?? 0, 0, ',', '.');
        $actualTotal    = 'Rp ' . number_format($transaction->amount ?? 0, 0, ',', '.');
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

        // Kirim ke SEMUA OWNER
        $stats = $this->sendToMultipleUsers(
            User::query()->where('role', 'owner'),
            $message
        );

        // Kirim ke GROUP monitoring
        $this->sendToMonitoringGroup("[FLAGGED] {$invoiceNumber} - Selisih {$selisih}");

        Log::channel('ai_autofill')->info('📨 [TELEGRAM] Flagged notification sent', [
            'transaction_id' => $transaction->id,
            'invoice_number' => $invoiceNumber,
            'recipients'     => $stats,
        ]);
    }

    /**
     * ─────────────────────────────────────────────────────────
     *  Kirim notifikasi ke ADMIN/OWNER/ATASAN saat Auto-Reject
     * ─────────────────────────────────────────────────────────
     */
    public function notifyAutoReject(Transaction $transaction): void
    {
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

        // Kirim ke ADMIN, OWNER, ATASAN
        $stats = $this->sendToMultipleUsers(
            User::query()->whereIn('role', ['admin', 'owner', 'atasan']),
            $message
        );

        // Kirim ke GROUP monitoring
        $this->sendToMonitoringGroup("[AUTO-REJECT] {$invoiceNumber} - {$reason}");

        Log::channel('ai_autofill')->info('📨 [TELEGRAM] Auto-reject notification sent', [
            'transaction_id' => $transaction->id,
            'recipients'     => $stats,
        ]);
    }

    /**
     * ─────────────────────────────────────────────────────────
     *  Kirim notifikasi ke OWNER saat Force Approve dilakukan
     * ─────────────────────────────────────────────────────────
     */
    public function notifyForceApproved(Transaction $transaction, User $approver, string $reason): void
    {
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

        // Kirim ke SEMUA OWNER
        $stats = $this->sendToMultipleUsers(
            User::query()->where('role', 'owner'),
            $message
        );

        // Kirim ke GROUP monitoring
        $this->sendToMonitoringGroup("[FORCE APPROVE] {$invoiceNumber} by {$approverName}");

        Log::channel('ai_autofill')->info('📨 [TELEGRAM] Force approve notification sent', [
            'transaction_id' => $transaction->id,
            'recipients'     => $stats,
        ]);
    }

    // ════════════════════════════════════════════════════════
    //  NOTIFIKASI UNTUK TEKNISI
    // ════════════════════════════════════════════════════════

    /**
     * Notifikasi pembayaran CASH siap diambil (dengan tombol konfirmasi)
     */
    public function notifyPaymentCash(Transaction $transaction): void
    {
        $teknisi = $transaction->submitter;
        
        if (!$teknisi || !$teknisi->telegram_chat_id) {
            Log::channel('ai_autofill')->warning('⚠️ [TELEGRAM] Teknisi tidak punya chat_id', [
                'teknisi_id' => $teknisi?->id,
            ]);

            // Fallback: Kirim ke group monitoring saja
            $this->sendToMonitoringGroup(
                "⚠️ [CASH] {$transaction->invoice_number} - Teknisi {$teknisi?->name} belum daftar Telegram"
            );
            return;
        }

        $invoiceNumber = $transaction->invoice_number;
        $kategori      = $transaction->category ?? '-';
        $nominal       = 'Rp ' . number_format($transaction->amount, 0, ',', '.');
        $cabang        = $transaction->branch?->name ?? '-';
        $timestamp     = now()->format('d/m/Y H:i');

        $message = <<<HTML
💰 <b>PEMBAYARAN CASH SIAP DIAMBIL</b>

📋 <b>Invoice:</b> <code>{$invoiceNumber}</code>
🏷️ <b>Kategori:</b> {$kategori}
💵 <b>Nominal:</b> {$nominal}
🏢 <b>Lokasi:</b> {$cabang}
⏰ <b>Waktu:</b> {$timestamp}

📸 <b>Bukti Penyerahan:</b> Sudah diupload oleh Admin

─────────────────────────────────
Apakah Anda sudah menerima uang ini?
Klik tombol di bawah untuk konfirmasi.
HTML;

        // Inline Keyboard dengan tombol "Terima"
        $replyMarkup = [
            'inline_keyboard' => [
                [
                    [
                        'text'          => '✅ Terima',
                        'callback_data' => "confirm_cash:{$transaction->id}",
                    ],
                    [
                        'text'          => '❌ Tolak',
                        'callback_data' => "report_issue:{$transaction->id}",
                    ],
                ],
            ],
        ];

        // Kirim ke TEKNISI dengan tombol
        $this->sendMessage($teknisi->telegram_chat_id, $message, $replyMarkup);

        // Kirim ke GROUP monitoring (tanpa tombol)
        $this->sendToMonitoringGroup(
            "[CASH] {$invoiceNumber} - {$teknisi->name} - {$nominal}"
        );

        Log::channel('ai_autofill')->info('📨 [TELEGRAM] Cash payment notification sent', [
            'transaction_id' => $transaction->id,
            'teknisi_id'     => $teknisi->id,
        ]);
    }

    /**
     * Notifikasi pembayaran TRANSFER berhasil (tanpa tombol)
     */
    public function notifyPaymentComplete(Transaction $transaction): void
    {
        $teknisi = $transaction->submitter;
        
        if (!$teknisi || !$teknisi->telegram_chat_id) {
            Log::channel('ai_autofill')->warning('⚠️ [TELEGRAM] Teknisi tidak punya chat_id');
            return;
        }

        $invoiceNumber = $transaction->invoice_number;
        $nominal       = 'Rp ' . number_format($transaction->amount, 0, ',', '.');
        $rekening      = $transaction->payment_account ?? '-';
        $timestamp     = now()->format('d/m/Y H:i');

        $message = <<<HTML
✅ <b>PEMBAYARAN TRANSFER BERHASIL</b>

📋 <b>Invoice:</b> <code>{$invoiceNumber}</code>
💰 <b>Nominal:</b> {$nominal}
🏦 <b>Rekening:</b> {$rekening}
⏰ <b>Waktu Transfer:</b> {$timestamp}

Dana sudah masuk ke rekening Anda.
<b>Status:</b> SELESAI ✅
HTML;

        // Kirim ke TEKNISI
        $this->sendMessage($teknisi->telegram_chat_id, $message);

        // Kirim ke GROUP monitoring
        $this->sendToMonitoringGroup(
            "[TRANSFER COMPLETE] {$invoiceNumber} - {$teknisi->name} - {$nominal}"
        );

        Log::channel('ai_autofill')->info('📨 [TELEGRAM] Transfer complete notification sent', [
            'transaction_id' => $transaction->id,
            'teknisi_id'     => $teknisi->id,
        ]);
    }

    /**
     * Notifikasi setelah Force Approve (untuk teknisi)
     */
    public function notifyForceApprovedToTechnician(Transaction $transaction): void
    {
        $teknisi = $transaction->submitter;
        
        if (!$teknisi || !$teknisi->telegram_chat_id) {
            return;
        }

        $invoiceNumber = $transaction->invoice_number;
        $nominal       = 'Rp ' . number_format($transaction->amount, 0, ',', '.');

        $message = <<<HTML
✅ <b>PEMBAYARAN DISETUJUI OWNER</b>

📋 <b>Invoice:</b> <code>{$invoiceNumber}</code>
💰 <b>Nominal:</b> {$nominal}

Transaksi Anda telah disetujui oleh Owner.
Dana akan segera diproses.

<b>Status:</b> SELESAI ✅
HTML;

        $this->sendMessage($teknisi->telegram_chat_id, $message);

        Log::channel('ai_autofill')->info('📨 [TELEGRAM] Force approve notification sent to technician', [
            'transaction_id' => $transaction->id,
            'teknisi_id'     => $teknisi->id,
        ]);
    }

    /**
     * Notifikasi pembayaran sedang diproses
     */
    public function notifyPaymentProcessing(Transaction $transaction): void
    {
        $teknisi = $transaction->submitter;
        
        if (!$teknisi || !$teknisi->telegram_chat_id) {
            return;
        }

        $invoiceNumber = $transaction->invoice_number;
        $nominal       = 'Rp ' . number_format($transaction->amount, 0, ',', '.');

        $message = <<<HTML
⏳ <b>PEMBAYARAN SEDANG DIPROSES</b>

📋 <b>Invoice:</b> <code>{$invoiceNumber}</code>
💰 <b>Nominal:</b> {$nominal}

Transaksi Anda sudah disetujui dan sedang diproses pembayaran.
Mohon tunggu konfirmasi selanjutnya.

<b>Status:</b> DALAM PROSES ⏳
HTML;

        $this->sendMessage($teknisi->telegram_chat_id, $message);
    }

    // ════════════════════════════════════════════════════════
    //  BROADCAST METHODS
    // ════════════════════════════════════════════════════════

    /**
     * Broadcast ke SEMUA karyawan yang terdaftar
     */
    public function broadcastToAllStaff(string $message): array
    {
        return $this->sendToMultipleUsers(
            User::query(),
            $message
        );
    }

    /**
     * Broadcast ke karyawan berdasarkan ROLE
     * 
     * @param array $roles ['owner', 'admin', 'teknisi']
     */
    public function broadcastByRole(array $roles, string $message): array
    {
        return $this->sendToMultipleUsers(
            User::query()->whereIn('role', $roles),
            $message
        );
    }

    // ─── Setup Helpers ────────────────────────────────────────────────

    /**
     * Daftarkan URL webhook ke Telegram Server
     */
    public function setWebhook(string $webhookUrl): array
    {
        if (!$this->botToken) {
            return ['ok' => false, 'description' => 'Bot token not configured'];
        }

        $response = Http::timeout(15)->post("{$this->apiUrl}/setWebhook", [
            'url'                  => $webhookUrl,
            'allowed_updates'      => ['message', 'callback_query'],
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
     * Hapus webhook yang terdaftar
     */
    public function deleteWebhook(): array
    {
        $response = Http::timeout(10)->post("{$this->apiUrl}/deleteWebhook", [
            'drop_pending_updates' => true,
        ]);
        return $response->json() ?? ['ok' => false];
    }

    /**
     * Cek info webhook yang sedang aktif
     */
    public function getWebhookInfo(): array
    {
        $response = Http::timeout(10)->get("{$this->apiUrl}/getWebhookInfo");
        return $response->json() ?? [];
    }

    // ─── Callback Query Helpers ───────────────────────────────────────

    /**
     * Answer callback query (respond ke inline button click)
     * WAJIB dipanggil untuk mencegah button stuck di loading
     * 
     * @param string $callbackQueryId Callback query ID dari Telegram
     * @param string $text Text yang ditampilkan di popup (max 200 chars)
     * @param bool $showAlert True = popup alert, False = toast notification
     * @return bool
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): bool
    {
        if (!$this->botToken) {
            return false;
        }

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/answerCallbackQuery", [
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
                'show_alert' => $showAlert,
            ]);

            if ($response->successful()) {
                Log::channel('ai_autofill')->debug('✅ [TELEGRAM] Callback answered', [
                    'callback_id' => substr($callbackQueryId, 0, 20) . '...',
                ]);
                return true;
            }

            Log::channel('ai_autofill')->warning('⚠️ [TELEGRAM] Failed to answer callback', [
                'response' => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::channel('ai_autofill')->error('❌ [TELEGRAM] Exception answering callback', [
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Edit message text (update pesan yang sudah dikirim)
     * Berguna untuk update message setelah button diklik
     * 
     * @param string $chatId Chat ID
     * @param int $messageId Message ID yang mau di-edit
     * @param string $text Text baru
     * @param array $replyMarkup Optional new inline keyboard
     * @return bool
     */
    public function editMessageText(string $chatId, int $messageId, string $text, array $replyMarkup = []): bool
    {
        if (!$this->botToken) {
            return false;
        }

        try {
            $payload = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            if (!empty($replyMarkup)) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $response = Http::timeout(10)->post("{$this->apiUrl}/editMessageText", $payload);

            if ($response->successful()) {
                Log::channel('ai_autofill')->debug('✅ [TELEGRAM] Message edited', [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                ]);
                return true;
            }

            Log::channel('ai_autofill')->warning('⚠️ [TELEGRAM] Failed to edit message', [
                'response' => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::channel('ai_autofill')->error('❌ [TELEGRAM] Exception editing message', [
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
}