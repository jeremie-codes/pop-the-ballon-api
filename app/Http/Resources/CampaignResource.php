<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner' => [
                'type' => $this->owner_type,
                'id' => $this->owner_id,
            ],
            'title' => $this->title,
            'description' => $this->description,
            'campaign_type' => $this->campaign_type,
            'media_type' => $this->media_type,
            'button' => [
                'text' => $this->button_text,
                'type' => $this->target_type,
                'value' => $this->target_value,
            ],
            'priority' => $this->priority,
            'budget' => $this->budget,
            'price' => $this->price,
            'statistics' => [
                'views' => $this->views_count,
                'clicks' => $this->clicks_count,
            ],
            'period' => [
                'start_at' => $this->start_at,
                'end_at' => $this->end_at,
            ],
            'media' => CampaignMediaResource::collection(
                $this->whenLoaded('media')
            ),
            'created_at' => $this->created_at,
        ];
    }
}
