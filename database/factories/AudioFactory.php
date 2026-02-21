<?php

namespace Database\Factories;

use App\Models\Audio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Audio>
 */
class AudioFactory extends Factory
{
    protected $model = Audio::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'network_id' => fake()->numberBetween(1, 10),
            'mformat' => fake()->randomElement(['POP', 'NEWS', 'ROCK', 'JAZZ']),
            'channel_id' => fake()->numberBetween(1, 100),
        ];
    }
}
