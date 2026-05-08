<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transactionId;
    public $invoiceNumber;

    /**
     * Create a new event instance.
     */
    public function __construct(int $transactionId, string $invoiceNumber)
    {
        $this->transactionId = $transactionId;
        $this->invoiceNumber = $invoiceNumber;
        
        // Debug logging
        \Log::info('🔔 [BROADCAST] TransactionDeleted event constructed', [
            'id' => $transactionId,
            'invoice_number' => $invoiceNumber,
            'broadcast_driver' => config('broadcasting.default'),
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('transactions'),
        ];
    }
    
    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'transaction.deleted';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->transactionId,
            'invoice_number' => $this->invoiceNumber,
        ];
    }
}
