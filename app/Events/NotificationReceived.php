<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public int $unreadCount;
    public string $title;
    public string $message;
    public string $type;

    public function __construct(int $userId, int $unreadCount, string $title, string $message, string $type = 'general')
    {
        $this->userId      = $userId;
        $this->unreadCount = $unreadCount;
        $this->title       = $title;
        $this->message     = $message;
        $this->type        = $type;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("notifications.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.received';
    }
}
