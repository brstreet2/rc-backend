<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AudioActiveResource extends AudioResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'promo' => $this->active_promotion
                ? PromotionResource::make($this->active_promotion)
                : null,
        ];
    }
}
