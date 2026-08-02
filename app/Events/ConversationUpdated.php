<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public int $userId,
        public bool $incrementUnread,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'App.Models.User.'.$this->userId
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ConversationUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => (string) $this->message->conversation_id,
            'message_id' => (string) $this->message->id,
            'sender_id' => (string) $this->message->sender_id,
            'message' => $this->message->type === \App\Enums\MessageType::VOICE
                ? 'Message vocal'
                : ($this->message->type === \App\Enums\MessageType::IMAGE ? 'Image' : $this->message->body),
            'time' => $this->message->created_at->toISOString(),
            'created_at' => $this->message->created_at->toISOString(),
            'increment_unread' => $this->incrementUnread,
            'read' => $this->message->read_at !== null,
        ];
    }
}
