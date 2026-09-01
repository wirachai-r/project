<?php

namespace Tests\Feature;

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

        $this->actingAs($user)
            ->getJson("/api/daily-health-records?from={$recordedOn}&to={$recordedOn}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
