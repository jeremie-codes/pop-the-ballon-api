<?php

namespace App\Models;

use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'body',
        'attachment',
        'attachment_duration',
        'attachment_size',
        'attachment_mime',
        'read_at',
        'is_broadcast',
    ];

    protected $casts = [
        'type' => MessageType::class,
        'read_at' => 'datetime',
        'attachment_duration' => 'integer',
        'attachment_size' => 'integer',
        'is_broadcast' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
