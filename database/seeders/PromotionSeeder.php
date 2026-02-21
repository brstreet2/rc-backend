<?php

namespace Database\Seeders;

use App\Models\Audio;
use App\Models\Promotion;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $audios = Audio::query()->get();
        $now = CarbonImmutable::now()->startOfHour();

        foreach ($audios as $audio) {
            // Overlapping exact-scope promotions to force deterministic winner selection.
            Promotion::factory()->create([
                'audio_id' => $audio->id,
                'network_id' => $audio->network_id,
                'mformat' => $audio->mformat,
                'channel_id' => $audio->channel_id,
                'priority' => 5,
                'version' => 1,
                'start_at' => $now->subHours(2),
                'end_at' => $now->addHours(2),
                'created_at' => $now->subMinutes(30),
            ]);

            Promotion::factory()->create([
                'audio_id' => $audio->id,
                'network_id' => $audio->network_id,
                'mformat' => $audio->mformat,
                'channel_id' => $audio->channel_id,
                'priority' => 5,
                'version' => 2,
                'start_at' => $now->subHours(2),
                'end_at' => $now->addHours(2),
                'created_at' => $now->subMinutes(20),
            ]);

            Promotion::factory()->create([
                'audio_id' => $audio->id,
                'network_id' => $audio->network_id,
                'mformat' => $audio->mformat,
                'channel_id' => $audio->channel_id,
                'priority' => 5,
                'version' => 2,
                'start_at' => $now->subHours(2),
                'end_at' => $now->addHours(2),
                'created_at' => $now->subMinutes(10),
            ]);

            // Less-specific scope with higher priority should still lose to exact scope.
            Promotion::factory()->create([
                'audio_id' => $audio->id,
                'network_id' => null,
                'mformat' => null,
                'channel_id' => null,
                'priority' => 999,
                'version' => 1,
                'start_at' => $now->subHours(2),
                'end_at' => $now->addHours(2),
                'created_at' => $now->subMinutes(5),
            ]);

            // Future exact-scope winner to produce non-null schedule segments after a switch point.
            Promotion::factory()->create([
                'audio_id' => $audio->id,
                'network_id' => $audio->network_id,
                'mformat' => $audio->mformat,
                'channel_id' => $audio->channel_id,
                'priority' => 8,
                'version' => 1,
                'start_at' => $now->addHours(2),
                'end_at' => $now->addHours(6),
                'created_at' => $now,
            ]);

            // Invalid records for rule coverage.
            Promotion::factory()->hidden()->create([
                'audio_id' => $audio->id,
                'network_id' => $audio->network_id,
                'mformat' => $audio->mformat,
                'channel_id' => $audio->channel_id,
                'priority' => 1,
                'start_at' => $now->subHours(2),
                'end_at' => $now->addHours(2),
            ]);

            Promotion::factory()->expired()->create([
                'audio_id' => $audio->id,
                'network_id' => $audio->network_id,
                'mformat' => $audio->mformat,
                'channel_id' => $audio->channel_id,
                'priority' => 4,
                'start_at' => $now->subDays(2),
                'end_at' => $now->subDay(),
            ]);
        }
    }
}
