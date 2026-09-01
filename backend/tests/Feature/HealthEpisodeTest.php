<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthEpisodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_episode_starts_with_one_primary_symptom_and_accepts_multiple_symptoms_without_new_assessments(): void
    {
        [$user, $assessment] = $this->fixture();

        $start = $this->actingAs($user)->postJson("/api/assessments/{$assessment->id}/health-episode")
            ->assertCreated()
            ->assertJsonPath('data.symptoms.0.symptom_id', 'SYM0000001')
            ->assertJsonPath('data.symptoms.0.is_primary', true);

        $episodeId = $start->json('data.id');
        $this->postJson("/api/health-episodes/{$episodeId}/symptoms", ['symptom_id' => 'SYM0000002'])
            ->assertCreated()->assertJsonPath('data.is_primary', false);
        $custom = $this->postJson("/api/health-episodes/{$episodeId}/symptoms", [
            'custom_symptom_text' => 'อาการที่ยังไม่มีในรายการ',
        ])->assertCreated();

        $this->assertDatabaseCount('assessments', 1);
        $this->assertDatabaseCount('episode_symptoms', 3);

        $questions = collect($custom->json('data.questions'));
        $multipleChoice = $questions->firstWhere('answer_type', 'multiple_choice');
        $scale = $questions->firstWhere('answer_type', 'scale');
        $date = $questions->firstWhere('answer_type', 'date');
        $time = $questions->firstWhere('answer_type', 'time');
        $this->postJson('/api/episode-symptoms/'.$custom->json('data.id').'/follow-ups', [
            'severity' => 4,
            'note' => 'ติดตามอาการใหม่',
            'answers' => [
                ['question_template_id' => $questions[0]['id'], 'value' => 'ใกล้เคียงเดิม'],
                ['question_template_id' => $questions[1]['id'], 'value' => true],
                ['question_template_id' => $multipleChoice['id'], 'value' => ['ไอ', 'เจ็บคอ']],
                ['question_template_id' => $scale['id'], 'value' => '3'],
                ['question_template_id' => $date['id'], 'value' => '2026-08-31'],
                ['question_template_id' => $time['id'], 'value' => '09:30'],
            ],
        ])->assertCreated();
        $this->assertDatabaseHas('follow_up_entries', ['severity' => 4, 'note' => 'ติดตามอาการใหม่']);
        $this->assertDatabaseCount('follow_up_entry_answers', 6);
        $this->assertDatabaseHas('follow_up_entry_answers', [
            'question_text_snapshot' => 'มีอาการใหม่เกิดขึ้นหรือไม่?',
        ]);
    }

    public function test_duplicate_symptom_is_rejected_and_other_user_cannot_access_episode(): void
    {
        [$user, $assessment] = $this->fixture();
        $episodeId = $this->actingAs($user)
            ->postJson("/api/assessments/{$assessment->id}/health-episode")->json('data.id');

        $this->postJson("/api/health-episodes/{$episodeId}/symptoms", ['symptom_id' => 'SYM0000001'])
            ->assertUnprocessable();

        $other = User::create([
            'user_id' => '000000002', 'first_name' => 'Other', 'last_name' => 'User',
            'email' => 'other-episode@example.test', 'password' => 'password',
        ]);
        $this->actingAs($other)->getJson("/api/health-episodes/{$episodeId}")->assertForbidden();
    }

    private function fixture(): array
    {
        $user = User::create([
            'user_id' => '000000001', 'first_name' => 'Episode', 'last_name' => 'User',
            'email' => 'episode@example.test', 'password' => 'password',
        ]);
        DB::table('symptom_categories')->insert([
            'symptom_category_id' => 'SC0001', 'category_name' => 'Test', 'status' => '1',
        ]);
        foreach ([['SYM0000001', 'อาการหลัก'], ['SYM0000002', 'อาการร่วม']] as [$id, $name]) {
            DB::table('main_symptoms')->insert([
                'symptom_id' => $id, 'symptom_name' => $name,
                'symptom_category_id' => 'SC0001', 'status' => '1',
            ]);
        }
        DB::table('diagrams')->insert(['diagram_id' => 'DG001', 'diagram_name' => 'Test', 'status' => '1']);
        DB::table('follow_up_question_templates')->insert([
            [
                'question_text' => 'เลือกอาการร่วม', 'answer_type' => 'multiple_choice',
                'options' => json_encode(['ไอ', 'เจ็บคอ'], JSON_UNESCAPED_UNICODE),
                'is_required' => false, 'applies_to_all_symptoms' => true, 'status' => '1',
            ],
            [
                'question_text' => 'วันที่เริ่มสังเกตอาการ', 'answer_type' => 'date',
                'options' => null,
                'is_required' => false, 'applies_to_all_symptoms' => true, 'status' => '1',
            ],
            [
                'question_text' => 'เลือกระดับอาการ', 'answer_type' => 'scale',
                'options' => json_encode(['1', '2', '3', '4', '5'], JSON_UNESCAPED_UNICODE),
                'is_required' => false, 'applies_to_all_symptoms' => true, 'status' => '1',
            ],
            [
                'question_text' => 'เวลาที่สังเกตอาการ', 'answer_type' => 'time',
                'options' => null,
                'is_required' => false, 'applies_to_all_symptoms' => true, 'status' => '1',
            ],
        ]);

        $assessment = Assessment::create([
            'user_id' => $user->user_id, 'symptom_id' => 'SYM0000001', 'diagram_id' => 'DG001',
            'assessment_status' => 'C', 'started_at' => now(), 'completed_at' => now(),
        ]);

        return [$user, $assessment];
    }
}
