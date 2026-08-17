<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;
    public ?string $clientId;

    public function __construct(Message $message, ?string $clientId = null)
    {
        $this->message = $message;
        $this->clientId = $clientId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => (string) $this->message->id,
            'type' => $this->message->type->value,
            'text' => $this->message->body,
            'attachment' => $this->message->attachment ?? null,
            'duration' => $this->message->attachment_duration,
            'time' => $this->message->created_at->format('H:i'),
            'read' => false,
            'sender_id' => (string) $this->message->sender_id,
            'clientId' => $this->clientId,
            'conversation_id' => (string) $this->message->conversation_id,
        ];
    }
}
