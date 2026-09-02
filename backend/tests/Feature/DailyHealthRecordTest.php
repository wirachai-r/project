<?php

namespace Tests\Feature;

use App\Models\HealthEpisode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyHealthRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_multiple_health_records_on_the_same_day(): void
    {
        $recordedOn = now()->toDateString();
        $user = User::create([
            'user_id' => '000000001',
            'first_name' => 'Health',
            'last_name' => 'User',
            'email' => 'health@example.com',
            'password' => 'password',
        ]);

        $first = $this->actingAs($user)->postJson('/api/daily-health-records', [
            'recorded_on' => $recordedOn,
            'status' => 'well',
            'note' => 'Morning',
        ]);
        $second = $this->actingAs($user)->postJson('/api/daily-health-records', [
            'recorded_on' => $recordedOn,
            'status' => 'unwell',
            'note' => 'Evening',
        ]);

        $first->assertCreated();
        $second->assertCreated();
        $this->assertNotSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('daily_health_records', 2);

        $this->actingAs($user)->patchJson('/api/daily-health-records/'.$first->json('data.id'), [
            'recorded_on' => $recordedOn,
            'status' => 'normal',
            'note' => 'Updated morning record',
        ])->assertOk()->assertJsonPath('data.status', 'normal');
        $this->assertDatabaseCount('daily_health_records', 2);
        $this->assertDatabaseHas('daily_health_records', [
            'id' => $first->json('data.id'),
            'status' => 'normal',
            'note' => 'Updated morning record',
        ]);

        $this->actingAs($user)
            ->getJson("/api/daily-health-records?from={$recordedOn}&to={$recordedOn}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_check_in_can_link_multiple_owned_health_episodes(): void
    {
        $user = User::create([
            'user_id' => '000000001', 'first_name' => 'Health', 'last_name' => 'User',
            'email' => 'linked-health@example.com', 'password' => 'password',
        ]);
        $episodes = collect([1, 2])->map(fn () => HealthEpisode::create([
            'user_id' => $user->user_id, 'status' => 'A', 'started_at' => now(),
        ]));

        $response = $this->actingAs($user)->postJson('/api/daily-health-records', [
            'recorded_on' => now()->toDateString(),
            'status' => 'well',
            'health_episode_ids' => $episodes->pluck('id')->all(),
        ])->assertCreated()->assertJsonCount(2, 'data.health_episodes');

        $this->assertDatabaseCount('daily_health_record_health_episode', 2);
        $this->assertNotNull($response->json('data.recorded_at'));
    }
}
