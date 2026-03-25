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
            'approved'           => 'Disetujui',
            'rejected'           => 'Ditolak',
            'completed'          => 'Selesai',
            'pending_technician' => 'Pembayaran Siap',
            'waiting_payment'    => 'Sedang Diproses',
            'force_approved'     => 'Disetujui Owner',
            default              => ucfirst($this->status),
        };

        $isSuccess = in_array($this->status, ['approved', 'completed', 'pending_technician', 'waiting_payment', 'force_approved']);
        
        $title = match($this->status) {
            'rejected'           => "Transaksi {$this->transaction->invoice_number} ditolak",
            'pending_technician' => "💰 PEMBAYARAN CASH SIAP DIAMBIL",
            'waiting_payment'    => "⏳ PEMBAYARAN SEDANG DIPROSES",
            'force_approved'     => "✅ PEMBAYARAN DISETUJUI OWNER",
            default              => "Transaksi {$this->transaction->invoice_number} {$statusLabel}",
        };

        $catatanText = $this->transaction->description ? " Catatan: {$this->transaction->description}" : "";
        $message = match($this->status) {
            'pending_technician' => "Admin telah mengunggah bukti pembayaran untuk invoice #{$this->transaction->invoice_number}. Silakan ambil uang Anda.{$catatanText}",
            'waiting_payment'    => "Transaksi #{$this->transaction->invoice_number} sedang diproses untuk pembayaran.",
            'force_approved'     => "Transaksi #{$this->transaction->invoice_number} telah disetujui secara manual oleh Owner.",
            default              => "Status transaksi untuk {$this->transaction->customer} telah diubah menjadi {$statusLabel}.",
        };
        
        if ($this->status === 'rejected' && $this->transaction->rejection_reason) {
            $message .= " Alasan penolakan: {$this->transaction->rejection_reason}";
        }

        // Dispatch real-time toast event
        $unreadCount = $notifiable->unreadNotifications()->count() + 1;
        broadcast(new NotificationReceived($notifiable->id, $unreadCount, $title, $message, 'transaction_status'));

        return [
            'type'               => 'transaction_status',
            'transaction_status' => $this->status,
            'transaction_id'     => $this->transaction->id,
            'invoice_number'     => $this->transaction->invoice_number,

            'title'   => $title,
            'message' => $message,

            'url'     => route('transactions.show', $this->transaction->id),
            'icon'    => match($this->status) {
                'pending_technician' => 'banknote',
                'completed', 'force_approved' => 'check-circle',
                'waiting_payment'    => 'clock',
                'rejected'           => 'x-circle',
                default              => 'info'
            },
            'color'   => match($this->status) {
                'pending_technician' => 'amber',
                'completed', 'force_approved' => 'green',
                'waiting_payment'    => 'blue',
                'rejected'           => 'red',
                default              => 'blue'
            },
        ];
    }
}
