<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Events\NotificationReceived;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class OwnerApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Transaction $transaction,
        private readonly string      $approverName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $amount = Transaction::formatShortRupiah($this->transaction->effective_amount);

        $title = "Membutuhkan Persetujuan Anda";
        $message = "Transaksi {$this->transaction->invoice_number} senilai {$amount} " .
                   "telah disetujui oleh {$this->approverName} dan membutuhkan persetujuan final Anda.";

        // Dispatch real-time badge update event
        $unreadCount = $notifiable->unreadNotifications()->count() + 1;
        broadcast(new NotificationReceived($notifiable->id, $unreadCount, $title, $message, 'owner_approval'));

        return [
            'type'               => 'owner_approval',
            'transaction_status' => 'approved',
            'transaction_id'     => $this->transaction->id,
            'invoice_number'     => $this->transaction->invoice_number,

            'title'   => $title,
            'message' => $message,

            'url'     => route('transactions.show', $this->transaction->id),
            'icon'    => 'alert-circle',
            'color'   => 'amber',
        ];
    }
}
