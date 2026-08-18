<?php

namespace App\Services;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class SupportConversationService
{

    public function getSupportUser(): User
    {
        return User::where('is_staff', true)
            ->where('role', 'support')
            ->firstOrFail();
    }

    public function getOrCreate(User $user): Conversation
    {
        $support = $this->getSupportUser();


        return Conversation::firstOrCreate(
            [
                'type' => 'support',
                'user_one_id' => $support->id,
                'user_two_id' => $user->id,
            ],
            [
                'last_message_at' => now(),
            ]
        );
    }

    public function formatForMobile(Conversation $conversation): array
    {

        $support = $this->getSupportUser();
        $currentUserId = Auth::check() ? Auth::id() : null;

        $hasSupportMessage = $conversation->messages()
            ->where('sender_id', $support->id)
            ->exists();

        if (! $hasSupportMessage) {
            return [];
        }

        $lastMessage = $conversation->messages()->latest()->first();


        return [
            'id' => (string) $conversation->id,
            'profileId' => (string) $support->id,
            'verified' => (bool) $support->verified,
            'is_staff' => (bool) $support->is_staff,
            'name' => $support->full_name,
            'avatar' => $support->avatar ? asset('storage/' . $support->avatar) : '',
            'message' => $lastMessage ? $lastMessage->body : '',
            'time' => $conversation->last_message_at ? $conversation->last_message_at->diffForHumans() : '',
            'unread' => $conversation->messages
            ->filter(
                fn(Message $message) => $message->sender_id !== $currentUserId && $message->read_at === null
            )->count(),
            'matched' => false,
            'lastMessageAt' => optional($conversation->last_message_at)->toISOString(),
            'senderId' => $lastMessage && $lastMessage->sender_id !== null ? (string) $lastMessage->sender_id : null,
            'read' => $lastMessage ? $lastMessage->read_at !== null : false,
            'messages' => $conversation
                ->messages()
                ->oldest()
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => (string) $message->id,
                        'type' => $message->type->value,
                        'text' => $message->body,
                        'attachment' => $message->attachment ?? null,
                        'duration' => $message->attachment_duration,
                        'sender_id' => (string) $message->sender_id,
                        'time' => $message->created_at->format('H:i'),
                        'mine' =>false,
                        'read' => $message->read_at !== null,
                    ];
                })
                ->values(),
        ];
    }
}
