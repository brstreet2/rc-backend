<?php

namespace App\Services;

use App\Models\Audio;
use App\Models\Promotion;
use App\Support\Concerns\PicksWinningPromotion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

class PromotionService
{
    use PicksWinningPromotion;

    /**
     * Create a promotion and record an audit entry.
     */
    public function store(array $payload): Promotion
    {
        if (! isset($payload['created_at'])) {
            $payload['created_at'] = Carbon::now();
        }

        return Promotion::query()->create($payload);
    }

    /**
     * Update a promotion and record before/after audit details.
     */
    public function update(Promotion $promotion, array $payload): Promotion
    {
        $promotion->fill($payload);
        $promotion->save();

        return $promotion->refresh();
    }

    /**
     * Soft delete a promotion and record a deletion audit.
     */
    public function destroy(Promotion $promotion): void
    {
        $promotion->delete();
    }

    /**
     * Simulate whether a candidate promotion would win at a given moment.
     */
    public function preview(array $payload): array
    {
        $audio = Audio::query()->findOrFail($payload['audio_id']);
        $moment = isset($payload['at']) ? CarbonImmutable::parse($payload['at']) : CarbonImmutable::now();

        $candidate = new Promotion($payload);
        $candidate->created_at = CarbonImmutable::now();

        $existing = Promotion::query()
            ->where('audio_id', $audio->id)
            ->visible()
            ->activeAt($moment)
            ->get();

        $currentWinner = $this->pickWinningPromotionForAudio($audio, $existing);

        $candidateIsActiveAtMoment = (bool) $candidate->visible
            && CarbonImmutable::parse($candidate->start_at)->lessThanOrEqualTo($moment)
            && CarbonImmutable::parse($candidate->end_at)->greaterThanOrEqualTo($moment);

        if (! $candidateIsActiveAtMoment) {
            return [
                'at' => $moment->toIso8601String(),
                'would_win' => false,
                'reason' => 'candidate_not_active_at_given_time',
                'current_winner' => $currentWinner,
                'candidate' => $candidate,
            ];
        }

        $candidates = $existing->push($candidate);
        $projectedWinner = $this->pickWinningPromotionForAudio($audio, $candidates);

        $wouldWin = $projectedWinner === $candidate;

        return [
            'at' => $moment->toIso8601String(),
            'would_win' => $wouldWin,
            'reason' => $wouldWin ? 'candidate_wins' : 'candidate_loses',
            'current_winner' => $currentWinner,
            'projected_winner' => $projectedWinner,
            'candidate' => $candidate,
        ];
    }

}
