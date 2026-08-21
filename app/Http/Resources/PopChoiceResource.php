<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PopChoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'option_a' => $this->option_a,
            'option_b' => $this->option_b,
            'type' => $this->type?->value,
            'category' => $this->category?->value,
        ];
    }
}
