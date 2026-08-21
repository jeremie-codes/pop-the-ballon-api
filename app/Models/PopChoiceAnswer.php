<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PopChoiceAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pop_choice_id',
        'pop_choice_session_id',
        'answer',
        'answered_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function popChoice(): BelongsTo
    {
        return $this->belongsTo(PopChoice::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            PopChoiceSession::class,
            'pop_choice_session_id'
        );
    }
}