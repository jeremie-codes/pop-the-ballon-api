<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->displayName(),
            'age' => $this->age() ?? 18,
            'city' => $this->city ?? '',
            'country' => $this->country ?? '',
            'bio' => $this->bio ?? '',
            'intention' => $this->intention ?? '',
            'verified' => (bool) $this->verified,
            'avatar' => optional($this->photos->first())->path,
            'pictures' => $this->photos
                ->map(fn($photo)=>[
                    'id'=>(string)$photo->id,
                    'name'=>$photo->path,
                    'isPrimary'=>(bool)$photo->is_primary,
                ])
                ->values(),

            'interests'=>$this->interests->pluck('name')->values(),
        ];
    }
}
