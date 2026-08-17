<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public User $matchedUser;
    public Conversation $conversation;

    public function __construct(int $userId, User $matchedUser, Conversation $conversation)
    {
        $this->userId = $userId;
        $this->matchedUser = $matchedUser;
        $this->conversation = $conversation;
    }

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
        return 'MatchCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'match' => [
                'id' => (string) $this->matchedUser->id,
                'conversationId' => (string) $this->conversation->id,
                'name' => $this->matchedUser->displayName(),
                'avatar' => optional($this->matchedUser->photos->first())->path ?? '',
                'verified' => $this->matchedUser->isVerified(),
            ],
        ];
    }
}
