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
            ->assertJsonPath('results.0.diseases.0.match_percent', 100)
            ->assertJsonPath('results.0.diseases.0.supporting_symptom_count', 1)
            ->assertJsonPath('results.0.diseases.0.evaluated_symptom_count', 1);
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

    public function test_condition_is_hidden_until_its_minimum_supporting_symptom_count_is_met(): void
    {
        $this->fixture();
        DB::table('diseases')->where('disease_id', 'DIS0000001')->update([
            'minimum_supporting_symptoms' => 2,
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
            ->assertJsonCount(0, 'results');
    }

    public function test_adaptive_result_returns_all_supported_conditions(): void
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
            ->assertJsonCount(4, 'results');
    }

    public function test_ranking_uses_answer_evidence_and_gives_specific_symptoms_more_weight(): void
    {
        $this->fixture();
        DB::table('main_symptoms')->insert([
            'symptom_id' => 'SYM0000003',
            'symptom_name' => 'Specific symptom',
            'symptom_category_id' => 'SC0001',
            'status' => '1',
        ]);
        DB::table('disease_symptoms')->insert([
            ['disease_id' => 'DIS0000002', 'symptom_id' => 'SYM0000002'],
            ['disease_id' => 'DIS0000002', 'symptom_id' => 'SYM0000003'],
        ]);

        $questionIds = [];
        foreach (['SYM0000002', 'SYM0000003'] as $index => $symptomId) {
            $questionId = DB::table('adaptive_questions')->insertGetId([
                'question_symptom_id' => $symptomId,
                'question_text' => "Question {$symptomId}",
                'answer_type' => 'yes_no_unsure',
                'status' => 'approved',
                'evidence_source' => 'Reviewed ranking fixture',
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('adaptive_question_rules')->insert([
                'initial_symptom_id' => 'SYM0000001',
                'adaptive_question_id' => $questionId,
                'question_stage' => 'associated',
                'priority' => $index + 1,
                'is_required' => true,
                'status' => '1',
                'evidence_source' => 'Reviewed ranking route',
                'evidence_status' => 'reviewed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $questionIds[] = $questionId;
        }

        $start = $this->postJson('/api/adaptive-assessments/start', [
            'symptom_id' => 'SYM0000001',
        ])->assertOk()->assertJsonPath('question.question_id', $questionIds[0]);

        $firstAnswer = $this->withHeader('X-Session-Token', $start->json('session_token'))
            ->postJson('/api/adaptive-assessments/'.$start->json('assessment_id').'/answer', [
                'question_id' => $questionIds[0],
                'answer' => 'yes',
            ])->assertOk()->assertJsonPath('question.question_id', $questionIds[1]);

        $this->withHeader('X-Session-Token', $start->json('session_token'))
            ->postJson('/api/adaptive-assessments/'.$start->json('assessment_id').'/answer', [
                'question_id' => $firstAnswer->json('question.question_id'),
                'answer' => 'yes',
            ])->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('results.0.disease_id', 'DIS0000002')
            ->assertJsonPath('results.0.supporting_symptom_count', 2)
            ->assertJsonPath('results.1.disease_id', 'DIS0000001')
            ->assertJsonPath('results.1.supporting_symptom_count', 1)
            ->assertJsonPath('results.1.match_percent', 42);
    }

    public function test_approved_question_bank_controls_the_question_and_accepts_standard_answer(): void
    {
        $this->fixture();
        $questionId = DB::table('adaptive_questions')->insertGetId([
            'question_symptom_id' => 'SYM0000002',
            'question_text' => 'มีอาการร่วมด้วยหรือไม่?',
            'answer_type' => 'yes_no_unsure',
            'status' => 'approved',
            'evidence_source' => 'Reviewed test fixture',
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('adaptive_question_rules')->insert([
            'initial_symptom_id' => 'SYM0000001',
            'adaptive_question_id' => $questionId,
            'question_stage' => 'local',
            'priority' => 1,
            'is_required' => true,
            'status' => '1',
            'evidence_source' => 'Reviewed test route',
            'evidence_status' => 'reviewed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $start = $this->postJson('/api/adaptive-assessments/start', [
            'symptom_id' => 'SYM0000001',
        ])->assertOk()
            ->assertJsonPath('question.question_id', $questionId)
            ->assertJsonPath('question.answer_type', 'yes_no_unsure');

        $this->withHeader('X-Session-Token', $start->json('session_token'))
            ->postJson('/api/adaptive-assessments/'.$start->json('assessment_id').'/answer', [
                'question_id' => $questionId,
                'answer' => 'yes',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('results.0.disease_id', 'DIS0000001');

        $this->assertDatabaseHas('adaptive_assessment_answers', [
            'adaptive_assessment_id' => $start->json('assessment_id'),
            'adaptive_question_id' => $questionId,
            'symptom_id' => 'SYM0000002',
            'answer' => 'yes',
        ]);
    }

    public function test_draft_scope_prevents_unrelated_legacy_fallback_question(): void
    {
        $this->fixture();
        DB::table('main_symptoms')->insert([
            'symptom_id' => 'SYM0000003',
            'symptom_name' => 'Unrelated symptom',
            'symptom_category_id' => 'SC0001',
            'status' => '1',
        ]);
        DB::table('disease_symptoms')->insert([
            'disease_id' => 'DIS0000002',
            'symptom_id' => 'SYM0000003',
        ]);
        $questionId = DB::table('adaptive_questions')->insertGetId([
            'question_symptom_id' => 'SYM0000002',
            'question_text' => 'Draft wording is not published',
            'answer_type' => 'yes_no_unsure',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('adaptive_question_rules')->insert([
            'initial_symptom_id' => 'SYM0000001',
            'adaptive_question_id' => $questionId,
            'question_stage' => 'associated',
            'priority' => 1,
            'is_required' => false,
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/adaptive-assessments/start', ['symptom_id' => 'SYM0000001'])
            ->assertOk()
            ->assertJsonPath('status', 'question')
            ->assertJsonPath('question.symptom_id', 'SYM0000002');
    }

    public function test_answer_must_match_the_current_configured_question(): void
    {
        $this->fixture();
        $firstQuestionId = $this->createApprovedQuestion('คำถามลำดับแรก', 1);
        $secondQuestionId = $this->createApprovedQuestion('คำถามลำดับถัดไป', 2);

        $start = $this->postJson('/api/adaptive-assessments/start', [
            'symptom_id' => 'SYM0000001',
        ])->assertOk()->assertJsonPath('question.question_id', $firstQuestionId);

        $this->withHeader('X-Session-Token', $start->json('session_token'))
            ->postJson('/api/adaptive-assessments/'.$start->json('assessment_id').'/answer', [
                'question_id' => $secondQuestionId,
                'answer' => 'yes',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'คำถามนี้ไม่ใช่คำถามลำดับปัจจุบัน กรุณาโหลดคำถามล่าสุดแล้วลองอีกครั้ง');

        $this->assertDatabaseMissing('adaptive_assessment_answers', [
            'adaptive_assessment_id' => $start->json('assessment_id'),
        ]);
    }

    public function test_configured_question_does_not_repeat_the_initial_symptom(): void
    {
        $this->fixture();
        $duplicateQuestionId = DB::table('adaptive_questions')->insertGetId([
            'question_symptom_id' => 'SYM0000001',
            'question_text' => 'มีอาการหลักร่วมด้วยหรือไม่?',
            'answer_type' => 'yes_no_unsure',
            'status' => 'approved',
            'evidence_source' => 'Reviewed duplicate test fixture',
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $usefulQuestionId = DB::table('adaptive_questions')->insertGetId([
            'question_symptom_id' => 'SYM0000002',
            'question_text' => 'มีอาการร่วมด้วยหรือไม่?',
            'answer_type' => 'yes_no_unsure',
            'status' => 'approved',
            'evidence_source' => 'Reviewed useful test fixture',
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('adaptive_question_rules')->insert([
            [
                'initial_symptom_id' => 'SYM0000001',
                'adaptive_question_id' => $duplicateQuestionId,
                'question_stage' => 'local',
                'priority' => 1,
                'is_required' => true,
                'status' => '1',
                'evidence_source' => 'Reviewed duplicate route',
                'evidence_status' => 'reviewed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'initial_symptom_id' => 'SYM0000001',
                'adaptive_question_id' => $usefulQuestionId,
                'question_stage' => 'associated',
                'priority' => 2,
                'is_required' => false,
                'status' => '1',
                'evidence_source' => 'Reviewed useful route',
                'evidence_status' => 'reviewed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->postJson('/api/adaptive-assessments/start', [
            'symptom_id' => 'SYM0000001',
        ])->assertOk()
            ->assertJsonPath('status', 'question')
            ->assertJsonPath('question.question_id', $usefulQuestionId);
    }

    public function test_optional_question_prefers_the_best_candidate_split(): void
    {
        $this->fixture();
        DB::table('main_symptoms')->insert([
            ['symptom_id' => 'SYM0000003', 'symptom_name' => 'แบ่งได้หนึ่งภาวะ', 'symptom_category_id' => 'SC0001', 'status' => '1'],
            ['symptom_id' => 'SYM0000004', 'symptom_name' => 'แบ่งได้ครึ่งหนึ่ง', 'symptom_category_id' => 'SC0001', 'status' => '1'],
        ]);
        DB::table('diseases')->insert([
            ['disease_id' => 'DIS0000003', 'disease_name' => 'โรคสาม', 'disease_category_id' => 'DC0001', 'status' => '1'],
            ['disease_id' => 'DIS0000004', 'disease_name' => 'โรคสี่', 'disease_category_id' => 'DC0001', 'status' => '1'],
        ]);
        DB::table('disease_symptoms')->insert([
            ['disease_id' => 'DIS0000003', 'symptom_id' => 'SYM0000001'],
            ['disease_id' => 'DIS0000004', 'symptom_id' => 'SYM0000001'],
            ['disease_id' => 'DIS0000001', 'symptom_id' => 'SYM0000003'],
            ['disease_id' => 'DIS0000001', 'symptom_id' => 'SYM0000004'],
            ['disease_id' => 'DIS0000002', 'symptom_id' => 'SYM0000004'],
        ]);

        $questionIds = [];
        foreach ([
            ['SYM0000003', 'คำถามที่แบ่งได้หนึ่งภาวะ', 1],
            ['SYM0000004', 'คำถามที่แบ่งได้ครึ่งหนึ่ง', 2],
        ] as [$symptomId, $text, $priority]) {
            $questionId = DB::table('adaptive_questions')->insertGetId([
                'question_symptom_id' => $symptomId,
                'question_text' => $text,
                'answer_type' => 'yes_no_unsure',
                'status' => 'approved',
                'evidence_source' => 'Reviewed split test fixture',
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('adaptive_question_rules')->insert([
                'initial_symptom_id' => 'SYM0000001',
                'adaptive_question_id' => $questionId,
                'question_stage' => 'associated',
                'priority' => $priority,
                'is_required' => false,
                'status' => '1',
                'evidence_source' => 'Reviewed split test route',
                'evidence_status' => 'reviewed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $questionIds[$symptomId] = $questionId;
        }

        $this->postJson('/api/adaptive-assessments/start', [
            'symptom_id' => 'SYM0000001',
        ])->assertOk()
            ->assertJsonPath('question.question_id', $questionIds['SYM0000004']);
    }

    private function createApprovedQuestion(string $text, int $priority): int
    {
        $questionId = DB::table('adaptive_questions')->insertGetId([
            'question_symptom_id' => 'SYM0000002',
            'question_text' => $text,
            'answer_type' => 'yes_no_unsure',
            'status' => 'approved',
            'evidence_source' => 'Reviewed test fixture',
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('adaptive_question_rules')->insert([
            'initial_symptom_id' => 'SYM0000001',
            'adaptive_question_id' => $questionId,
            'question_stage' => 'local',
            'priority' => $priority,
            'is_required' => true,
            'status' => '1',
            'evidence_source' => 'Reviewed test route',
            'evidence_status' => 'reviewed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $questionId;
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
