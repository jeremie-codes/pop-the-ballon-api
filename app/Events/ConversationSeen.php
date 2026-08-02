<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationSeen implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $senderId,
        public int $readerId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'App.Models.User.'.$this->senderId
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ConversationSeen';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => (string) $this->conversationId,
            'reader_id' => (string) $this->readerId,
        ];
    }
}
