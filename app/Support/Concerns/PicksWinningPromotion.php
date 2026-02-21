<?php

namespace App\Support\Concerns;

use App\Models\Audio;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

trait PicksWinningPromotion
{
    /**
     * Pick the single winning promotion for an audio from candidate promotions.
     */
    protected function pickWinningPromotionForAudio(Audio $audio, EloquentCollection $promotions): ?Promotion
    {
        $scored = $promotions
            ->map(function (Promotion $promotion) use ($audio): ?array {
                $scopeRank = $this->promotionScopePriority($audio, $promotion);

                if ($scopeRank === null) {
                    return null;
                }

                return [
                    'scope_rank' => $scopeRank,
                    'promotion' => $promotion,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (count($scored) === 0) {
            return null;
        }

        usort($scored, function (array $left, array $right): int {
            if ($left['scope_rank'] !== $right['scope_rank']) {
                return $left['scope_rank'] <=> $right['scope_rank'];
            }

            $priorityCompare = $right['promotion']->priority <=> $left['promotion']->priority;
            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            $versionCompare = $right['promotion']->version <=> $left['promotion']->version;
            if ($versionCompare !== 0) {
                return $versionCompare;
            }

            return $right['promotion']->created_at <=> $left['promotion']->created_at;
        });

        return $scored[0]['promotion'];
    }

    /**
     * Compute scope specificity priority for a promotion against an audio.
     */
    protected function promotionScopePriority(Audio $audio, Promotion $promotion): ?int
    {
        if (
            $promotion->network_id === $audio->network_id
            && $promotion->mformat === $audio->mformat
            && $promotion->channel_id === $audio->channel_id
        ) {
            return 1;
        }

        if (
            $promotion->network_id === $audio->network_id
            && $promotion->mformat === $audio->mformat
            && $promotion->channel_id === null
        ) {
            return 2;
        }

        if (
            $promotion->network_id === $audio->network_id
            && $promotion->mformat === null
            && $promotion->channel_id === null
        ) {
            return 3;
        }

        if (
            $promotion->network_id === null
            && $promotion->mformat === null
            && $promotion->channel_id === null
        ) {
            return 4;
        }

        return null;
    }
}
