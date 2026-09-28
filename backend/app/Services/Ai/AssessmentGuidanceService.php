<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use App\Models\Assessment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AssessmentGuidanceService
{
    public const VERSION = 5;

    private const ACTIONS = ['find_facility', 'save_result', 'start_follow_up', 'start_related_assessment'];

    public function __construct(
        private readonly AiClient $client,
        private readonly ClassicAssessmentGuidanceContextBuilder $classicContext,
        private readonly AdaptiveAssessmentGuidanceContextBuilder $adaptiveContext,
    ) {}

    public function generate(Assessment $assessment): array
    {
        $assessment->loadMissing([
            'symptom',
            'answers.box',
            'answers.choice',
            'clarificationSessions.questions.answer.choice',
            'results.diseases',
            'results.rule.nextDiagrams',
            'adaptiveAssessment.answers.symptom',
        ]);
        $guidanceContext = $assessment->assessment_type === 'adaptive'
            ? $this->adaptiveContext->build($assessment)
            : $this->classicContext->build($assessment);
        $actions = $guidanceContext['actions'];

        $result = $this->client->generateStructured(
            'สรุปข้อมูลการประเมินที่ backend คำนวณแล้วเป็นภาษาไทยง่าย กระชับ และเป็นภาษาคนทั่วไป โดยอ้างอิงเฉพาะข้อมูลที่ส่งให้ '
            .'ห้ามแสดงรหัสภายใน รหัสโรค หรือตัวอักษรระดับ R/P/Y/G/W ให้ถอดความเป็นสิ่งที่ผู้ใช้ควรทำ '
            .'ห้ามเปลี่ยนผล ระดับความเร่งด่วน กรอบเวลา โรค หรือคำเตือน ห้ามกล่าวเหมือนยืนยันว่าผู้ใช้เป็นโรค ให้เรียกว่าโรคหรือภาวะที่ผลประเมินระบุว่าอาจเกี่ยวข้อง '
            .'ห้ามวินิจฉัย ห้ามสั่งยา ระบุชื่อยา หรือขนาดยา '
            .'summary และ assessment_overview ต้องสรุปจาก symptom, answered_questions, clarification_observations และ results เท่านั้น '
            .'next_steps ใช้ได้เฉพาะ action ที่ส่งให้ ห้ามสร้าง action ใหม่ และ disclaimer ต้องระบุว่าเป็นข้อมูลคัดกรอง ไม่ใช่การวินิจฉัย'
            .CoreAnalysisRules::instructions()
            .$guidanceContext['instructions'],
            $guidanceContext['input'],
            $this->schema(),
        );

        Validator::make($result, [
            'summary' => ['required', 'string', 'max:1500'],
            'assessment_overview' => ['present', 'array', 'max:8'],
            'assessment_overview.*' => ['required', 'string', 'max:300'],
            'self_care' => ['present', 'array', 'max:6'],
            'self_care.*' => ['required', 'string', 'max:300'],
            'warning_signs' => ['present', 'array', 'max:6'],
            'warning_signs.*' => ['required', 'string', 'max:300'],
            'next_steps' => ['required', 'array', 'max:4'],
            'next_steps.*.action' => ['required', Rule::in($actions)],
            'next_steps.*.label' => ['required', 'string', 'max:200'],
            'disclaimer' => ['required', 'string', 'max:500'],
        ])->validate();

        $result['assessment_overview'] = $this->uniqueItems($result['assessment_overview']);
        $result['self_care'] = $this->uniqueItems($result['self_care']);
        $selfCareKeys = collect($result['self_care'])
            ->map(fn (string $item) => mb_strtolower(trim($item)))
            ->all();
        $result['warning_signs'] = collect($this->uniqueItems($result['warning_signs']))
            ->reject(fn (string $item) => in_array(mb_strtolower(trim($item)), $selfCareKeys, true))
            ->values()
            ->all();

        return $result;
    }

    private function uniqueItems(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique(fn (string $item) => mb_strtolower($item))
            ->values()
            ->all();
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
