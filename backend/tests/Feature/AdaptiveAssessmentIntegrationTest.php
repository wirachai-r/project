<?php

namespace Tests\Feature;

use App\Contracts\AiClient;
use App\Models\Assessment;
use App\Models\User;
use App\Services\Ai\FakeAiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdaptiveAssessmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_adaptive_assessment_uses_history_ai_and_tracking_pipeline(): void
    {
        $this->app->bind(AiClient::class, FakeAiClient::class);
        $this->fixture();
        $user = User::create([
            'user_id' => '000000001',
            'first_name' => 'Adaptive',
            'last_name' => 'User',
            'email' => 'adaptive@example.test',
            'password' => 'password',
        ]);

        $start = $this->actingAs($user)->postJson('/api/adaptive-assessments/start', [
            'symptom_id' => 'SYM0000001',
        ])->assertOk()->assertJsonPath('status', 'question');

        $completed = $this->actingAs($user)->postJson(
            '/api/adaptive-assessments/'.$start->json('assessment_id').'/answer',
            ['symptom_id' => 'SYM0000002', 'answer' => 'yes'],
        )->assertOk()->assertJsonPath('status', 'completed');

        $assessmentId = $completed->json('history_assessment_id');
        $this->assertNotNull($assessmentId);
        $this->assertDatabaseHas('assessments', [
            'id' => $assessmentId,
            'user_id' => $user->user_id,
            'assessment_type' => 'adaptive',
            'assessment_status' => 'C',
            'is_saved' => false,
        ]);

        $this->actingAs($user)->getJson("/api/assessments/{$assessmentId}/result")
            ->assertOk()
            ->assertJsonPath('assessment_type', 'adaptive')
            ->assertJsonPath('results.0.diseases.0.disease_id', 'DIS0000001')
            ->assertJsonPath('results.0.diseases.0.match_percent', 100);
        $this->actingAs($user)->getJson("/api/assessments/{$assessmentId}")
            ->assertOk()
            ->assertJsonPath('data.assessment_type', 'adaptive')
            ->assertJsonPath('data.diagram_id', null)
            ->assertJsonPath('data.results.0.diseases.0.disease_id', 'DIS0000001');
        $this->actingAs($user)->postJson("/api/ai/assessments/{$assessmentId}/guidance")
            ->assertOk()
            ->assertJsonStructure(['data' => ['summary', 'assessment_overview', 'next_steps']]);

        $this->actingAs($user)->postJson("/api/assessments/{$assessmentId}/save")
            ->assertOk();
        $this->actingAs($user)->getJson('/api/assessments')
            ->assertOk()
            ->assertJsonPath('data.0.assessment_type', 'adaptive');
        $this->actingAs($user)->postJson("/api/assessments/{$assessmentId}/health-episode")
            ->assertCreated();

        $this->assertTrue(Assessment::findOrFail($assessmentId)->is_saved);
    }

    public function test_adaptive_result_does_not_force_a_condition_without_supporting_yes_answer(): void
    {
        $this->fixture();

        $start = $this->postJson('/api/adaptive-assessments/start', [
            'symptom_id' => 'SYM0000001',
        ])->assertOk()->assertJsonPath('status', 'question');

        $this->withHeader('X-Session-Token', $start->json('session_token'))
            ->postJson(
                '/api/adaptive-assessments/'.$start->json('assessment_id').'/answer',
                ['symptom_id' => 'SYM0000002', 'answer' => 'no'],
            )
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonCount(0, 'results');
    }

    public function test_adaptive_result_returns_at_most_three_supported_conditions(): void
    {
        $this->fixture();
        DB::table('diseases')->insert([
            ['disease_id' => 'DIS0000003', 'disease_name' => 'Condition 3', 'disease_category_id' => 'DC0001', 'status' => '1'],
            ['disease_id' => 'DIS0000004', 'disease_name' => 'Condition 4', 'disease_category_id' => 'DC0001', 'status' => '1'],
            ['disease_id' => 'DIS0000005', 'disease_name' => 'Condition 5', 'disease_category_id' => 'DC0001', 'status' => '1'],
            ['disease_id' => 'DIS0000006', 'disease_name' => 'Condition 6', 'disease_category_id' => 'DC0001', 'status' => '1'],
        ]);
        DB::table('disease_symptoms')->insert([
            ['disease_id' => 'DIS0000003', 'symptom_id' => 'SYM0000001'],
            ['disease_id' => 'DIS0000003', 'symptom_id' => 'SYM0000002'],
            ['disease_id' => 'DIS0000004', 'symptom_id' => 'SYM0000001'],
            ['disease_id' => 'DIS0000004', 'symptom_id' => 'SYM0000002'],
            ['disease_id' => 'DIS0000005', 'symptom_id' => 'SYM0000001'],
            ['disease_id' => 'DIS0000005', 'symptom_id' => 'SYM0000002'],
            ['disease_id' => 'DIS0000006', 'symptom_id' => 'SYM0000001'],
        ]);

        $start = $this->postJson('/api/adaptive-assessments/start', [
            'symptom_id' => 'SYM0000001',
        ])->assertOk()->assertJsonPath('status', 'question');

        $this->withHeader('X-Session-Token', $start->json('session_token'))
            ->postJson(
                '/api/adaptive-assessments/'.$start->json('assessment_id').'/answer',
                ['symptom_id' => 'SYM0000002', 'answer' => 'yes'],
            )
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonCount(3, 'results');
    }

    private function fixture(): void
    {
        DB::table('symptom_categories')->insert([
            'symptom_category_id' => 'SC0001', 'category_name' => 'Test', 'status' => '1',
        ]);
        DB::table('main_symptoms')->insert([
            ['symptom_id' => 'SYM0000001', 'symptom_name' => 'อาการหลัก', 'symptom_category_id' => 'SC0001', 'status' => '1'],
            ['symptom_id' => 'SYM0000002', 'symptom_name' => 'อาการร่วม', 'symptom_category_id' => 'SC0001', 'status' => '1'],
        ]);
        DB::table('disease_categories')->insert([
            'disease_category_id' => 'DC0001', 'category_name' => 'Test', 'status' => '1',
        ]);
        DB::table('diseases')->insert([
            ['disease_id' => 'DIS0000001', 'disease_name' => 'โรคหนึ่ง', 'disease_category_id' => 'DC0001', 'status' => '1'],
            ['disease_id' => 'DIS0000002', 'disease_name' => 'โรคสอง', 'disease_category_id' => 'DC0001', 'status' => '1'],
        ]);
        DB::table('disease_symptoms')->insert([
            ['disease_id' => 'DIS0000001', 'symptom_id' => 'SYM0000001'],
            ['disease_id' => 'DIS0000001', 'symptom_id' => 'SYM0000002'],
            ['disease_id' => 'DIS0000002', 'symptom_id' => 'SYM0000001'],
        ]);
    }
}
