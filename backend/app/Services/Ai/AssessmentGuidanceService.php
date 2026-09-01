<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use App\Models\Assessment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AssessmentGuidanceService
{
    private const ACTIONS = ['find_facility', 'save_result', 'start_follow_up', 'start_related_assessment'];

    public function __construct(private readonly AiClient $client) {}

    public function generate(Assessment $assessment): array
    {
        $assessment->loadMissing([
            'symptom',
            'answers.box',
            'answers.choice',
            'clarificationSessions.questions.answer.choice',
            'results.diseases',
            'results.rule.nextDiagrams',
        ]);
        $actions = ['find_facility', 'save_result', 'start_follow_up'];
        if ($assessment->results->contains(fn ($result) => $result->rule?->nextDiagrams?->isNotEmpty())) {
            $actions[] = 'start_related_assessment';
        }

        $result = $this->client->generateStructured(
            'สรุปข้อมูลการประเมินที่ backend คำนวณแล้วเป็นภาษาไทยง่าย กระชับ และเป็นภาษาคนทั่วไป โดยอ้างอิงเฉพาะข้อมูลที่ส่งให้ ห้ามแสดงรหัสภายใน รหัสโรค หรือตัวอักษรระดับ R/P/Y/G/W ให้ถอดความเป็นสิ่งที่ผู้ใช้ควรทำ ห้ามเปลี่ยนผล ระดับความเร่งด่วน กรอบเวลา โรค หรือคำเตือน ห้ามกล่าวเหมือนยืนยันว่าผู้ใช้เป็นโรค ห้ามวินิจฉัย ห้ามสั่งยา ระบุชื่อยา หรือขนาดยา การดูแลเบื้องต้นต้องถอดความจาก rule_recommendation เท่านั้น ใช้ได้เฉพาะ action ที่ส่งให้',
            [
                'symptom' => $assessment->symptom?->symptom_name,
                'answered_questions' => $assessment->answers->map(fn ($answer) => [
                    'question' => $answer->box?->question_text,
                    'answer' => $answer->choice?->choice_text,
                ])->values()->all(),
                'clarification_observations' => $assessment->clarificationSessions
                    ->flatMap(fn ($session) => $session->questions->map(fn ($question) => [
                        'question' => $question->question_text,
                        'answer' => $question->answer?->choice?->choice_text,
                    ]))->filter(fn ($item) => $item['answer'] !== null)->values()->all(),
                'results' => $assessment->results->map(fn ($item) => [
                    'urgency_level' => $item->urgency_level,
                    'time_frame' => $item->time_frame,
                    'rule_recommendation' => $item->recommendation,
                    'diseases' => $item->diseases->pluck('disease_name')->values()->all(),
                ])->values()->all(),
                'allowed_actions' => $actions,
            ],
            $this->schema(),
        );

        Validator::make($result, [
            'summary' => ['required', 'string', 'max:1500'],
            'assessment_overview' => ['present', 'array', 'max:8'],
            'assessment_overview.*' => ['required', 'string', 'max:300'],
            'self_care' => ['present', 'array', 'max:6'],
            'self_care.*' => ['required', 'string', 'max:300'],
            'warning_signs' => ['required', 'array', 'max:6'],
            'warning_signs.*' => ['required', 'string', 'max:300'],
            'next_steps' => ['required', 'array', 'max:4'],
            'next_steps.*.action' => ['required', Rule::in($actions)],
            'next_steps.*.label' => ['required', 'string', 'max:200'],
            'disclaimer' => ['required', 'string', 'max:500'],
        ])->validate();

        return $result;
    }

    private function schema(): array
    {
        return ['name' => 'assessment_guidance', 'schema' => [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['summary', 'assessment_overview', 'self_care', 'warning_signs', 'next_steps', 'disclaimer'],
            'properties' => [
                'summary' => ['type' => 'string'],
                'assessment_overview' => ['type' => 'array', 'maxItems' => 8, 'items' => ['type' => 'string']],
                'self_care' => ['type' => 'array', 'maxItems' => 6, 'items' => ['type' => 'string']],
                'warning_signs' => ['type' => 'array', 'maxItems' => 6, 'items' => ['type' => 'string']],
                'next_steps' => ['type' => 'array', 'maxItems' => 4, 'items' => [
                    'type' => 'object', 'additionalProperties' => false,
                    'required' => ['action', 'label'],
                    'properties' => [
                        'action' => ['type' => 'string', 'enum' => self::ACTIONS],
                        'label' => ['type' => 'string'],
                    ],
                ]],
                'disclaimer' => ['type' => 'string'],
            ],
        ]];
    }
}
