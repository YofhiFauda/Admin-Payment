<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class OcrStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Transaction $transaction,
        private readonly string      $aiStatus,   // 'completed' | 'error' | 'auto-reject'
        private readonly ?int        $confidence = null,
        private readonly ?string     $customMessage = null, // Pesan detail dari n8n/AI
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isSuccess    = $this->aiStatus === 'completed';
        $isAutoReject = $this->aiStatus === 'auto-reject';
        
        // ── ✅ Penentuan Title ──
        $title = $isSuccess ? 'Nota berhasil diproses oleh AI!' : 'AI gagal memproses nota';
        if ($isAutoReject) {
            $title = '⛔ Auto-Reject: Nota Ditolak';
        }

        // ── ✅ Penentuan Message ──
        $message = $isSuccess
            ? "Invoice #{$this->transaction->invoice_number} telah diisi otomatis"
              . ($this->confidence ? " (akurasi {$this->confidence}%)" : '') . '.'
            : "Invoice #{$this->transaction->invoice_number} gagal diproses. Silakan isi form secara manual.";

        if ($isAutoReject) {
            $reason = $this->customMessage ?? '';
            if (stripos($reason, 'duplikat') !== false || stripos($reason, 'sama') !== false) {
                $message = "Invoice #{$this->transaction->invoice_number} ditolak karena sudah terdaftar di sistem (Duplikat).";
            } elseif (stripos($reason, 'tanggal') !== false || stripos($reason, 'expired') !== false || stripos($reason, 'hari') !== false) {
                $message = "Invoice #{$this->transaction->invoice_number} ditolak karena sudah melewati batas waktu maksimal 2 hari.";
            } else {
                $message = "Invoice #{$this->transaction->invoice_number} ditolak otomatis oleh sistem: " . ($this->customMessage ?? 'Alasan tidak diketahui');
            }
        }

        // Dispatch real-time toast event. Jangan gagalkan database notification
        // hanya karena Reverb/broadcast sedang tidak tersedia.
        $unreadCount = $notifiable->unreadNotifications()->count() + 1;
        try {
            broadcast(new \App\Events\NotificationReceived($notifiable->id, $unreadCount, $title, $message, 'ocr_status'));
        } catch (\Throwable $e) {
            \Log::warning('Broadcast NotificationReceived gagal (non-critical)', [
                'notifiable_id' => $notifiable->id,
                'transaction_id' => $this->transaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'type'           => 'ocr_status',
            'ai_status'      => $this->aiStatus,
            'transaction_id' => $this->transaction->id,
            'invoice_number' => $this->transaction->invoice_number,
            'upload_id'      => $this->transaction->upload_id,
            'confidence'     => $this->confidence,

            // Pesan untuk ditampilkan di UI
            'title'   => $title,
            'message' => $message,

            'url'     => route('transactions.show', $this->transaction->id),
            'icon'    => $isSuccess ? 'check-circle' : ($isAutoReject ? 'shield-off' : 'x-circle'),
            'color'   => $isSuccess ? 'green' : ($isAutoReject ? 'slate' : 'red'),
        ];
    }
}
