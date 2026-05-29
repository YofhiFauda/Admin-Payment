<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ExportStatusUpdated
 *
 * Broadcast progress update untuk async export ke user yang request.
 * Mirip dengan OcrStatusUpdated — user-specific private channel.
 */
class ExportStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public array $payload;

    /**
     * @param int   $userId
     * @param array $payload {
     *     export_id: string,
     *     status: 'queued'|'processing'|'completed'|'failed',
     *     progress_percent?: int,
     *     processed?: int,
     *     total?: int,
     *     filename?: string,
     *     file_size?: int,
     *     error_message?: string,
     *     download_url?: string
     * }
     */
    public function __construct(int $userId, array $payload)
    {
        $this->userId  = $userId;
        $this->payload = $payload;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('exports.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'export.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
