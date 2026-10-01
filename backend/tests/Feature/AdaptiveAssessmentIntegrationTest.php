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

    public function test_initial_symptom_creates_candidate_diseases(): void
    {
        $this->fixture();
        $this->start()->assertJsonPath('status', 'completed')->assertJsonCount(2, 'results');
    }

    public function test_initial_symptom_is_present_evidence(): void
    {
        $this->fixture();
        $this->start()
            ->assertJsonPath('evidence_summary.present_symptoms.0.symptom_id', 'SYM0000001')
            ->assertJsonPath('evidence_summary.present_symptoms.0.source', 'initial')
            ->assertJsonPath('results.0.supporting_symptom_count', 1);
    }

    public function test_initial_symptom_is_not_an_answered_question(): void
    {
        $this->fixture();
        $response = $this->start()->assertJsonPath('evidence_summary.answered_question_count', 0);
        $this->assertDatabaseHas('adaptive_assessments', ['id' => $response->json('assessment_id'), 'question_count' => 0]);
        $this->assertDatabaseCount('adaptive_assessment_answers', 0);
    }

    public function test_initial_symptom_uses_its_owned_question(): void
    {
        $this->fixture();
        $owned = $this->question('SYM0000001', 'คำถามประจำอาการ', 'SYM0000001', required: true);
        $this->question('SYM0000002', 'คำถามอาการอื่น', 'SYM0000001', priority: 2);
        $this->start()->assertJsonPath('question.question_id', $owned);
    }

    public function test_phase_one_uses_admin_question_for_initial_symptom(): void
    {
        $this->fixture();
        $id = $this->question('SYM0000001', 'ข้อความจากผู้ดูแล', 'SYM0000001');
        $this->start()->assertJsonPath('question.question_id', $id)
            ->assertJsonPath('question.text', 'ข้อความจากผู้ดูแล')
            ->assertJsonPath('question.phase', 'frame');
    }

    public function test_admin_approval_also_reviews_the_selected_question_routes(): void
    {
        $this->fixture();
        $questionId = $this->question(
            'SYM0000002',
            'คำถามที่ผู้ดูแลอนุมัติ',
            'SYM0000001',
            status: 'draft',
        );
        DB::table('adaptive_question_rules')->where('adaptive_question_id', $questionId)->update([
            'evidence_source' => null,
            'evidence_status' => 'unreviewed',
            'reviewed_at' => null,
        ]);
        $admin = User::create([
            'user_id' => '000000001',
            'first_name' => 'Adaptive',
            'last_name' => 'Admin',
            'email' => 'adaptive-admin@example.test',
            'password' => 'password',
            'role' => 'Admin',
        ]);

        $this->actingAs($admin)->putJson("/api/admin/adaptive-questions/{$questionId}", [
            'question_symptom_ids' => ['SYM0000002'],
            'question_text' => 'คำถามที่ผู้ดูแลอนุมัติ',
            'explanation_text' => '',
            'answer_type' => 'yes_no_unsure',
            'status' => 'approved',
            'evidence_source' => 'Reviewed clinical reference',
            'options' => [],
            'rules' => [[
                'initial_symptom_id' => 'SYM0000001',
                'question_stage' => 'associated',
                'priority' => 1,
                'is_required' => false,
                'status' => '1',
                'evidence_status' => 'unreviewed',
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('adaptive_question_rules', [
            'adaptive_question_id' => $questionId,
            'initial_symptom_id' => 'SYM0000001',
            'evidence_source' => 'Reviewed clinical reference',
            'evidence_status' => 'reviewed',
            'reviewed_by' => $admin->user_id,
        ]);
    }

    public function test_admin_can_manage_a_question_group_from_the_initial_symptom(): void
    {
        $this->fixture();
        $first = $this->question('SYM0000002', 'คำถามไอ', 'SYM0000002');
        $second = $this->question('SYM0000003', 'คำถามหอบ', 'SYM0000003');
        $admin = User::create([
            'user_id' => '000000002',
            'first_name' => 'Group',
            'last_name' => 'Admin',
            'email' => 'group-admin@example.test',
            'password' => 'password',
            'role' => 'Admin',
        ]);

        $this->actingAs($admin)->putJson('/api/admin/adaptive-question-groups/SYM0000001', [
            'questions' => [
                [
                    'adaptive_question_id' => $first,
                    'question_stage' => 'local',
                    'is_required' => true,
                ],
                [
                    'adaptive_question_id' => $second,
                    'question_stage' => 'associated',
                    'is_required' => false,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('adaptive_question_rules', [
            'initial_symptom_id' => 'SYM0000001',
            'adaptive_question_id' => $first,
            'priority' => 1,
            'question_stage' => 'local',
        ]);
        $this->assertDatabaseHas('adaptive_question_rules', [
            'initial_symptom_id' => 'SYM0000001',
            'adaptive_question_id' => $second,
            'priority' => 2,
            'question_stage' => 'associated',
        ]);
    }

    public function test_admin_cannot_add_the_initial_symptom_to_its_own_group(): void
    {
        $this->fixture();
        $selfQuestion = $this->question('SYM0000001', 'คำถามอาการตัวเอง', 'SYM0000001');
        $admin = User::create([
            'user_id' => '000000003',
            'first_name' => 'No Self',
            'last_name' => 'Admin',
            'email' => 'no-self-admin@example.test',
            'password' => 'password',
            'role' => 'Admin',
        ]);

        $this->actingAs($admin)->putJson('/api/admin/adaptive-question-groups/SYM0000001', [
            'questions' => [[
                'adaptive_question_id' => $selfQuestion,
                'question_stage' => 'local',
                'is_required' => true,
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors('questions');
    }

    public function test_owned_initial_question_precedes_phase_two(): void
    {
        $this->fixtureWithDiscriminationData();
        $frame = $this->question('SYM0000001', 'คำถามประจำอาการเริ่มต้น', 'SYM0000001', required: true);
        $this->question('SYM0000003', 'คำถามเจาะลึก', 'SYM0000001');
        $this->start()->assertJsonPath('question.question_id', $frame)->assertJsonPath('question.phase', 'frame');
    }

    public function test_configured_symptom_group_is_completed_in_priority_order(): void
    {
        $this->fixtureWithDiscriminationData();
        $first = $this->question('SYM0000002', 'คำถามแรกในกลุ่ม', 'SYM0000001', priority: 1);
        $second = $this->question('SYM0000003', 'คำถามที่สองในกลุ่ม', 'SYM0000001', priority: 2);
        DB::table('adaptive_question_rules')->whereIn('adaptive_question_id', [$first, $second])->update([
            'initial_symptom_id' => 'SYM0000001',
        ]);

        [, $next] = $this->startAndAnswer($first, 'yes');
        $next->assertJsonPath('status', 'question')
            ->assertJsonPath('question.question_id', $second)
            ->assertJsonPath('question.phase', 'frame');
    }

    public function test_discrimination_question_precedes_remaining_optional_group_after_minimum_answers(): void
    {
        config(['adaptive_assessment.minimum_clear_answers' => 1]);
        $this->fixtureWithDiscriminationData();
        DB::table('diseases')->update(['minimum_supporting_symptoms' => 2]);
        $first = $this->question('SYM0000002', 'first group question', 'SYM0000001', priority: 1);
        $remaining = $this->question('SYM0000004', 'remaining group question', 'SYM0000001', priority: 2);
        $discrimination = $this->question('SYM0000003', 'candidate disease symptom', 'SYM0000003');
        DB::table('adaptive_question_rules')->whereIn('adaptive_question_id', [$first, $remaining])->update([
            'initial_symptom_id' => 'SYM0000001',
        ]);

        [, $response] = $this->startAndAnswer($first, 'no');

        $response->assertJsonPath('status', 'question')
            ->assertJsonPath('question.question_id', $discrimination)
            ->assertJsonPath('question.phase', 'discrimination');
    }

    public function test_optional_group_questions_stop_once_evidence_is_sufficient(): void
    {
        config(['adaptive_assessment.minimum_clear_answers' => 1]);
        $this->fixture();
        $first = $this->question('SYM0000002', 'first optional question', 'SYM0000001', priority: 1);
        $this->question('SYM0000003', 'second optional question', 'SYM0000001', priority: 2);
        DB::table('adaptive_question_rules')->where('adaptive_question_id', $first)->update([
            'initial_symptom_id' => 'SYM0000001',
        ]);

        [, $response] = $this->startAndAnswer($first, 'yes');

        $response->assertJsonPath('status', 'completed')
            ->assertJsonPath('evidence_summary.answered_question_count', 1);
    }

    public function test_tied_matching_diseases_do_not_force_more_questions(): void
    {
        config(['adaptive_assessment.minimum_clear_answers' => 1]);
        $this->fixture();
        DB::table('disease_symptoms')->insert($this->ds('DIS0000002', 'SYM0000002'));
        $matching = $this->question('SYM0000002', 'shared supporting symptom', 'SYM0000001', priority: 1);
        $unnecessary = $this->question('SYM0000003', 'unnecessary separating question', 'SYM0000001', priority: 2);
        DB::table('adaptive_question_rules')->whereIn('adaptive_question_id', [$matching, $unnecessary])->update([
            'initial_symptom_id' => 'SYM0000001',
        ]);

        [, $response] = $this->startAndAnswer($matching, 'yes');

        $response->assertJsonPath('status', 'completed')
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('evidence_summary.answered_question_count', 1);
    }

    public function test_initial_symptom_alone_does_not_stop_after_minimum_clear_answers(): void
    {
        config(['adaptive_assessment.minimum_clear_answers' => 1]);
        $this->fixture();
        $required = $this->question(
            'SYM0000005',
            'required unrelated symptom',
            'SYM0000001',
            required: true,
        );
        $related = $this->question('SYM0000002', 'related disease symptom', 'SYM0000002');
        DB::table('adaptive_question_rules')->where('adaptive_question_id', $required)->update([
            'initial_symptom_id' => 'SYM0000001',
        ]);

        [, $response] = $this->startAndAnswer($required, 'no');

        $response->assertJsonPath('status', 'question')
            ->assertJsonPath('question.question_id', $related)
            ->assertJsonPath('question.phase', 'discrimination');
    }

    public function test_low_threshold_match_does_not_hide_supported_higher_threshold_disease(): void
    {
        config(['adaptive_assessment.minimum_clear_answers' => 1]);
        $this->fixtureWithDiscriminationData();
        DB::table('diseases')->where('disease_id', 'DIS0000001')->update([
            'minimum_supporting_symptoms' => 1,
        ]);
        DB::table('diseases')->where('disease_id', 'DIS0000002')->update([
            'minimum_supporting_symptoms' => 3,
        ]);
        DB::table('disease_symptoms')->insert($this->ds('DIS0000002', 'SYM0000002'));
        $first = $this->question('SYM0000002', 'first supporting symptom', 'SYM0000001', required: true);
        $higherThreshold = $this->question('SYM0000003', 'higher threshold symptom', 'SYM0000003');
        DB::table('adaptive_question_rules')->where('adaptive_question_id', $first)->update([
            'initial_symptom_id' => 'SYM0000001',
        ]);

        [$start, $response] = $this->startAndAnswer($first, 'yes');

        $response->assertJsonPath('status', 'question')
            ->assertJsonPath('question.question_id', $higherThreshold)
            ->assertJsonPath('question.phase', 'discrimination');

        $this->withHeader('X-Session-Token', $start->json('session_token'))
            ->postJson('/api/adaptive-assessments/'.$start->json('assessment_id').'/answer', [
                'question_id' => $higherThreshold,
                'answer' => 'yes',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonFragment(['disease_id' => 'DIS0000002']);
    }

    public function test_non_discriminating_remaining_symptoms_do_not_extend_assessment(): void
    {
        config(['adaptive_assessment.minimum_clear_answers' => 1]);
        $this->fixture();
        DB::table('diseases')->where('disease_id', 'DIS0000001')->update([
            'minimum_supporting_symptoms' => 1,
        ]);
        DB::table('diseases')->where('disease_id', 'DIS0000002')->update([
            'minimum_supporting_symptoms' => 3,
        ]);
        DB::table('disease_symptoms')->insert([
            $this->ds('DIS0000002', 'SYM0000002'),
            $this->ds('DIS0000001', 'SYM0000003'),
            $this->ds('DIS0000002', 'SYM0000003'),
        ]);
        $shared = $this->question('SYM0000002', 'shared evidence', 'SYM0000001', required: true);
        $this->question('SYM0000003', 'unnecessary high threshold question', 'SYM0000003');
        DB::table('adaptive_question_rules')->where('adaptive_question_id', $shared)->update([
            'initial_symptom_id' => 'SYM0000001',
        ]);

        [, $response] = $this->startAndAnswer($shared, 'yes');

        $response->assertJsonPath('status', 'completed')
            ->assertJsonPath('evidence_summary.answered_question_count', 1);
    }

    public function test_result_count_uses_the_number_of_symptoms_of_each_disease(): void
    {
        $this->fixture();

        $this->start()
            ->assertJsonPath('results.0.supporting_symptom_count', 1)
            ->assertJsonPath('results.0.evaluated_symptom_count', 2)
            ->assertJsonPath('results.1.supporting_symptom_count', 1)
            ->assertJsonPath('results.1.evaluated_symptom_count', 1);
    }

    public function test_related_symptom_question_is_asked_next(): void
    {
        $this->fixtureWithDiscriminationData();
        $frame = $this->question('SYM0000001', 'คำถามประจำอาการเริ่มต้น', 'SYM0000001', required: true);
        $adaptive = $this->question('SYM0000003', 'คำถามเจาะลึก', 'SYM0000001');
        [, $next] = $this->startAndAnswer($frame, 'yes');
        $next->assertJsonPath('status', 'question')->assertJsonPath('question.question_id', $adaptive)
            ->assertJsonPath('question.phase', 'discrimination');
    }

    public function test_phase_two_uses_candidate_disease_symptoms(): void
    {
        $this->fixtureWithDiscriminationData();
        $linked = $this->question('SYM0000003', 'คำถามอาการโรค', 'SYM0000001');
        $this->question('SYM0000005', 'ไม่เกี่ยวกับโรค', 'SYM0000001');
        $this->start()->assertJsonPath('question.question_id', $linked);
    }

    public function test_phase_two_can_ask_another_related_symptoms_owned_question(): void
    {
        $this->fixtureWithDiscriminationData();
        $otherRoute = $this->question('SYM0000003', 'ใช้กับอาการตั้งต้นอื่น', 'SYM0000002');

        $this->start()
            ->assertJsonPath('status', 'question')
            ->assertJsonPath('question.question_id', $otherRoute);
    }

    public function test_phase_two_can_reuse_approved_question_from_another_route(): void
    {
        $this->fixtureWithDiscriminationData();
        $question = $this->question('SYM0000003', 'approved question from another route', 'SYM0000003');
        DB::table('adaptive_question_rules')->where('adaptive_question_id', $question)->update([
            'initial_symptom_id' => 'SYM0000002',
        ]);

        $this->start()
            ->assertJsonPath('status', 'question')
            ->assertJsonPath('question.question_id', $question)
            ->assertJsonPath('question.phase', 'discrimination');
    }

    public function test_phase_two_uses_only_approved_question_bank(): void
    {
        $this->fixtureWithDiscriminationData();
        $approved = $this->question('SYM0000004', 'อนุมัติแล้ว', 'SYM0000001');
        $this->question('SYM0000003', 'ฉบับร่าง', 'SYM0000001', status: 'draft');
        $this->start()->assertJsonPath('question.question_id', $approved);
    }

    public function test_missing_question_bank_is_skipped_without_generation(): void
    {
        $this->fixtureWithDiscriminationData();
        $this->start()->assertJsonPath('status', 'completed');
        $this->assertDatabaseCount('adaptive_questions', 0);
    }

    public function test_unreviewed_disease_symptom_is_askable(): void
    {
        $this->fixtureWithDiscriminationData();
        DB::table('disease_symptoms')->where('symptom_id', 'SYM0000003')->update(['evidence_status' => 'unreviewed']);
        $question = $this->question('SYM0000003', 'ใช้ได้แม้ยังไม่ตรวจทาน', 'SYM0000003');
        DB::table('adaptive_question_rules')->where('adaptive_question_id', $question)->update([
            'evidence_status' => 'unreviewed',
        ]);

        $this->start()
            ->assertJsonPath('status', 'question')
            ->assertJsonPath('question.question_id', $question);
    }

    public function test_unreviewed_configured_question_rule_is_askable(): void
    {
        $this->fixture();
        $question = $this->question('SYM0000002', 'คำถามที่ยังไม่ตรวจทาน', 'SYM0000001');
        DB::table('adaptive_question_rules')->where('adaptive_question_id', $question)->update([
            'initial_symptom_id' => 'SYM0000001',
            'evidence_status' => 'unreviewed',
        ]);

        $this->start()
            ->assertJsonPath('status', 'question')
            ->assertJsonPath('question.question_id', $question)
            ->assertJsonPath('question.phase', 'frame');
    }

    public function test_generated_unreviewed_question_is_not_used_at_runtime(): void
    {
        $this->fixtureWithDiscriminationData();
        $questionId = $this->question('SYM0000003', 'คำถามที่สร้างอัตโนมัติ', 'SYM0000001');
        DB::table('adaptive_questions')->where('id', $questionId)->update(['origin' => 'generated']);
        DB::table('adaptive_question_rules')->where('adaptive_question_id', $questionId)
            ->update(['evidence_status' => 'unreviewed']);

        $this->start()->assertJsonPath('status', 'completed');
    }

    public function test_generated_reviewed_question_is_used_without_changing_its_source_text(): void
    {
        $this->fixtureWithDiscriminationData();
        $questionId = $this->question('SYM0000003', 'คำถามที่สร้างอัตโนมัติ', 'SYM0000001');
        DB::table('adaptive_questions')->where('id', $questionId)->update([
            'origin' => 'generated',
            'evidence_source' => 'Generated candidate from internal disease-symptom co-occurrence and taxonomy',
        ]);

        $this->start()
            ->assertJsonPath('status', 'question')
            ->assertJsonPath('question.question_id', $questionId);
    }

    public function test_shared_symptom_scores_below_balanced_split(): void
    {
        $this->fixtureWithDiscriminationData();
        DB::table('disease_symptoms')->insert([
            $this->ds('DIS0000002', 'SYM0000002'), $this->ds('DIS0000003', 'SYM0000002'),
            $this->ds('DIS0000004', 'SYM0000002'),
        ]);
        $balanced = $this->question('SYM0000003', 'แบ่งครึ่ง', 'SYM0000001', priority: 20);
        $this->question('SYM0000002', 'พบทุกโรค', 'SYM0000001');
        $this->start()->assertJsonPath('question.question_id', $balanced);
    }

    public function test_fifty_fifty_split_beats_one_of_four(): void
    {
        $this->fixtureWithDiscriminationData();
        $balanced = $this->question('SYM0000003', 'สองต่อสอง', 'SYM0000001', priority: 20);
        $this->question('SYM0000004', 'หนึ่งต่อสาม', 'SYM0000001');
        $this->start()->assertJsonPath('question.question_id', $balanced);
    }

    public function test_same_category_breaks_tie_between_equally_discriminating_questions(): void
    {
        $this->fixtureWithDiscriminationData();
        DB::table('symptom_categories')->insert([
            'symptom_category_id' => 'SC0002',
            'category_name' => 'Other',
            'status' => '1',
        ]);
        DB::table('main_symptoms')->where('symptom_id', 'SYM0000004')->update([
            'symptom_category_id' => 'SC0002',
        ]);
        DB::table('disease_symptoms')->insert($this->ds('DIS0000002', 'SYM0000004'));
        $sameCategory = $this->question('SYM0000003', 'same category', 'SYM0000003', priority: 20);
        $this->question('SYM0000004', 'other category', 'SYM0000004', priority: 1);

        $this->start()->assertJsonPath('question.question_id', $sameCategory);
    }

    public function test_same_category_precedes_stronger_cross_category_question(): void
    {
        $this->fixtureWithDiscriminationData();
        DB::table('symptom_categories')->insert([
            'symptom_category_id' => 'SC0002',
            'category_name' => 'Other',
            'status' => '1',
        ]);
        DB::table('main_symptoms')->where('symptom_id', 'SYM0000003')->update([
            'symptom_category_id' => 'SC0002',
        ]);
        $sameCategory = $this->question('SYM0000004', 'same category first', 'SYM0000004', priority: 20);
        $this->question('SYM0000003', 'stronger cross category', 'SYM0000003', priority: 1);

        $this->start()->assertJsonPath('question.question_id', $sameCategory);
    }

    public function test_phase_two_preserves_question_text_and_options(): void
    {
        $this->fixtureWithDiscriminationData();
        $id = $this->question('SYM0000003', 'คำถามเดิม', 'SYM0000001', answerType: 'single_choice', options: [
            ['option_text' => 'พบ', 'option_value' => 'present', 'answer_effect' => 'present'],
            ['option_text' => 'ไม่พบ', 'option_value' => 'absent', 'answer_effect' => 'absent'],
        ]);
        $this->start()->assertJsonPath('question.question_id', $id)->assertJsonPath('question.text', 'คำถามเดิม')
            ->assertJsonPath('question.options.0.text', 'พบ');
    }

    public function test_question_and_evidence_are_not_repeated(): void
    {
        $this->fixtureWithDiscriminationData();
        $id = $this->question('SYM0000003', 'ถามครั้งเดียว', 'SYM0000001');
        [, $response] = $this->startAndAnswer($id, 'unsure');
        $response->assertJsonMissing(['question_id' => $id]);
    }

    public function test_yes_answer_updates_evidence_and_ranking(): void
    {
        $this->fixtureWithDiscriminationData();
        $id = $this->question('SYM0000003', 'มีอาการหรือไม่', 'SYM0000001');
        [, $response] = $this->startAndAnswer($id, 'yes');
        $response->assertJsonPath('results.0.disease_id', 'DIS0000001')
            ->assertJsonPath('evidence_summary.present_symptoms.1.symptom_id', 'SYM0000003');
    }

    public function test_no_answer_uses_existing_absence_penalty(): void
    {
        $this->fixtureWithDiscriminationData();
        DB::table('disease_symptoms')->where('disease_id', 'DIS0000001')->where('symptom_id', 'SYM0000003')
            ->update(['is_key_symptom' => true, 'absence_penalty' => 5]);
        $id = $this->question('SYM0000003', 'ไม่มีอาการหรือไม่', 'SYM0000001');
        [, $response] = $this->startAndAnswer($id, 'no');
        $response->assertJsonPath('results.0.disease_id', 'DIS0000002')
            ->assertJsonPath('evidence_summary.absent_symptoms.0.symptom_id', 'SYM0000003');
    }

    public function test_unsure_is_unknown_without_score_change(): void
    {
        $this->fixtureWithDiscriminationData();
        $id = $this->question('SYM0000003', 'ไม่แน่ใจได้', 'SYM0000001');
        [, $response] = $this->startAndAnswer($id, 'unsure');
        $response->assertJsonPath('evidence_summary.unknown_symptoms.0.symptom_id', 'SYM0000003')
            ->assertJsonPath('result_status', 'insufficient_evidence')
            ->assertJsonCount(0, 'results');
    }

    public function test_option_evidence_mapping_is_preserved(): void
    {
        $this->fixtureWithDiscriminationData();
        $id = $this->question('SYM0000003', 'คำถาม mapping', 'SYM0000001', answerType: 'single_choice', options: [
            ['option_text' => 'มี', 'option_value' => 'yes', 'answer_effect' => 'present'],
            ['option_text' => 'ไม่มี', 'option_value' => 'no', 'answer_effect' => 'absent'],
        ]);
        $start = $this->start();
        $option = DB::table('adaptive_question_options')->where('adaptive_question_id', $id)->value('id');
        $this->withHeader('X-Session-Token', $start->json('session_token'))
            ->postJson('/api/adaptive-assessments/'.$start->json('assessment_id').'/answer', [
                'question_id' => $id, 'option_ids' => [$option],
            ])->assertOk()->assertJsonFragment([
                'symptom_id' => 'SYM0000003',
                'source' => 'answer',
            ]);
    }

    public function test_best_question_is_recalculated_after_answer(): void
    {
        $this->fixtureWithDiscriminationData();
        $first = $this->question('SYM0000003', 'คำถามแรก', 'SYM0000001');
        $second = $this->question('SYM0000004', 'คำถามถัดไป', 'SYM0000001');
        [, $response] = $this->startAndAnswer($first, 'yes');
        $response->assertJsonPath('question.question_id', $second);
    }

    public function test_no_askable_symptoms_finishes_assessment(): void
    {
        $this->fixtureWithDiscriminationData();
        $id = $this->question('SYM0000003', 'คำถามเดียว', 'SYM0000001');
        [, $response] = $this->startAndAnswer($id, 'yes');
        $response->assertJsonPath('status', 'completed');
    }

    public function test_legacy_max_questions_config_does_not_limit_question_flow(): void
    {
        config(['adaptive_assessment.max_questions' => 1]);
        $this->fixtureWithDiscriminationData();
        $first = $this->question('SYM0000003', 'คำถามแรก', 'SYM0000001');
        $this->question('SYM0000004', 'ไม่ควรถูกถาม', 'SYM0000001');
        [, $response] = $this->startAndAnswer($first, 'yes');
        $response->assertJsonPath('status', 'question');
    }

    public function test_required_safety_question_precedes_discrimination(): void
    {
        $this->fixtureWithDiscriminationData();
        $this->question('SYM0000002', 'คำถามความปลอดภัย', 'SYM0000001', required: true, stage: 'safety');
        $discrimination = $this->question('SYM0000003', 'คำถามแยกโรค', 'SYM0000003');
        $this->start()->assertJsonPath('question.question_id', $discrimination)->assertJsonPath('question.phase', 'discrimination');
    }

    public function test_below_threshold_candidate_is_not_returned_as_a_normal_result(): void
    {
        $this->fixture();
        DB::table('diseases')->update(['minimum_supporting_symptoms' => 2]);
        $this->start()
            ->assertJsonPath('result_status', 'insufficient_evidence')
            ->assertJsonCount(0, 'results');
    }

    public function test_hard_limit_stops_adaptive_questions_with_insufficient_evidence(): void
    {
        config([
            'adaptive_assessment.minimum_clear_answers' => 1,
            'adaptive_assessment.hard_question_limit' => 1,
        ]);
        $this->fixtureWithDiscriminationData();
        DB::table('diseases')->update(['minimum_supporting_symptoms' => 3]);
        $first = $this->question('SYM0000003', 'คำถามแรก', 'SYM0000001');
        $this->question('SYM0000004', 'คำถามที่ไม่ควรถูกถาม', 'SYM0000001');

        [, $response] = $this->startAndAnswer($first, 'no');

        $response->assertJsonPath('status', 'completed')
            ->assertJsonPath('result_status', 'insufficient_evidence')
            ->assertJsonCount(0, 'results');
    }

    public function test_required_question_can_finish_before_hard_limit_stops_adaptive_flow(): void
    {
        config([
            'adaptive_assessment.minimum_clear_answers' => 1,
            'adaptive_assessment.hard_question_limit' => 1,
        ]);
        $this->fixtureWithDiscriminationData();
        $first = $this->question('SYM0000003', 'คำถามบังคับแรก', 'SYM0000001', required: true);
        $second = $this->question('SYM0000004', 'คำถามบังคับที่สอง', 'SYM0000001', priority: 2, required: true);
        DB::table('adaptive_question_rules')->whereIn('adaptive_question_id', [$first, $second])
            ->update(['initial_symptom_id' => 'SYM0000001']);

        [, $response] = $this->startAndAnswer($first, 'no');

        $response->assertJsonPath('status', 'question')
            ->assertJsonPath('question.question_id', $second);
    }

    public function test_history_ai_and_tracking_pipeline_remain_compatible(): void
    {
        $this->app->bind(AiClient::class, FakeAiClient::class);
        $this->fixture();
        $user = User::create(['user_id' => '000000001', 'first_name' => 'Adaptive', 'last_name' => 'User',
            'email' => 'adaptive@example.test', 'password' => 'password']);
        $completed = $this->actingAs($user)->postJson('/api/adaptive-assessments/start', ['symptom_id' => 'SYM0000001'])
            ->assertOk()->assertJsonPath('status', 'completed');
        $id = $completed->json('history_assessment_id');
        $this->assertDatabaseHas('assessments', ['id' => $id, 'user_id' => $user->user_id, 'assessment_type' => 'adaptive']);
        $this->actingAs($user)->postJson("/api/ai/assessments/{$id}/guidance")->assertOk();
        $this->actingAs($user)->postJson("/api/assessments/{$id}/save")->assertOk();
        $this->assertTrue(Assessment::findOrFail($id)->is_saved);
    }

    private function start()
    {
        return $this->postJson('/api/adaptive-assessments/start', ['symptom_id' => 'SYM0000001'])->assertOk();
    }

    private function startAndAnswer(int $questionId, string $answer): array
    {
        $start = $this->start()->assertJsonPath('question.question_id', $questionId);
        $response = $this->withHeader('X-Session-Token', $start->json('session_token'))
            ->postJson('/api/adaptive-assessments/'.$start->json('assessment_id').'/answer', [
                'question_id' => $questionId, 'answer' => $answer,
            ])->assertOk();

        return [$start, $response];
    }

    private function question(string $symptomId, string $text, string $ruleInitial, int $priority = 1,
        bool $required = false, string $stage = 'associated', string $status = 'approved',
        string $answerType = 'yes_no_unsure', array $options = []): int
    {
        $id = DB::table('adaptive_questions')->insertGetId([
            'question_symptom_id' => $symptomId, 'question_text' => $text, 'answer_type' => $answerType,
            'status' => $status, 'evidence_source' => 'Reviewed fixture', 'approved_at' => $status === 'approved' ? now() : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('adaptive_question_symptoms')->insert(['adaptive_question_id' => $id, 'symptom_id' => $symptomId,
            'display_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('adaptive_question_rules')->insert(['initial_symptom_id' => $symptomId, 'adaptive_question_id' => $id,
            'question_stage' => $stage, 'priority' => $priority, 'is_required' => $required, 'status' => '1',
            'evidence_source' => 'Reviewed route', 'evidence_status' => 'reviewed', 'created_at' => now(), 'updated_at' => now()]);
        foreach ($options as $index => $option) {
            DB::table('adaptive_question_options')->insert(['adaptive_question_id' => $id,
                'option_text' => $option['option_text'], 'option_value' => $option['option_value'],
                'target_symptom_id' => $option['target_symptom_id'] ?? $symptomId, 'answer_effect' => $option['answer_effect'],
                'display_order' => $index, 'status' => '1', 'created_at' => now(), 'updated_at' => now()]);
        }

        return $id;
    }

    private function fixture(): void
    {
        DB::table('symptom_categories')->insert(['symptom_category_id' => 'SC0001', 'category_name' => 'Test', 'status' => '1']);
        DB::table('main_symptoms')->insert([
            ['symptom_id' => 'SYM0000001', 'symptom_name' => 'อาการเริ่มต้น', 'symptom_category_id' => 'SC0001', 'status' => '1'],
            ['symptom_id' => 'SYM0000002', 'symptom_name' => 'อาการกรอบ', 'symptom_category_id' => 'SC0001', 'status' => '1'],
            ['symptom_id' => 'SYM0000003', 'symptom_name' => 'อาการแบ่งครึ่ง', 'symptom_category_id' => 'SC0001', 'status' => '1'],
            ['symptom_id' => 'SYM0000004', 'symptom_name' => 'อาการจำเพาะ', 'symptom_category_id' => 'SC0001', 'status' => '1'],
            ['symptom_id' => 'SYM0000005', 'symptom_name' => 'อาการไม่เกี่ยวข้อง', 'symptom_category_id' => 'SC0001', 'status' => '1'],
        ]);
        DB::table('disease_categories')->insert(['disease_category_id' => 'DC0001', 'category_name' => 'Test', 'status' => '1']);
        DB::table('diseases')->insert([
            ['disease_id' => 'DIS0000001', 'disease_name' => 'โรคหนึ่ง', 'disease_category_id' => 'DC0001', 'status' => '1'],
            ['disease_id' => 'DIS0000002', 'disease_name' => 'โรคสอง', 'disease_category_id' => 'DC0001', 'status' => '1'],
        ]);
        DB::table('disease_symptoms')->insert([$this->ds('DIS0000001', 'SYM0000001'),
            $this->ds('DIS0000002', 'SYM0000001'), $this->ds('DIS0000001', 'SYM0000002')]);
    }

    private function fixtureWithDiscriminationData(): void
    {
        $this->fixture();
        DB::table('diseases')->insert([
            ['disease_id' => 'DIS0000003', 'disease_name' => 'โรคสาม', 'disease_category_id' => 'DC0001', 'status' => '1'],
            ['disease_id' => 'DIS0000004', 'disease_name' => 'โรคสี่', 'disease_category_id' => 'DC0001', 'status' => '1'],
        ]);
        DB::table('disease_symptoms')->insert([$this->ds('DIS0000003', 'SYM0000001'),
            $this->ds('DIS0000004', 'SYM0000001'), $this->ds('DIS0000001', 'SYM0000003'),
            $this->ds('DIS0000002', 'SYM0000003'), $this->ds('DIS0000001', 'SYM0000004')]);
    }

    private function ds(string $diseaseId, string $symptomId): array
    {
        return ['disease_id' => $diseaseId, 'symptom_id' => $symptomId, 'evidence_status' => 'reviewed'];
    }
}
