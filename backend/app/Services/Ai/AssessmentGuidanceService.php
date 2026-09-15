<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use App\Models\Assessment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssessmentGuidanceService
{
    public const VERSION = 4;

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
            'สรุปข้อมูลการประเมินที่ backend คำนวณแล้วเป็นภาษาไทยง่าย กระชับ และเป็นภาษาคนทั่วไป โดยอ้างอิงเฉพาะข้อมูลที่ส่งให้ '
            .'ห้ามแสดงรหัสภายใน รหัสโรค หรือตัวอักษรระดับ R/P/Y/G/W ให้ถอดความเป็นสิ่งที่ผู้ใช้ควรทำ '
            .'ห้ามเปลี่ยนผล ระดับความเร่งด่วน กรอบเวลา โรค หรือคำเตือน ห้ามกล่าวเหมือนยืนยันว่าผู้ใช้เป็นโรค ให้เรียกว่าโรคหรือภาวะที่ผลประเมินระบุว่าอาจเกี่ยวข้อง '
            .'ห้ามวินิจฉัย ห้ามสั่งยา ระบุชื่อยา หรือขนาดยา '
            .'summary และ assessment_overview ต้องสรุปจาก symptom, answered_questions, clarification_observations และ results เท่านั้น '
            .'self_care ต้องถอดความจาก rule_recommendation, disease_context.self_care หรือ disease_context.recommendations เท่านั้น '
            .'warning_signs ต้องถอดความจาก rule_note, rule_recommendation หรือ disease_context.when_to_see_doctor เท่านั้น หากไม่มีข้อมูลอ้างอิงให้คืนรายการว่าง '
            .'next_steps ใช้ได้เฉพาะ action ที่ส่งให้ ห้ามสร้าง action ใหม่ และ disclaimer ต้องระบุว่าเป็นข้อมูลคัดกรอง ไม่ใช่การวินิจฉัย'
            .CoreAnalysisRules::instructions()
            .ServiceAnalysisRules::guidance(),
            [
                'symptom' => [
                    'name' => $assessment->symptom?->symptom_name,
                    'description' => $this->plainText($assessment->symptom?->description),
                ],
                'answered_questions' => $assessment->answers->map(fn ($answer) => [
                    'question' => $answer->box?->question_text,
                    'question_detail' => $this->plainText($answer->box?->detail),
                    'answer' => $answer->choice?->choice_text,
                ])->values()->all(),
                'clarification_observations' => $assessment->clarificationSessions
                    ->flatMap(fn ($session) => $session->questions->map(fn ($question) => [
                        'question' => $question->question_text,
                        'answer' => $question->answer?->choice?->choice_text,
                    ]))->filter(fn ($item) => $item['answer'] !== null)->values()->all(),
                'results' => $assessment->results->map(fn ($item) => [
                    'urgency_level' => $item->urgency_level,
                    'should_see_doctor' => $item->should_see_doctor,
                    'time_frame' => $item->rule?->time_frame,
                    'rule_note' => $this->plainText($item->rule?->note),
                    'rule_recommendation' => $this->plainText($item->recommendation),
                    'disease_context' => $item->diseases->map(fn ($disease) => [
                        'name' => $disease->disease_name,
                        'description' => $this->plainText($disease->description),
                        'symptom_description' => $this->plainText($disease->symptom_description),
                        'self_care' => $this->plainText($disease->self_care),
                        'when_to_see_doctor' => $this->plainText($disease->when_to_see_doctor),
                        'recommendations' => $this->plainText($disease->recommendations),
                    ])->values()->all(),
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

    private function plainText(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $plain = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;

        return Str::limit(trim($plain), 2000, '…');
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
