<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Promotion */
class PromotionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'audio_id' => $this->audio_id,
            'network_id' => $this->network_id,
            'mformat' => $this->mformat,
            'channel_id' => $this->channel_id,
            'priority' => $this->priority,
            'version' => $this->version,
            'visible' => (bool) $this->visible,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
