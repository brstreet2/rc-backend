<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin array{at:string,would_win:bool,reason:string,current_winner:?\App\Models\Promotion,projected_winner?:?\App\Models\Promotion,candidate:\App\Models\Promotion} */
class PromotionPreviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'at' => $this['at'],
            'would_win' => $this['would_win'],
            'reason' => $this['reason'],
            'current_winner' => $this['current_winner']
                ? PromotionResource::make($this['current_winner'])
                : null,
            'projected_winner' => isset($this['projected_winner']) && $this['projected_winner']
                ? PromotionResource::make($this['projected_winner'])
                : null,
            'candidate' => PromotionResource::make($this['candidate']),
        ];
    }
}
