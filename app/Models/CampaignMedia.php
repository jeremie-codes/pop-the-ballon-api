<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CampaignMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'type',
        'path',
        'order',
        'visitor_id',
        'ip_address',
        'user_agent',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
