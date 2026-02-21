<?php

namespace App\Services;

use App\Models\Audio;
use App\Models\Promotion;
use App\Support\Concerns\PicksWinningPromotion;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class AudioService
{
    use PicksWinningPromotion;

    /**
     * Return paginated audio records.
     */
    public function index(int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        return Audio::query()
            ->select(['id', 'title', 'network_id', 'mformat', 'channel_id'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Return paginated audios with the winning active promotion at a given time.
     */
    public function active(int $page = 1, int $perPage = 15, ?string $at = null): LengthAwarePaginator
    {
        $moment = $at ? CarbonImmutable::parse($at) : CarbonImmutable::now();

        $paginator = Audio::query()
            ->select(['id', 'title', 'network_id', 'mformat', 'channel_id'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $audioItems = collect($paginator->items());
        $audioIds = $audioItems->pluck('id')->all();

        $promotionsByAudio = Promotion::query()
            ->forAudioIds($audioIds)
            ->visible()
            ->activeAt($moment)
            ->orderByDesc('priority')
            ->orderByDesc('version')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('audio_id');

        $paginator->setCollection(
            $audioItems->map(function (Audio $audio) use ($promotionsByAudio): Audio {
                $winner = $this->pickWinningPromotionForAudio(
                    $audio,
                    $promotionsByAudio->get($audio->id, new EloquentCollection())
                );

                $audio->setAttribute('active_promotion', $winner);

                return $audio;
            })
        );

        return $paginator;
    }

    /**
     * Build winner timeline segments for a single audio across a time range.
     */
    public function schedule(Audio $audio, ?string $from = null, ?string $to = null): array
    {
        $promotions = Promotion::query()
            ->where('audio_id', $audio->id)
            ->whereNull('deleted_at')
            ->orderBy('start_at')
            ->get();

        if ($promotions->isEmpty()) {
            return [
                'audio_id' => $audio->id,
                'from' => $from,
                'to' => $to,
                'segments' => [],
            ];
        }

        $defaultFrom = $promotions->min('start_at');
        $defaultTo = $promotions->max('end_at');

        $windowFrom = $from ? CarbonImmutable::parse($from) : CarbonImmutable::parse($defaultFrom);
        $windowTo = $to ? CarbonImmutable::parse($to) : CarbonImmutable::parse($defaultTo);

        if ($windowFrom->greaterThanOrEqualTo($windowTo)) {
            return [
                'audio_id' => $audio->id,
                'from' => $windowFrom->toIso8601String(),
                'to' => $windowTo->toIso8601String(),
                'segments' => [],
            ];
        }

        $boundaries = collect([$windowFrom, $windowTo]);

        foreach ($promotions as $promotion) {
            $startAt = CarbonImmutable::parse($promotion->start_at);
            $endAt = CarbonImmutable::parse($promotion->end_at);

            if ($startAt->betweenIncluded($windowFrom, $windowTo)) {
                $boundaries->push($startAt);
            }

            if ($endAt->betweenIncluded($windowFrom, $windowTo)) {
                $boundaries->push($endAt);
            }
        }

        $timeline = $boundaries
            ->unique(fn (CarbonImmutable $point): string => $point->toIso8601String())
            ->sort()
            ->values();

        $segments = [];
        for ($i = 0; $i < $timeline->count() - 1; $i++) {
            /** @var CarbonImmutable $start */
            $start = $timeline[$i];
            /** @var CarbonImmutable $end */
            $end = $timeline[$i + 1];

            if (! $end->greaterThan($start)) {
                continue;
            }

            $midpoint = $start->addSeconds((int) floor($start->diffInSeconds($end) / 2));
            $activeCandidates = $promotions
                ->filter(fn (Promotion $promotion): bool => (bool) $promotion->visible)
                ->filter(fn (Promotion $promotion): bool =>
                    CarbonImmutable::parse($promotion->start_at)->lessThanOrEqualTo($midpoint)
                    && CarbonImmutable::parse($promotion->end_at)->greaterThanOrEqualTo($midpoint)
                )
                ->values();

            $winner = $this->pickWinningPromotionForAudio($audio, $activeCandidates);

            $segments[] = [
                'from' => $start->toIso8601String(),
                'to' => $end->toIso8601String(),
                'winner_promotion' => $winner,
            ];
        }

        return [
            'audio_id' => $audio->id,
            'from' => $windowFrom->toIso8601String(),
            'to' => $windowTo->toIso8601String(),
            'segments' => $segments,
        ];
    }
}
