<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssessmentAccessContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_start_and_read_own_completed_assessment_with_session_token(): void
    {
        $this->assessmentFixture();

        $start = $this->postJson('/api/assessments/start', ['symptom_id' => 'SYM0000001'])
            ->assertCreated()
            ->assertJsonStructure(['assessment_id', 'diagram_id', 'first_box', 'session_token']);

        $assessment = Assessment::findOrFail($start->json('assessment_id'));
        $assessment->update(['assessment_status' => 'C', 'completed_at' => now()]);

        $this->getJson("/api/assessments/{$assessment->id}/result", [
            'X-Session-Token' => $start->json('session_token'),
        ])->assertOk();
    }

    public function test_guest_cannot_access_another_guest_assessment(): void
    {
        $assessment = $this->completedAssessment('owner-session');

        $this->getJson("/api/assessments/{$assessment->id}/result", [
            'X-Session-Token' => 'different-session',
        ])->assertForbidden();

        $this->getJson("/api/assessments/{$assessment->id}/result")
            ->assertForbidden();
    }

    public function test_guest_can_abandon_own_processing_assessment(): void
    {
        $this->assessmentFixture();

        $start = $this->postJson('/api/assessments/start', ['symptom_id' => 'SYM0000001'])
            ->assertCreated();

        $assessmentId = $start->json('assessment_id');
        $headers = ['X-Session-Token' => $start->json('session_token')];

        $this->getJson('/api/assessments/pending?symptom_id=SYM0000001', $headers)
            ->assertOk()
            ->assertJsonPath('data.assessment_id', $assessmentId)
            ->assertJsonPath('data.current_box.box_id', 'BOX0000001');

        $this->postJson("/api/assessments/{$assessmentId}/abandon", [], $headers)
            ->assertOk()
            ->assertJsonPath('assessment_status', 'A');

        $this->assertDatabaseHas('assessments', [
            'id' => $assessmentId,
            'assessment_status' => 'A',
            'completed_at' => null,
        ]);

        $this->postJson("/api/assessments/{$assessmentId}/answer", [
            'answers' => [],
        ], $headers)->assertUnprocessable();

        $this->getJson('/api/assessments/pending?symptom_id=SYM0000001', $headers)
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_history_and_save_remain_authenticated(): void
    {
        $assessment = $this->completedAssessment('guest-session');

        $this->getJson('/api/assessments')->assertUnauthorized();
        $this->postJson("/api/assessments/{$assessment->id}/save", [], [
            'X-Session-Token' => 'guest-session',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_claim_and_save_their_guest_result(): void
    {
        $assessment = $this->completedAssessment('guest-session');
        $user = $this->user();

        $this->actingAs($user)->postJson("/api/assessments/{$assessment->id}/save", [], [
            'X-Session-Token' => 'guest-session',
        ])->assertOk();

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'user_id' => $user->user_id,
            'session_token' => null,
            'is_saved' => true,
        ]);
    }

    public function test_authenticated_start_attaches_the_assessment_to_the_user(): void
    {
        $this->assessmentFixture();
        $user = $this->user();

        $response = $this->actingAs($user)->postJson('/api/assessments/start', [
            'symptom_id' => 'SYM0000001',
        ])->assertCreated()->assertJsonPath('session_token', null);

        $this->assertDatabaseHas('assessments', [
            'id' => $response->json('assessment_id'),
            'user_id' => $user->user_id,
            'session_token' => null,
        ]);
    }

    private function completedAssessment(string $sessionToken): Assessment
    {
        $this->assessmentFixture();

        return Assessment::create([
            'user_id' => null,
            'session_token' => $sessionToken,
            'symptom_id' => 'SYM0000001',
            'diagram_id' => 'DG001',
            'assessment_status' => 'C',
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }

    private function user(): User
    {
        return User::create([
            'user_id' => '000000001',
            'first_name' => 'Assessment',
            'last_name' => 'User',
            'email' => 'assessment@example.test',
            'password' => 'password',
        ]);
    }

    private function assessmentFixture(): void
    {
        DB::table('symptom_categories')->insert([
            'symptom_category_id' => 'SC0001',
            'category_name' => 'Test category',
            'status' => '1',
        ]);
        DB::table('main_symptoms')->insert([
            'symptom_id' => 'SYM0000001',
            'symptom_name' => 'Test symptom',
            'symptom_category_id' => 'SC0001',
            'status' => '1',
        ]);
        DB::table('diagrams')->insert([
            'diagram_id' => 'DG001',
            'diagram_name' => 'Test diagram',
            'status' => '1',
        ]);
        DB::table('question_boxes')->insert([
            'box_id' => 'BOX0000001',
            'question_text' => 'Test question',
            'question_type' => 'S',
            'diagram_id' => 'DG001',
            'status' => '1',
        ]);
        DB::table('diagrams')->where('diagram_id', 'DG001')->update(['entry_box_id' => 'BOX0000001']);
        DB::table('symptom_diagrams')->insert([
            'symptom_id' => 'SYM0000001',
            'diagram_id' => 'DG001',
        ]);
    }
}
