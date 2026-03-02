<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Events\NotificationReceived;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class TransactionStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Transaction $transaction,
        private readonly string      $status // 'approved', 'rejected', 'completed'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusLabel = match($this->status) {
            'approved'  => 'Disetujui',
            'rejected'  => 'Ditolak',
            'completed' => 'Selesai',
            default     => ucfirst($this->status),
        };

        $isSuccess = in_array($this->status, ['approved', 'completed']);
        
        $title = $this->status === 'rejected' 
            ? "Transaksi {$this->transaction->invoice_number} ditolak" 
            : "Transaksi {$this->transaction->invoice_number} {$statusLabel}";

        $message = "Status transaksi untuk {$this->transaction->customer} telah diubah menjadi {$statusLabel}.";
        
        if ($this->status === 'rejected' && $this->transaction->rejection_reason) {
            $message .= " Alasan penolakan: {$this->transaction->rejection_reason}";
        }

        // Dispatch real-time badge update event
        $unreadCount = $notifiable->unreadNotifications()->count() + 1; // +1 karena notifikasi ini belum tersimpan saat broadcast
        broadcast(new NotificationReceived($notifiable->id, $unreadCount, $title, $message));

        return [
            'type'               => 'transaction_status',
            'transaction_status' => $this->status,
            'transaction_id'     => $this->transaction->id,
            'invoice_number'     => $this->transaction->invoice_number,

            'title'   => $title,
            'message' => $message,

            'url'     => route('transactions.show', $this->transaction->id),
            'icon'    => $isSuccess ? 'check-circle' : 'x-circle',
            'color'   => $isSuccess ? 'green' : 'red',
        ];
    }
}
