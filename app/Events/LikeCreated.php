<?php

namespace App\Events;

use App\Models\User;
use App\Models\ProfileAction;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LikeCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public User $user;
    public ProfileAction $like;

    public function __construct(
        User $user,
        ProfileAction $like
    ) {
        $this->user = $user;
        $this->like = $like->load([
            'actor.photos'
        ]);
    }


    public function broadcastOn()
    {
        return new PrivateChannel(
            'App.Models.User.' . $this->user->id
        );
    }


    public function broadcastAs()
    {
        return 'LikeCreated';
    }
}
 