<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        $startAt = Carbon::now()->subHour();
        $endAt = Carbon::now()->addHour();

        return [
            'audio_id' => Audio::factory(),
            'network_id' => fake()->numberBetween(1, 10),
            'mformat' => fake()->randomElement(['POP', 'NEWS', 'ROCK', 'JAZZ']),
            'channel_id' => fake()->numberBetween(1, 100),
            'priority' => fake()->numberBetween(1, 10),
            'version' => fake()->numberBetween(1, 5),
            'visible' => true,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'created_at' => Carbon::now(),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['visible' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'start_at' => Carbon::now()->subDays(2),
            'end_at' => Carbon::now()->subDay(),
        ]);
    }
}
