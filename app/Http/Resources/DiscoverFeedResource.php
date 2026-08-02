<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscoverFeedResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        if ($this['type'] === 'profile') {

            return [
                'type'=>'profile',
                'data'=> new ProfileResource(
                    $this['data']
                ),
            ];
        }

        if ($this['type'] === 'campaign') {
            return [
                'type'=>'campaign',
                'data'=> new CampaignResource(
                    $this['data']
                ),
            ];
        }

        return [];
    }
}
