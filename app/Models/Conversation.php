<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'match_id',
        'user_one_id',
        'user_two_id',
        'last_message_at',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
        //return $this->hasMany(Message::class)->latest();
    }

    public function deletions()
    {
        return $this->hasMany(ConversationDeletion::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function scopeSupport($query)
    {
        return $query->where('type', 'support');
    }

    public function scopeMatch($query)
    {
        return $query->where('type', 'match');
    }

    public function getClientAttribute()
    {
        if ($this->userOne?->is_staff) {
            return $this->userTwo;
        }

        return $this->userOne;
    }

    public function getStaffAttribute()
    {
        if ($this->userOne?->is_staff) {
            return $this->userOne;
        }

        return $this->userTwo;
    }

    public static function getConversationId($userOneId, $userTwoId)
    {
        $conversation = self::where(function ($query) use ($userOneId, $userTwoId) {
            $query->where('user_one_id', $userOneId)
                  ->where('user_two_id', $userTwoId);
        })->orWhere(function ($query) use ($userOneId, $userTwoId) {
            $query->where('user_one_id', $userTwoId)
                  ->where('user_two_id', $userOneId);
        })->first();

        return $conversation ? $conversation->id : null;
    }
}
