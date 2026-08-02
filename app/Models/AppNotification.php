<?php

namespace App\Models;

use App\Events\NotificationCreated;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'kind',
        'profile_id',
        'conversation_id',
        'avatar',
        'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public static function createAndBroadcast(
        array $attributes
    ): self {
        $notification = static::query()->create(
            $attributes
        );

        NotificationCreated::dispatch(
            $notification
        );

        return $notification;
    }
}
