<?php

namespace App\Events;

use App\Models\AppNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AppNotification $notification;

    public function __construct(AppNotification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'App.Models.User.' . $this->notification->user_id
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NotificationCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'notification' => [
                'id' => (string) $this->notification->id,
                'title' => $this->notification->title,
                'message' => $this->notification->message,
                'time' => $this->notification->created_at->toISOString(),
                'read' => false,
                'kind' => $this->notification->kind,

                'profileId' => $this->notification->profile_id
                    ? (string) $this->notification->profile_id
                    : null,

                'conversationId' => $this->notification->conversation_id
                    ? (string) $this->notification->conversation_id
                    : null,

                'avatar' => null,
            ],
        ];
    }
}
