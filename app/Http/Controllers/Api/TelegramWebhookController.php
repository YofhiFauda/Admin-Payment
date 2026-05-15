<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════
 *  TelegramWebhookController
 *
 *  Menerima callback dari Telegram Bot saat user mengirim pesan
 *  atau menekan tombol inline.
 *
 *  Fitur:
 *  1. Auto-registrasi chat_id via /daftar <email>
 *  2. Handle callback button (Terima pembayaran cash)
 * ═══════════════════════════════════════════════════════════════
 */
class TelegramWebhookController extends Controller
{
    private TelegramBotService $telegram;

    public function __construct(TelegramBotService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * POST /api/telegram/webhook
     * Dipanggil oleh Telegram server setiap ada pesan masuk ke bot.
     */
    public function handle(Request $request)
    {
        try {
            $update = $request->all();

            // 📝 LOG MENTAH: Gunakan ini untuk memastikan request sampai ke server
        Log::info('📥 [TELEGRAM WEBHOOK] Update Received', [
            'update_id' => $update['update_id'] ?? null,
            'type'      => isset($update['callback_query']) ? 'callback' : (isset($update['message']) ? 'message' : 'unknown'),
            'raw'       => $update
        ]);

            // ═══════════════════════════════════════════════════
            //  1. HANDLE CALLBACK QUERY (Tombol Inline)
            // ═══════════════════════════════════════════════════
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
                return response()->json(['ok' => true]);
            }

            // ═══════════════════════════════════════════════════
            //  2. HANDLE PESAN TEXT (/start, /daftar, dll)
            // ═══════════════════════════════════════════════════
            $message = $update['message'] ?? null;
            if (!$message) {
                return response()->json(['ok' => true]);
            }

            $chatId = $message['chat']['id'] ?? null;
            $text   = trim($message['text'] ?? '');
            $from   = $message['from'] ?? [];

            if (!$chatId) {
                return response()->json(['ok' => true]);
            }

            // ── Handle /start ──────────────────────────────────────
            if ($text === '/start' || str_starts_with($text, '/start')) {
                $this->handleStart($chatId, $from);
                return response()->json(['ok' => true]);
            }

            // ── Handle /daftar <email> ─────────────────────────────
            if (str_starts_with(strtolower($text), '/daftar')) {
                $parts = explode(' ', $text, 2);
                $email = trim($parts[1] ?? '');
                $this->handleDaftar($chatId, $email, $from);
                return response()->json(['ok' => true]);
            }

            // ── Handle /status ─────────────────────────────────────
            if ($text === '/status') {
                $this->handleStatus($chatId);
                return response()->json(['ok' => true]);
            }

            // ── Handle /cabut ──────────────────────────────────────
            if ($text === '/cabut') {
                $this->handleCabut($chatId);
                return response()->json(['ok' => true]);
            }

            // ── Pesan tidak dikenali (Hanya respon jika di private chat) ──
            if (isset($message['chat']['type']) && $message['chat']['type'] === 'private') {
                $this->telegram->sendMessage($chatId,
                    "❓ Perintah tidak dikenal.\n\nGunakan:\n" .
                    "• /daftar &lt;email&gt; — Daftarkan akun\n" .
                    "• /status — Cek status pendaftaran\n" .
                    "• /cabut — Hapus notifikasi"
                );
            }

        } catch (\Exception $e) {
            Log::error('❌ [TELEGRAM WEBHOOK] Crash:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ]);
        }

        // Telegram mewajibkan respon 200 OK agar tidak mengirim ulang pesan yang sama
        return response()->json(['ok' => true]);
    }

    // ═══════════════════════════════════════════════════
    //  HANDLE CALLBACK QUERY (Tombol "Terima" dll)
    // ═══════════════════════════════════════════════════
    
    private function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackId   = $callbackQuery['id'] ?? null;
        $callbackData = $callbackQuery['data'] ?? '';
        $fromId       = $callbackQuery['from']['id'] ?? null;
        $chatId       = $callbackQuery['message']['chat']['id'] ?? null;
        $messageId    = $callbackQuery['message']['message_id'] ?? null;

        if (!$callbackId || !$callbackData) {
            return;
        }

        Log::info('📱 [TELEGRAM CALLBACK] Received', [
            'action'  => $callbackData,
            'from_id' => $fromId,
            'chat_id' => $chatId,
        ]);

        try {
            // Parse callback_data format: "confirm_cash:123"
            $parts         = explode(':', $callbackData, 2);
            $action        = $parts[0] ?? '';
            $transactionId = (int) ($parts[1] ?? 0);

            // 1. Validasi User (Gunakan fromId, bukan chatId - Penting untuk Group!)
            $user = User::where('telegram_chat_id', (string)$fromId)->first();
            if (!$user) {
                $this->answerCallbackQuery($callbackId, '⚠️ Akun Telegram Anda belum terdaftar di sistem.', true);
                return;
            }

            // 2. Load Transaksi
            $transaction = Transaction::find($transactionId);
            if (!$transaction) {
                $this->answerCallbackQuery($callbackId, '❌ Transaksi tidak ditemukan.', true);
                return;
            }

            // 3. Routing Aksi
            if ($action === 'confirm_cash') {
                $this->handleConfirmCash($callbackId, $transaction, $user, $chatId, $messageId);
            } elseif ($action === 'report_issue') {
                $this->handleReportIssue($callbackId, $transaction, $user, $chatId, $messageId);
            } else {
                $this->answerCallbackQuery($callbackId, '❓ Aksi tidak dikenali.');
            }

        } catch (\Exception $e) {
            Log::error('❌ [TELEGRAM CALLBACK] Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->answerCallbackQuery($callbackId, '❌ Terjadi kesalahan internal sistem.', true);
        }
    }

    /**
     * Logika Konfirmasi Cash
     */
    private function handleConfirmCash(string $callbackId, Transaction $transaction, User $user, $chatId, $messageId): void
    {
        // 🛡️ Prevent double confirmation
        if (in_array($transaction->status, ['completed', 'approved', 'Ditolak Teknisi'])) {
            $this->answerCallbackQuery($callbackId, 'ℹ️ Transaksi ini sudah diproses sebelumnya.', false);
            return;
        }

        // Validasi kepemilikan (Khusus Teknisi)
        if ($user->id !== $transaction->submitted_by && $user->role === 'teknisi') {
            $this->answerCallbackQuery($callbackId, '❌ Anda hanya bisa konfirmasi pengajuan Anda sendiri.', true);
            return;
        }

        // ✅ Answer Telegram FAST (Prevent spinning icon)
        $this->answerCallbackQuery($callbackId, '✅ Konfirmasi diterima, memproses...');

        // Update Database
        $transaction->update([
            'status'         => 'completed',
            'konfirmasi_at'  => now(),
            'konfirmasi_by'  => $user->id,
        ]);

        // Broadcast & Notification
        broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));
        $user->notify(new \App\Notifications\TransactionStatusNotification($transaction, 'completed'));

        // Update Message UI (Remove Buttons)
        $timestamp = now()->translatedFormat('d F Y H:i');
        $this->telegram->editMessageText($chatId, $messageId, 
            "✅ <b>PENGAMBILAN DANA TUNAI SELESAI</b>\n\n" .
            "Transaksi <code>{$transaction->invoice_number}</code> telah dikonfirmasi diterima.\n" .
            "👤 <b>Penerima:</b> {$user->name}\n" .
            "⏰ <b>Waktu:</b> {$timestamp} WIB\n\n" .
            "Status: <b>SELESAI</b> ✅"
        );
    }

    /**
     * Logika Laporan Masalah (Tolak)
     */
    private function handleReportIssue(string $callbackId, Transaction $transaction, User $user, $chatId, $messageId): void
    {
        if (in_array($transaction->status, ['completed', 'approved', 'Ditolak Teknisi'])) {
            $this->answerCallbackQuery($callbackId, 'ℹ️ Transaksi ini sudah diproses sebelumnya.', false);
            return;
        }

        if ($user->id !== $transaction->submitted_by && $user->role === 'teknisi') {
            $this->answerCallbackQuery($callbackId, '❌ Anda hanya bisa menolak pengajuan Anda sendiri.', true);
            return;
        }

        $this->answerCallbackQuery($callbackId, '❌ Laporan diterima, membatalkan...');

        $transaction->update([
            'status'           => 'rejected',
            'rejection_reason' => "Ditolak oleh {$user->name} via Telegram",
            'konfirmasi_at'    => now(),
            'konfirmasi_by'    => $user->id,
        ]);

        broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));
        $user->notify(new \App\Notifications\TransactionStatusNotification($transaction, 'rejected'));

        $this->telegram->editMessageText($chatId, $messageId, 
            "❌ <b>PEMBAYARAN DITOLAK</b>\n\n" .
            "Transaksi <code>{$transaction->invoice_number}</code> telah dibatalkan oleh teknisi.\n" .
            "👤 <b>Oleh:</b> {$user->name}\n" .
            "📝 <b>Alasan:</b> Masalah fisik dana / nominal tidak sesuai.\n\n" .
            "Status: <b>DITOLAK</b> ❌"
        );
    }

    /**
     * Kirim answer callback query (popup notification di Telegram)
     * Menggunakan TelegramBotService method
     */
    private function answerCallbackQuery(string $callbackId, string $text, bool $showAlert = false): void
    {
        $this->telegram->answerCallbackQuery($callbackId, $text, $showAlert);
    }

    // ─── Handlers ─────────────────────────────────────────────────

    private function handleStart(string $chatId, array $from): void
    {
        $firstName = $from['first_name'] ?? 'Pengguna';

        // Cek apakah sudah terdaftar
        $user = User::where('telegram_chat_id', (string) $chatId)->first();
        if ($user) {
            $this->telegram->sendMessage($chatId,
                "👋 Halo <b>{$firstName}</b>!\n\n" .
                "✅ Akun Anda sudah terdaftar sebagai <b>{$user->name}</b> ({$user->role}).\n\n" .
                "Anda akan otomatis menerima notifikasi ketika ada transaksi bermasalah."
            );
            return;
        }

        $this->telegram->sendMessage($chatId,
            "👋 Halo <b>{$firstName}</b>!\n\n" .
            "Selamat datang di <b>WhusNet Admin Bot</b> 🤖\n\n" .
            "Bot ini akan mengirimkan notifikasi real-time ketika:\n" .
            "• 🚨 Transfer berselisih (Flagged)\n" .
            "• ✅ Force Approve dilakukan\n" .
            "• ⛔ Nota di-Auto Reject (Admin)\n" .
            "• 💰 Pembayaran cash siap diambil (Teknisi)\n" .
            "• 💸 Transfer berhasil masuk (Teknisi)\n\n" .
            "─────────────────────\n" .
            "📲 <b>Cara Mendaftar:</b>\n" .
            "Kirim perintah:\n\n" .
            "<code>/daftar email@anda.com</code>\n\n" .
            "Gunakan email yang sama dengan akun login sistem."
        );
    }

    private function handleDaftar(string $chatId, string $email, array $from): void
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->telegram->sendMessage($chatId,
                "❌ <b>Format email tidak valid.</b>\n\n" .
                "Contoh penggunaan:\n<code>/daftar admin@whusnet.com</code>"
            );
            return;
        }

        // Cari user berdasarkan email
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->telegram->sendMessage($chatId,
                "❌ <b>Email tidak ditemukan.</b>\n\n" .
                "Pastikan email yang dimasukkan terdaftar di sistem WhusNet.\n\n" .
                "<code>/daftar email@anda.com</code>"
            );
            return;
        }

        // Cek apakah email ini sudah terhubung ke chat_id lain
        if ($user->telegram_chat_id && $user->telegram_chat_id !== (string)$chatId) {
            $this->telegram->sendMessage($chatId,
                "⚠️ Akun <b>{$user->name}</b> sudah terhubung ke akun Telegram lain.\n\n" .
                "Hubungi Admin untuk mereset jika diperlukan."
            );
            return;
        }

        // Cek apakah chat_id ini sudah terdaftar ke user lain
        $existingUser = User::where('telegram_chat_id', (string)$chatId)->first();
        if ($existingUser && $existingUser->id !== $user->id) {
            $this->telegram->sendMessage($chatId,
                "⚠️ Akun Telegram ini sudah terdaftar untuk <b>{$existingUser->name}</b>.\n\n" .
                "Gunakan /cabut terlebih dahulu jika ingin mengganti akun."
            );
            return;
        }

        // ✅ Simpan chat_id ke profil user
        $user->update(['telegram_chat_id' => (string)$chatId]);

        Log::info('📱 [TELEGRAM] Chat ID registered', [
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'role'      => $user->role,
            'chat_id'   => $chatId,
        ]);

        $roleLabel = match ($user->role) {
            'owner'   => 'Owner 👑',
            'admin'   => 'Admin 🔧',
            'atasan'  => 'Atasan 📋',
            'teknisi' => 'Teknisi 🔩',
            default   => ucfirst($user->role),
        };

        $this->telegram->sendMessage($chatId,
            "✅ <b>Pendaftaran Berhasil!</b>\n\n" .
            "👤 Nama: <b>{$user->name}</b>\n" .
            "📧 Email: <code>{$email}</code>\n" .
            "🏷️ Role: {$roleLabel}\n\n" .
            "Mulai sekarang Anda akan menerima notifikasi real-time dari sistem WhusNet.\n\n" .
            "Gunakan /status untuk melihat info akun Anda."
        );
    }

    private function handleStatus(string $chatId): void
    {
        $user = User::where('telegram_chat_id', (string)$chatId)->first();

        if (!$user) {
            $this->telegram->sendMessage($chatId,
                "❌ <b>Belum terdaftar.</b>\n\n" .
                "Kirim /daftar &lt;email&gt; untuk mendaftar."
            );
            return;
        }

        $this->telegram->sendMessage($chatId,
            "📊 <b>Status Pendaftaran</b>\n\n" .
            "👤 Nama: <b>{$user->name}</b>\n" .
            "📧 Email: <code>{$user->email}</code>\n" .
            "🏷️ Role: <b>{$user->role}</b>\n" .
            "✅ Status: <b>Aktif</b>\n\n" .
            "Notifikasi real-time: <b>ON</b> 🔔"
        );
    }

    private function handleCabut(string $chatId): void
    {
        $user = User::where('telegram_chat_id', (string)$chatId)->first();

        if (!$user) {
            $this->telegram->sendMessage($chatId,
                "ℹ️ Anda belum terdaftar, tidak ada yang perlu dihapus."
            );
            return;
        }

        $name = $user->name;
        $user->update(['telegram_chat_id' => null]);

        Log::channel('ai_autofill')->info('📱 [TELEGRAM] Chat ID removed', [
            'user_id'   => $user->id,
            'user_name' => $user->name,
        ]);

        $this->telegram->sendMessage($chatId,
            "✅ <b>Notifikasi dinonaktifkan.</b>\n\n" .
            "Akun <b>{$name}</b> tidak lagi terhubung ke bot ini.\n\n" .
            "Gunakan /daftar &lt;email&gt; untuk mendaftar kembali."
        );
    }
}