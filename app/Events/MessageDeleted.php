<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $messageId,
        public int $conversationId,
        public int $deletedBy,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'conversation.' . $this->conversationId
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageDeleted';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => (string) $this->messageId,
            'conversation_id' => (string) $this->conversationId,
            'deleted_by' => (string) $this->deletedBy,
        ];
    }
}
