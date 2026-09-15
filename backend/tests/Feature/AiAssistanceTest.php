<?php

namespace Tests\Feature;

use App\Contracts\AiClient;
use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiAssistanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.enabled' => true, 'ai.provider' => 'fake']);
        Cache::flush();
    }

    public function test_guest_can_clarify_a_question_without_recording_an_answer(): void
    {
        $assessment = $this->fixture('P', 'guest-token');

        $response = $this->postJson("/api/ai/assessments/{$assessment->id}/clarify-question", [
            'box_id' => 'BOX0000001',
            'attempt' => 1,
        ], ['X-Session-Token' => 'guest-token'])
            ->assertOk()
            ->assertJsonPath('data.requires_user_confirmation', true)
            ->assertJsonCount(3, 'data.choices');

        $this->assertDatabaseCount('assessment_answers', 0);
        $this->assertDatabaseHas('ai_clarification_sessions', [
            'assessment_id' => $assessment->id,
            'box_id' => 'BOX0000001',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('ai_clarification_questions', [
            'id' => $response->json('data.question_id'),
            'sequence' => 1,
        ]);
        $this->assertDatabaseCount('ai_clarification_choices', 3);
    }

    public function test_clarification_answer_is_stored_separately_and_main_answer_resolves_session(): void
    {
        $assessment = $this->fixture('P', 'guest-token');
        $clarification = $this->postJson("/api/ai/assessments/{$assessment->id}/clarify-question", [
            'box_id' => 'BOX0000001',
        ], ['X-Session-Token' => 'guest-token'])->assertOk();

        $questionId = $clarification->json('data.question_id');
        $helperChoice = collect($clarification->json('data.choices'))->firstWhere('maps_to', 'yes');
        $this->postJson("/api/ai/clarification-questions/{$questionId}/answer", [
            'choice_id' => $helperChoice['id'],
        ], ['X-Session-Token' => 'guest-token'])
            ->assertOk()
            ->assertJsonPath('data.maps_to', 'yes')
            ->assertJsonPath('data.maps_to_choice_id', 'YES0000001');

        $this->assertDatabaseCount('ai_clarification_answers', 1);
        $this->assertDatabaseCount('assessment_answers', 0);

        $this->postJson("/api/assessments/{$assessment->id}/answer", [
            'answers' => [['box_id' => 'BOX0000001', 'choice_id' => 'YES0000001']],
        ], ['X-Session-Token' => 'guest-token'])->assertOk();

        $this->assertDatabaseHas('ai_clarification_sessions', [
            'assessment_id' => $assessment->id,
            'status' => 'resolved',
            'resolved_to' => 'yes',
        ]);
        $this->assertDatabaseHas('assessment_answers', ['choice_id' => 'YES0000001']);
    }

    public function test_unanswered_stored_question_is_returned_without_consuming_another_attempt(): void
    {
        $assessment = $this->fixture('P', 'guest-token');
        $first = $this->postJson("/api/ai/assessments/{$assessment->id}/clarify-question", [
            'box_id' => 'BOX0000001',
        ], ['X-Session-Token' => 'guest-token'])->assertOk();
        $second = $this->postJson("/api/ai/assessments/{$assessment->id}/clarify-question", [
            'box_id' => 'BOX0000001',
        ], ['X-Session-Token' => 'guest-token'])->assertOk();

        $this->assertSame($first->json('data.question_id'), $second->json('data.question_id'));
        $this->assertSame(1, $second->json('data.attempt'));
        $this->assertDatabaseCount('ai_clarification_questions', 1);
    }

    public function test_user_can_change_a_stored_clarification_answer_while_assessment_is_active(): void
    {
        $assessment = $this->fixture('P', 'guest-token');
        $clarification = $this->postJson("/api/ai/assessments/{$assessment->id}/clarify-question", [
            'box_id' => 'BOX0000001',
        ], ['X-Session-Token' => 'guest-token'])->assertOk();
        $questionId = $clarification->json('data.question_id');
        $yes = collect($clarification->json('data.choices'))->firstWhere('maps_to', 'yes');
        $no = collect($clarification->json('data.choices'))->firstWhere('maps_to', 'no');

        $this->postJson("/api/ai/clarification-questions/{$questionId}/answer", [
            'choice_id' => $yes['id'],
        ], ['X-Session-Token' => 'guest-token'])->assertOk();
        DB::table('ai_clarification_sessions')->update(['status' => 'resolved']);

        $this->postJson("/api/ai/clarification-questions/{$questionId}/answer", [
            'choice_id' => $no['id'],
        ], ['X-Session-Token' => 'guest-token'])
            ->assertOk()
            ->assertJsonPath('data.maps_to', 'no');

        $this->assertDatabaseHas('ai_clarification_answers', [
            'question_id' => $questionId,
            'choice_id' => $no['id'],
        ]);
    }

    public function test_third_unresolved_answer_closes_session_without_main_answer(): void
    {
        $assessment = $this->fixture('P', 'guest-token');

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $clarification = $this->postJson("/api/ai/assessments/{$assessment->id}/clarify-question", [
                'box_id' => 'BOX0000001',
            ], ['X-Session-Token' => 'guest-token'])->assertOk();
            $unresolved = collect($clarification->json('data.choices'))
                ->firstWhere('maps_to', 'requires_user_choice');
            $answer = $this->postJson(
                "/api/ai/clarification-questions/{$clarification->json('data.question_id')}/answer",
                ['choice_id' => $unresolved['id']],
                ['X-Session-Token' => 'guest-token'],
            )->assertOk();
        }

        $answer->assertJsonPath('data.status', 'unresolved')->assertJsonPath('data.can_retry', false);
        $this->assertDatabaseCount('ai_clarification_answers', 3);
        $this->assertDatabaseCount('assessment_answers', 0);

        $this->postJson("/api/ai/assessments/{$assessment->id}/clarify-question", [
            'box_id' => 'BOX0000001',
        ], ['X-Session-Token' => 'guest-token'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'ใช้คำถามช่วยสำหรับคำถามนี้ครบแล้ว กรุณาเลือกคำตอบหลักหรือย้อนกลับ');
        $this->assertDatabaseCount('ai_clarification_sessions', 1);
    }

    public function test_clarification_rejects_wrong_guest_token_and_too_many_attempts(): void
    {
        $assessment = $this->fixture('P', 'guest-token');

        $this->postJson("/api/ai/assessments/{$assessment->id}/clarify-question", [
            'box_id' => 'BOX0000001', 'attempt' => 1,
        ], ['X-Session-Token' => 'wrong'])->assertForbidden();

        $this->postJson("/api/ai/assessments/{$assessment->id}/clarify-question", [
            'box_id' => 'BOX0000001', 'attempt' => 4,
        ], ['X-Session-Token' => 'guest-token'])->assertUnprocessable();
    }

    public function test_provider_confirmation_alias_is_normalized_instead_of_returning_validation_error(): void
    {
        $this->app->instance(AiClient::class, new class implements AiClient
        {
            public function generateStructured(string $instructions, array $input, array $schema): array
            {
                return [
                    'question_text' => 'คำถามช่วย',
                    'explanation' => 'เลือกสิ่งที่สังเกตได้',
                    'choices' => [
                        ['id' => 'a', 'label' => 'มี', 'maps_to' => 'yes', 'maps_to_choice_id' => 'YES0000001'],
                        ['id' => 'b', 'label' => 'ไม่มี', 'maps_to' => 'no', 'maps_to_choice_id' => 'NO00000001'],
                        ['id' => 'c', 'label' => 'ไม่แน่ใจ', 'maps_to' => 'requires_user_confirmation', 'maps_to_choice_id' => ''],
                    ],
                    'requires_user_confirmation' => false,
                ];
            }
        });
        $assessment = $this->fixture('P', 'guest-token');

        $this->postJson("/api/ai/assessments/{$assessment->id}/clarify-question", [
            'box_id' => 'BOX0000001',
        ], ['X-Session-Token' => 'guest-token'])
            ->assertOk()
            ->assertJsonPath('data.requires_user_confirmation', true)
            ->assertJsonPath('data.choices.0.maps_to', 'yes')
            ->assertJsonPath('data.choices.0.maps_to_choice_id', 'YES0000001')
            ->assertJsonPath('data.choices.1.maps_to', 'no')
            ->assertJsonPath('data.choices.1.maps_to_choice_id', 'NO00000001')
            ->assertJsonPath('data.choices.2.maps_to', 'requires_user_choice');
    }

    public function test_completed_assessment_can_receive_guidance_without_changing_results(): void
    {
        $assessment = $this->fixture('C', 'guest-token');

        $this->postJson("/api/ai/assessments/{$assessment->id}/guidance", [], [
            'X-Session-Token' => 'guest-token',
        ])->assertOk()->assertJsonStructure(['data' => [
            'summary', 'assessment_overview', 'self_care', 'warning_signs',
            'next_steps', 'disclaimer', 'cached', 'generated_at',
        ]])->assertJsonPath('data.cached', false);

        $this->postJson("/api/ai/assessments/{$assessment->id}/guidance", [], [
            'X-Session-Token' => 'guest-token',
        ])->assertOk()->assertJsonPath('data.cached', true);

        $this->assertDatabaseCount('ai_assessment_guidances', 1);

        $this->assertSame('C', $assessment->fresh()->assessment_status);
    }

    public function test_authenticated_user_can_request_trend_summary(): void
    {
        $user = User::create([
            'user_id' => '000000001', 'first_name' => 'AI', 'last_name' => 'User',
            'email' => 'ai@example.test', 'password' => 'password',
        ]);

        $this->actingAs($user)->postJson('/api/ai/health-trends/summary', ['days' => 30])
            ->assertOk()->assertJsonStructure(['data' => [
                'summary', 'observations', 'self_care', 'warning_signs', 'allowed_actions', 'disclaimer', 'source', 'cached',
            ]])->assertJsonPath('data.source', 'ai')->assertJsonPath('data.cached', false);

        $this->actingAs($user)->postJson('/api/ai/health-trends/summary', ['days' => 30])
            ->assertOk()->assertJsonPath('data.cached', true);
    }

    public function test_trend_summary_falls_back_when_ai_provider_fails(): void
    {
        $this->app->instance(AiClient::class, new class implements AiClient
        {
            public function generateStructured(string $instructions, array $input, array $schema): array
            {
                throw new \RuntimeException('Provider unavailable');
            }
        });
        $user = User::create([
            'user_id' => '000000002', 'first_name' => 'Fallback', 'last_name' => 'User',
            'email' => 'fallback@example.test', 'password' => 'password',
        ]);

        $this->actingAs($user)->postJson('/api/ai/health-trends/summary', ['days' => 30])
            ->assertOk()
            ->assertJsonPath('data.source', 'backend_fallback')
            ->assertJsonPath('data.cached', false)
            ->assertJsonStructure(['data' => [
                'summary', 'observations', 'self_care', 'warning_signs', 'allowed_actions', 'disclaimer',
            ]]);
    }

    private function fixture(string $status, string $token): Assessment
    {
        DB::table('symptom_categories')->insert([
            'symptom_category_id' => 'SC0001', 'category_name' => 'Test', 'status' => '1',
        ]);
        DB::table('main_symptoms')->insert([
            'symptom_id' => 'SYM0000001', 'symptom_name' => 'Test symptom',
            'symptom_category_id' => 'SC0001', 'status' => '1',
        ]);
        DB::table('diagrams')->insert([
            'diagram_id' => 'DG001', 'diagram_name' => 'Test diagram', 'status' => '1',
        ]);
        DB::table('question_boxes')->insert([
            'box_id' => 'BOX0000001', 'question_text' => 'มีอาการนี้หรือไม่',
            'question_type' => 'S', 'diagram_id' => 'DG001', 'status' => '1',
        ]);
        foreach ([['YES0000001', 'ใช่'], ['NO00000001', 'ไม่ใช่']] as $index => [$id, $text]) {
            DB::table('answer_choices')->insert([
                'choice_id' => $id, 'box_id' => 'BOX0000001', 'choice_text' => $text,
                'order' => $index + 1, 'status' => '1',
            ]);
        }
        DB::table('diagrams')->where('diagram_id', 'DG001')->update(['entry_box_id' => 'BOX0000001']);
        DB::table('symptom_diagrams')->insert(['symptom_id' => 'SYM0000001', 'diagram_id' => 'DG001']);

        return Assessment::create([
            'session_token' => $token, 'symptom_id' => 'SYM0000001', 'diagram_id' => 'DG001',
            'assessment_status' => $status, 'started_at' => now(),
            'completed_at' => $status === 'C' ? now() : null,
        ]);
    }
}
