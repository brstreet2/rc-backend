<?php

namespace Tests\Feature\Api;

use App\Models\Audio;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AudioControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_audio_list(): void
    {
        Audio::factory()->count(5)->create();

        $response = $this->getJson('/api/audios?page=2&per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_active_returns_message_and_pagination_meta(): void
    {
        Audio::factory()->count(3)->create();

        $response = $this->getJson('/api/audios/active?page=1&per_page=2');

        $response->assertOk()
            ->assertJsonPath('message', __('messages.fetch_success', ['name' => 'Audio']))
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_active_returns_null_when_only_hidden_expired_or_deleted_promotions_exist(): void
    {
        $audio = Audio::factory()->create();

        Promotion::factory()->hidden()->create([
            'audio_id' => $audio->id,
            'network_id' => $audio->network_id,
            'mformat' => $audio->mformat,
            'channel_id' => $audio->channel_id,
        ]);

        Promotion::factory()->expired()->create([
            'audio_id' => $audio->id,
            'network_id' => $audio->network_id,
            'mformat' => $audio->mformat,
            'channel_id' => $audio->channel_id,
        ]);

        $deleted = Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => $audio->network_id,
            'mformat' => $audio->mformat,
            'channel_id' => $audio->channel_id,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);
        $deleted->delete();

        $response = $this->getJson('/api/audios/active');
        $item = collect($response->json('data'))->firstWhere('id', $audio->id);

        $this->assertNotNull($item);
        $this->assertNull($item['promo']);
    }

    public function test_scope_hierarchy_exact_scope_beats_less_specific_scopes(): void
    {
        $audio = Audio::factory()->create([
            'network_id' => 10,
            'mformat' => 'POP',
            'channel_id' => 50,
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

        Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 10,
            'mformat' => null,
            'channel_id' => null,
            'priority' => 999,
            'version' => 1,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 10,
            'mformat' => 'POP',
            'channel_id' => null,
            'priority' => 999,
            'version' => 1,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $exact = Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 10,
            'mformat' => 'POP',
            'channel_id' => 50,
            'priority' => 1,
            'version' => 1,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/audios/active');
        $item = collect($response->json('data'))->firstWhere('id', $audio->id);

        $this->assertSame($exact->id, $item['promo']['id']);
    }

    public function test_scope_hierarchy_network_mformat_null_channel_beats_network_only(): void
    {
        $audio = Audio::factory()->create([
            'network_id' => 15,
            'mformat' => 'NEWS',
            'channel_id' => 7,
        ]);

        Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 15,
            'mformat' => null,
            'channel_id' => null,
            'priority' => 100,
            'version' => 1,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $networkMformat = Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 15,
            'mformat' => 'NEWS',
            'channel_id' => null,
            'priority' => 1,
            'version' => 1,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/audios/active');
        $item = collect($response->json('data'))->firstWhere('id', $audio->id);

        $this->assertSame($networkMformat->id, $item['promo']['id']);
    }

    public function test_conflict_resolution_prefers_higher_priority_within_same_scope(): void
    {
        $audio = Audio::factory()->create([
            'network_id' => 1,
            'mformat' => 'POP',
            'channel_id' => 1,
        ]);

        Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 1,
            'mformat' => 'POP',
            'channel_id' => 1,
            'priority' => 3,
            'version' => 10,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $winner = Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 1,
            'mformat' => 'POP',
            'channel_id' => 1,
            'priority' => 4,
            'version' => 1,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/audios/active');
        $item = collect($response->json('data'))->firstWhere('id', $audio->id);

        $this->assertSame($winner->id, $item['promo']['id']);
    }

    public function test_conflict_resolution_prefers_higher_version_when_priority_is_equal(): void
    {
        $audio = Audio::factory()->create([
            'network_id' => 2,
            'mformat' => 'NEWS',
            'channel_id' => 2,
        ]);

        Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 2,
            'mformat' => 'NEWS',
            'channel_id' => 2,
            'priority' => 5,
            'version' => 1,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $winner = Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 2,
            'mformat' => 'NEWS',
            'channel_id' => 2,
            'priority' => 5,
            'version' => 2,
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/audios/active');
        $item = collect($response->json('data'))->firstWhere('id', $audio->id);

        $this->assertSame($winner->id, $item['promo']['id']);
    }

    public function test_conflict_resolution_prefers_newer_created_at_when_priority_and_version_are_equal(): void
    {
        Carbon::setTestNow('2026-02-21 10:00:00');

        $audio = Audio::factory()->create([
            'network_id' => 3,
            'mformat' => 'ROCK',
            'channel_id' => 3,
        ]);

        Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 3,
            'mformat' => 'ROCK',
            'channel_id' => 3,
            'priority' => 7,
            'version' => 2,
            'created_at' => Carbon::parse('2026-02-21 09:00:00'),
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $winner = Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 3,
            'mformat' => 'ROCK',
            'channel_id' => 3,
            'priority' => 7,
            'version' => 2,
            'created_at' => Carbon::parse('2026-02-21 09:30:00'),
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/audios/active');
        $item = collect($response->json('data'))->firstWhere('id', $audio->id);

        $this->assertSame($winner->id, $item['promo']['id']);

        Carbon::setTestNow();
    }

    public function test_active_supports_simulation_at_parameter(): void
    {
        $audio = Audio::factory()->create([
            'network_id' => 4,
            'mformat' => 'JAZZ',
            'channel_id' => 4,
        ]);

        $first = Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 4,
            'mformat' => 'JAZZ',
            'channel_id' => 4,
            'priority' => 1,
            'start_at' => Carbon::parse('2026-02-21 09:00:00'),
            'end_at' => Carbon::parse('2026-02-21 10:00:00'),
        ]);

        $second = Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 4,
            'mformat' => 'JAZZ',
            'channel_id' => 4,
            'priority' => 2,
            'start_at' => Carbon::parse('2026-02-21 10:00:01'),
            'end_at' => Carbon::parse('2026-02-21 11:00:00'),
        ]);

        $responseA = $this->getJson('/api/audios/active?at=2026-02-21T09:30:00Z');
        $responseB = $this->getJson('/api/audios/active?at=2026-02-21T10:30:00Z');

        $itemA = collect($responseA->json('data'))->firstWhere('id', $audio->id);
        $itemB = collect($responseB->json('data'))->firstWhere('id', $audio->id);

        $this->assertSame($first->id, $itemA['promo']['id']);
        $this->assertSame($second->id, $itemB['promo']['id']);
    }

    public function test_schedule_returns_segments_with_winners_and_message(): void
    {
        $audio = Audio::factory()->create([
            'network_id' => 5,
            'mformat' => 'POP',
            'channel_id' => 5,
        ]);

        $promoA = Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 5,
            'mformat' => 'POP',
            'channel_id' => 5,
            'priority' => 4,
            'start_at' => Carbon::parse('2026-02-21 10:00:00'),
            'end_at' => Carbon::parse('2026-02-21 10:30:00'),
        ]);

        $promoB = Promotion::factory()->create([
            'audio_id' => $audio->id,
            'network_id' => 5,
            'mformat' => 'POP',
            'channel_id' => 5,
            'priority' => 8,
            'start_at' => Carbon::parse('2026-02-21 10:30:00'),
            'end_at' => Carbon::parse('2026-02-21 11:00:00'),
        ]);

        $response = $this->getJson('/api/audios/'.$audio->id.'/schedule?from=2026-02-21T10:00:00Z&to=2026-02-21T11:00:00Z');

        $response->assertOk()
            ->assertJsonPath('message', __('messages.fetch_success', ['name' => 'Audio']))
            ->assertJsonPath('data.audio_id', $audio->id);

        $segments = $response->json('data.segments');
        $this->assertCount(2, $segments);
        $this->assertSame($promoA->id, $segments[0]['winner_promotion']['id']);
        $this->assertSame($promoB->id, $segments[1]['winner_promotion']['id']);
    }

    public function test_schedule_validation_rejects_invalid_range(): void
    {
        $audio = Audio::factory()->create();

        $response = $this->getJson('/api/audios/'.$audio->id.'/schedule?from=2026-02-21T11:00:00Z&to=2026-02-21T10:00:00Z');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }
}
