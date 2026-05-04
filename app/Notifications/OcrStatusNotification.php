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
        private readonly string      $aiStatus,   // 'completed' | 'error'
        private readonly ?int        $confidence = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isSuccess = $this->aiStatus === 'completed';
        
        $title = $isSuccess
            ? 'Nota berhasil diproses oleh AI!'
            : 'AI gagal memproses nota';

        $message = $isSuccess
            ? "Invoice #{$this->transaction->invoice_number} telah diisi otomatis"
              . ($this->confidence ? " (akurasi {$this->confidence}%)" : '') . '.'
            : "Invoice #{$this->transaction->invoice_number} gagal diproses. Silakan isi form secara manual.";

        // Dispatch real-time toast event
        $unreadCount = $notifiable->unreadNotifications()->count() + 1;
        broadcast(new \App\Events\NotificationReceived($notifiable->id, $unreadCount, $title, $message, 'ocr_status'));

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
            'icon'    => $isSuccess ? 'check-circle' : 'x-circle',
            'color'   => $isSuccess ? 'green' : 'red',
        ];
    }
}