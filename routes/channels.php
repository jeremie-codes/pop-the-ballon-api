<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;


Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {

    return Conversation::query()
        ->whereKey($conversationId)
        ->where(function ($q) use ($user) {
            $q->where('user_one_id', $user->id)
              ->orWhere('user_two_id', $user->id);
        })
        ->exists();
});

Broadcast::channel('online', function ($user) {
    return [
        'id' => (string) $user->id,
        'name' => $user->displayName(),
    ];
});
