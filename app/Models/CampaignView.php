<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignView extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'visitor_id',
        'ip_address',
        'user_agent',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
