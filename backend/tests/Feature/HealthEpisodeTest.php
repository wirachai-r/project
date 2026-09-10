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

        $questions = collect($start->json('data.symptoms.0.questions'));
        $this->assertCount(0, $custom->json('data.questions'));
        $multipleChoice = $questions->firstWhere('answer_type', 'multiple_choice');
        $scale = $questions->firstWhere('answer_type', 'scale');
        $date = $questions->firstWhere('answer_type', 'date');
        $time = $questions->firstWhere('answer_type', 'time');
        $entry = $this->postJson('/api/episode-symptoms/'.$start->json('data.symptoms.0.id').'/follow-ups', [
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

        $this->patchJson('/api/follow-up-entries/'.$entry->json('data.id'), [
            'severity' => 2,
            'note' => 'อัปเดตบันทึกวันนี้',
            'answers' => [
                ['question_template_id' => $questions[0]['id'], 'value' => 'ดีขึ้น'],
                ['question_template_id' => $questions[1]['id'], 'value' => false],
            ],
        ])->assertOk()->assertJsonPath('data.severity', 2);
        $this->assertDatabaseCount('follow_up_entries', 1);
        $this->assertDatabaseHas('follow_up_entries', ['severity' => 2, 'note' => 'อัปเดตบันทึกวันนี้']);
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

    public function test_unsaved_assessment_cannot_start_symptom_tracking(): void
    {
        [$user, $assessment] = $this->fixture();
        $assessment->update(['is_saved' => false]);

        $this->actingAs($user)
            ->postJson("/api/assessments/{$assessment->id}/health-episode")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'กรุณาบันทึกผลประเมินลงประวัติก่อนเริ่มติดตามอาการ');

        $this->assertDatabaseCount('health_episodes', 0);
    }

    public function test_episode_can_start_directly_from_a_daily_health_record(): void
    {
        [$user] = $this->fixture();
        $record = $this->actingAs($user)->postJson('/api/daily-health-records', [
            'recorded_on' => now()->toDateString(),
            'status' => 'unwell',
            'symptom_ids' => ['SYM0000001', 'SYM0000002'],
            'health_episode_ids' => [],
        ])->assertCreated();

        $start = $this->postJson('/api/daily-health-records/'.$record->json('data.id').'/health-episode', [
            'symptom_ids' => ['SYM0000001', 'SYM0000002'],
        ])->assertCreated()
            ->assertJsonPath('data.source_assessment_id', null)
            ->assertJsonPath('data.symptoms.0.is_primary', true)
            ->assertJsonPath('data.symptoms.1.is_primary', false);

        $this->assertDatabaseHas('daily_health_record_health_episode', [
            'daily_health_record_id' => $record->json('data.id'),
            'health_episode_id' => $start->json('data.id'),
        ]);
        $this->assertDatabaseCount('episode_symptoms', 2);
    }

    public function test_daily_record_tracking_rejects_symptoms_not_in_the_record(): void
    {
        [$user] = $this->fixture();
        $recordId = $this->actingAs($user)->postJson('/api/daily-health-records', [
            'recorded_on' => now()->toDateString(),
            'status' => 'unwell',
            'symptom_ids' => ['SYM0000001'],
            'health_episode_ids' => [],
        ])->json('data.id');

        $this->postJson("/api/daily-health-records/{$recordId}/health-episode", [
            'symptom_ids' => ['SYM0000002'],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('health_episodes', 0);
    }

    public function test_daily_record_symptom_can_join_an_existing_active_episode(): void
    {
        [$user, $assessment] = $this->fixture();
        $episodeId = $this->actingAs($user)
            ->postJson("/api/assessments/{$assessment->id}/health-episode")
            ->json('data.id');
        $recordId = $this->postJson('/api/daily-health-records', [
            'recorded_on' => now()->toDateString(),
            'status' => 'unwell',
            'symptom_ids' => ['SYM0000002'],
            'health_episode_ids' => [],
        ])->json('data.id');

        $this->postJson("/api/daily-health-records/{$recordId}/health-episode", [
            'symptom_ids' => ['SYM0000002'],
            'health_episode_id' => $episodeId,
        ])->assertCreated()
            ->assertJsonPath('data.id', $episodeId)
            ->assertJsonCount(2, 'data.symptoms');

        $this->assertDatabaseCount('health_episodes', 1);
        $this->assertDatabaseHas('episode_symptoms', [
            'health_episode_id' => $episodeId,
            'symptom_id' => 'SYM0000002',
            'is_primary' => false,
        ]);
    }

    public function test_another_assessment_can_be_attached_to_an_active_episode_and_user_can_end_it(): void
    {
        [$user, $assessment] = $this->fixture();
        $episodeId = $this->actingAs($user)
            ->postJson("/api/assessments/{$assessment->id}/health-episode")->json('data.id');
        $related = Assessment::create([
            'user_id' => $user->user_id, 'symptom_id' => 'SYM0000002', 'diagram_id' => 'DG001',
            'assessment_status' => 'C', 'started_at' => now(), 'completed_at' => now(),
            'is_saved' => true,
        ]);

        $this->postJson("/api/assessments/{$related->id}/health-episode", [
            'health_episode_id' => $episodeId,
        ])->assertCreated()->assertJsonCount(2, 'data.assessments');

        $this->patchJson("/api/health-episodes/{$episodeId}/status", [
            'status' => 'E', 'end_reason' => 'improved',
        ])->assertOk()->assertJsonPath('data.status', 'E')->assertJsonPath('data.end_reason', 'improved');
        $this->assertDatabaseHas('health_episode_assessments', [
            'health_episode_id' => $episodeId,
            'assessment_id' => $related->id,
            'relationship_type' => 'related',
        ]);
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
            'is_saved' => true,
        ]);

        return [$user, $assessment];
    }
}
