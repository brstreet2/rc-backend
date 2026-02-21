<?php

namespace Tests\Feature;

use App\Models\Audio;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudioModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_audio_has_many_promotions(): void
    {
        $audio = Audio::factory()->create();
        $promotions = Promotion::factory()->count(2)->create([
            'audio_id' => $audio->id,
        ]);

        $this->assertCount(2, $audio->promotions);
        $this->assertEqualsCanonicalizing(
            $promotions->pluck('id')->all(),
            $audio->promotions->pluck('id')->all()
        );
    }

    public function test_audio_uses_soft_delete(): void
    {
        $audio = Audio::factory()->create();

        $audio->delete();

        $this->assertSoftDeleted('audio', ['id' => $audio->id]);
    }
}
