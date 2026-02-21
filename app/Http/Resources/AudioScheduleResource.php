<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin array{audio_id:int,from:?string,to:?string,segments:array<int,array{from:string,to:string,winner_promotion:?\App\Models\Promotion}>} */
class AudioScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'audio_id' => $this['audio_id'],
            'from' => $this['from'],
            'to' => $this['to'],
            'segments' => collect($this['segments'])->map(function (array $segment): array {
                return [
                    'from' => $segment['from'],
                    'to' => $segment['to'],
                    'winner_promotion' => $segment['winner_promotion']
                        ? PromotionResource::make($segment['winner_promotion'])
                        : null,
                ];
            })->all(),
        ];
    }
}
