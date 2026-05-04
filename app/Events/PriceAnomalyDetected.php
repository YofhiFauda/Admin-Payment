<?php

namespace App\Events;

use App\Models\PriceAnomaly;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PriceAnomalyDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public PriceAnomaly $anomaly)
    {
        $this->anomaly->load(['transaction.submitter']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.management'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id'                => $this->anomaly->id,
            'transaction_id'    => $this->anomaly->transaction_id,
            'invoice_number'    => $this->anomaly->transaction->invoice_number,
            'item_name'         => $this->anomaly->item_name,
            'input_price'       => $this->anomaly->input_price,
            'reference_max'     => $this->anomaly->reference_max_price,
            'excess_percentage' => $this->anomaly->excess_percentage,
            'severity'          => $this->anomaly->severity,
            'severity_label'    => $this->anomaly->severity_label,
            'submitter_name'    => $this->anomaly->transaction->submitter->name ?? '-',
            'url'               => route('price-index.anomalies'),
        ];
    }
}
