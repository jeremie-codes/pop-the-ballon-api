<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'identity_type',
        'document_path',
        'selfie_path',
        'status',
        'amount',
        'currency',
        'reference',
        'order_number',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
