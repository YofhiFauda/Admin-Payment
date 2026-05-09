<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Transaction;

class TransactionCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
        
        // Debug logging
        \Log::info('🔔 [BROADCAST] TransactionCreated event constructed', [
            'id' => $transaction->id,
            'invoice_number' => $transaction->invoice_number,
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
        // Broadcast to:
        // 1. Global channel for Admin/Owner/Atasan to see all new transactions
        // 2. Personal channel for the creator to get real-time feedback
        return [
            new PrivateChannel('transactions'),
            new PrivateChannel('transactions.' . $this->transaction->submitted_by),
        ];
    }
    
    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'transaction.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'transaction' => $this->transaction->toSearchArray()
        ];
    }
}
