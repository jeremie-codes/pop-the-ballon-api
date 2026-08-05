<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'iso',
        'phone_code',
        'flag',
        'is_active',
    ];
}
