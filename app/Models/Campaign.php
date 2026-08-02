<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'title',
        'description',
        'campaign_type',
        'media_type',
        'status',
        'button_text',
        'target_type',
        'target_value',
        'priority',
        'budget',
        'price',
        'start_at',
        'end_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('start_at')
                ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                ->orWhere('end_at', '>=', now());
            });
    }

    public function getActiveCampaigns()
    {
        return Campaign::active()
            ->with('media')
            ->orderByDesc('priority')
            ->get();
    }

    public function media()
    {
        return $this->hasMany(CampaignMedia::class);
    }

    public function views()
    {
        return $this->hasMany(CampaignView::class);
    }

    public function clicks()
    {
        return $this->hasMany(CampaignClick::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
