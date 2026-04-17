<?php

namespace App\Notifications;

use App\Models\PriceAnomaly;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriceAnomalyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public PriceAnomaly $anomaly)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->anomaly->loadMissing(['transaction.submitter']);

        return [
            'type'              => 'price_anomaly',
            'anomaly_id'        => $this->anomaly->id,
            'transaction_id'    => $this->anomaly->transaction_id,
            'invoice_number'    => $this->anomaly->transaction->invoice_number,
            'item_name'         => $this->anomaly->item_name,
            'excess_percentage' => $this->anomaly->excess_percentage,
            'severity'          => $this->anomaly->severity,
            'submitter_name'    => $this->anomaly->transaction->submitter->name ?? '-',
            'title'             => '⚠️ Anomali Harga Terdeteksi',
            'message'           => "Item '{$this->anomaly->item_name}' melebihi harga referensi (+{$this->anomaly->excess_percentage}%).",
            'url'               => route('price-index.anomalies'),
        ];
    }
}
