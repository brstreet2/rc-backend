<?php

namespace Database\Seeders;

use App\Models\Audio;
use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $audios = Audio::query()->get();

        foreach ($audios as $audio) {
            Promotion::factory()->create([
                'audio_id' => $audio->id,
                'network_id' => $audio->network_id,
                'mformat' => $audio->mformat,
                'channel_id' => $audio->channel_id,
                'priority' => 10,
                'version' => 1,
            ]);

            Promotion::factory()->hidden()->create([
                'audio_id' => $audio->id,
                'network_id' => $audio->network_id,
                'mformat' => $audio->mformat,
                'channel_id' => $audio->channel_id,
                'priority' => 1,
            ]);

            Promotion::factory()->expired()->create([
                'audio_id' => $audio->id,
                'network_id' => $audio->network_id,
                'mformat' => $audio->mformat,
                'channel_id' => $audio->channel_id,
                'priority' => 5,
            ]);
        }
    }
}
