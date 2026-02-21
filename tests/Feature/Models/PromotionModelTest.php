<?php

namespace Tests\Feature;

use App\Models\Audio;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PromotionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotion_belongs_to_audio(): void
    {
        $audio = Audio::factory()->create();
        $promotion = Promotion::factory()->create([
            'audio_id' => $audio->id,
        ]);

        $this->assertInstanceOf(Audio::class, $promotion->audio);
        $this->assertSame($audio->id, $promotion->audio->id);
    }

    public function test_promotion_factory_defaults_to_valid_visibility_and_time_range(): void
    {
        $promotion = Promotion::factory()->create();

        $this->assertTrue($promotion->visible);
        $this->assertTrue($promotion->start_at->lessThanOrEqualTo(Carbon::now()));
        $this->assertTrue($promotion->end_at->greaterThanOrEqualTo(Carbon::now()));
    }

    public function test_promotion_uses_soft_delete(): void
    {
        $promotion = Promotion::factory()->create();

        $promotion->delete();

        $this->assertSoftDeleted('promotions', ['id' => $promotion->id]);
    }
}
