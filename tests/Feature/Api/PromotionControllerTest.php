<?php

namespace Tests\Feature\Api;

use App\Models\Audio;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_promotion_and_returns_data_with_message(): void
    {
        $audio = Audio::factory()->create();

        $response = $this->postJson('/api/promotions', [
            'audio_id' => $audio->id,
            'network_id' => $audio->network_id,
            'mformat' => $audio->mformat,
            'channel_id' => $audio->channel_id,
            'priority' => 10,
            'version' => 1,
            'visible' => true,
            'start_at' => now()->subHour()->toDateTimeString(),
            'end_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', __('messages.create_success', ['name' => 'Promotion']))
            ->assertJsonPath('data.audio_id', $audio->id)
            ->assertJsonPath('data.priority', 10);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->postJson('/api/promotions', [
            'priority' => 1,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['audio_id', 'version', 'visible', 'start_at', 'end_at']);
    }

    public function test_store_validates_end_at_after_start_at(): void
    {
        $audio = Audio::factory()->create();

        $response = $this->postJson('/api/promotions', [
            'audio_id' => $audio->id,
            'priority' => 1,
            'version' => 1,
            'visible' => true,
            'start_at' => '2026-02-21 11:00:00',
            'end_at' => '2026-02-21 10:00:00',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_at']);
    }

    public function test_update_changes_fields_and_returns_message(): void
    {
        $promotion = Promotion::factory()->create([
            'priority' => 1,
            'version' => 1,
        ]);

        $response = $this->putJson('/api/promotions/'.$promotion->id, [
            'priority' => 8,
            'version' => 3,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', __('messages.update_success', ['name' => 'Promotion']))
            ->assertJsonPath('data.priority', 8)
            ->assertJsonPath('data.version', 3);

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'priority' => 8,
            'version' => 3,
        ]);
    }

    public function test_update_validates_end_at_after_start_at(): void
    {
        $promotion = Promotion::factory()->create();

        $response = $this->putJson('/api/promotions/'.$promotion->id, [
            'start_at' => '2026-02-21 11:00:00',
            'end_at' => '2026-02-21 10:00:00',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_at']);
    }

    public function test_delete_soft_deletes_and_returns_message_only(): void
    {
        $promotion = Promotion::factory()->create();

        $response = $this->deleteJson('/api/promotions/'.$promotion->id);

        $response->assertOk()
            ->assertJsonPath('message', __('messages.delete_success', ['name' => 'Promotion']))
            ->assertJsonMissingPath('data');

        $this->assertSoftDeleted('promotions', ['id' => $promotion->id]);
    }

    public function test_preview_returns_would_win_true_for_better_candidate(): void
    {
        $audio = Audio::factory()->create([
            'network_id' => 2,
            'mformat' => 'NEWS',
            'channel_id' => 11,
        ]);

        Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 2,
            'mformat' => 'NEWS',
            'channel_id' => 11,
            'priority' => 2,
            'version' => 1,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/promotions/preview', [
            'audio_id' => $audio->id,
            'network_id' => 2,
            'mformat' => 'NEWS',
            'channel_id' => 11,
            'priority' => 10,
            'version' => 1,
            'visible' => true,
            'start_at' => now()->subHour()->toDateTimeString(),
            'end_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertOk()
            ->assertJsonPath('message', __('messages.preview_success', ['name' => 'Promotion']))
            ->assertJsonPath('data.would_win', true)
            ->assertJsonPath('data.reason', 'candidate_wins');
    }

    public function test_preview_returns_candidate_not_active_reason_when_time_window_invalid(): void
    {
        $audio = Audio::factory()->create();

        $response = $this->postJson('/api/promotions/preview', [
            'audio_id' => $audio->id,
            'network_id' => $audio->network_id,
            'mformat' => $audio->mformat,
            'channel_id' => $audio->channel_id,
            'priority' => 10,
            'version' => 1,
            'visible' => true,
            'start_at' => now()->addHour()->toDateTimeString(),
            'end_at' => now()->addHours(2)->toDateTimeString(),
            'at' => now()->toDateTimeString(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.would_win', false)
            ->assertJsonPath('data.reason', 'candidate_not_active_at_given_time');
    }

    public function test_preview_returns_would_win_false_when_candidate_loses(): void
    {
        $audio = Audio::factory()->create([
            'network_id' => 9,
            'mformat' => 'ROCK',
            'channel_id' => 90,
        ]);

        Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 9,
            'mformat' => 'ROCK',
            'channel_id' => 90,
            'priority' => 10,
            'version' => 1,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/promotions/preview', [
            'audio_id' => $audio->id,
            'network_id' => 9,
            'mformat' => 'ROCK',
            'channel_id' => 90,
            'priority' => 1,
            'version' => 1,
            'visible' => true,
            'start_at' => now()->subHour()->toDateTimeString(),
            'end_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.would_win', false)
            ->assertJsonPath('data.reason', 'candidate_loses');
    }

    public function test_preview_scope_specificity_beats_global_priority(): void
    {
        $audio = Audio::factory()->create([
            'network_id' => 8,
            'mformat' => 'POP',
            'channel_id' => 18,
        ]);

        Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => null,
            'mformat' => null,
            'channel_id' => null,
            'priority' => 999,
            'version' => 1,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/promotions/preview', [
            'audio_id' => $audio->id,
            'network_id' => 8,
            'mformat' => 'POP',
            'channel_id' => 18,
            'priority' => 1,
            'version' => 1,
            'visible' => true,
            'start_at' => now()->subHour()->toDateTimeString(),
            'end_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.would_win', true)
            ->assertJsonPath('data.reason', 'candidate_wins');
    }

    public function test_update_missing_promotion_returns_not_found(): void
    {
        $response = $this->putJson('/api/promotions/999999', [
            'priority' => 5,
        ]);

        $response->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_preview_validation_requires_core_fields(): void
    {
        $response = $this->postJson('/api/promotions/preview', [
            'priority' => 1,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['audio_id', 'version', 'visible', 'start_at', 'end_at']);
    }
}
