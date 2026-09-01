<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use App\Models\QuestionBox;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AssessmentClarificationService
{
    public function __construct(private readonly AiClient $client) {}

    public function clarify(
        QuestionBox $box,
        ?string $message = null,
        array $previousClarifications = [],
    ): array {
        $choices = $box->choices()->where('status', '1')->orderBy('order')->get();
        $result = $this->client->generateStructured(
            'ช่วยอธิบายคำถามสุขภาพเดิมเป็นภาษาไทยที่สังเกตได้ง่าย ห้ามเพิ่มเกณฑ์ โรค การวินิจฉัย ยา ความเร่งด่วน หรือคำถามจากแผนภูมิอื่น สำหรับคำถามแบบสองคำตอบให้สร้างตัวเลือกตามลำดับ: ข้อ 1 สนับสนุนคำตอบแรก ข้อ 2 สนับสนุนคำตอบที่สอง ข้อ 3 ยังสังเกตไม่ได้ ห้ามสลับลำดับ และอย่าตัดสินคำตอบแทนผู้ใช้',
            [
                'question' => $box->question_text,
                'detail' => $box->detail,
                'answer_choices' => $choices->map(fn ($choice) => [
                    'id' => $choice->choice_id,
                    'text' => $choice->choice_text,
                ])->values()->all(),
                'user_message' => $message,
                'previous_clarifications' => $previousClarifications,
                'clarification_rule' => 'Ask a different observable follow-up from every previous_clarifications question. Use prior answers to narrow the uncertainty; never repeat or merely paraphrase a previous question.',
                'mapping_rule' => 'For each helper choice, maps_to_choice_id must be an exact answer_choices id when it supports that answer; otherwise use an empty string.',
            ],
            $this->schema(),
        );

        $validChoiceIds = $choices->pluck('choice_id')->all();
        $isBinaryQuestion = count($validChoiceIds) === 2;
        $result['requires_user_confirmation'] = true;
        $result['choices'] = collect($result['choices'] ?? [])->map(function ($choice, $index) use ($validChoiceIds, $isBinaryQuestion) {
            if ($isBinaryQuestion) {
                return [
                    'id' => (string) ($choice['id'] ?? ''),
                    'label' => (string) ($choice['label'] ?? ''),
                    'maps_to' => $index === 0 ? 'yes' : ($index === 1 ? 'no' : 'requires_user_choice'),
                    'maps_to_choice_id' => $index < 2 ? $validChoiceIds[$index] : '',
                ];
            }
            $mapsTo = match ($choice['maps_to'] ?? null) {
                'yes' => 'yes',
                'no' => 'no',
                default => 'requires_user_choice',
            };
            $mapsToChoiceId = (string) ($choice['maps_to_choice_id'] ?? '');
            if ($mapsTo === 'requires_user_choice' || ! in_array($mapsToChoiceId, $validChoiceIds, true)) {
                $mapsTo = 'requires_user_choice';
                $mapsToChoiceId = '';
            }

            return [
                'id' => (string) ($choice['id'] ?? ''),
                'label' => (string) ($choice['label'] ?? ''),
                'maps_to' => $mapsTo,
                'maps_to_choice_id' => $mapsToChoiceId,
            ];
        })->all();

        Validator::make($result, [
            'question_text' => ['required', 'string', 'max:500'],
            'explanation' => ['required', 'string', 'max:1000'],
            'choices' => ['required', 'array', 'min:3', 'max:5'],
            'choices.*.id' => ['required', 'string', 'max:80', 'distinct'],
            'choices.*.label' => ['required', 'string', 'max:300'],
            'choices.*.maps_to' => ['required', Rule::in(['yes', 'no', 'requires_user_choice'])],
            'choices.*.maps_to_choice_id' => [
                'present',
                'nullable',
                'string',
                Rule::in([...$choices->pluck('choice_id')->all(), '']),
            ],
            'requires_user_confirmation' => ['required', 'boolean', Rule::in([true])],
        ])->validate();

        if (! collect($result['choices'])->contains('maps_to', 'requires_user_choice')) {
            $result['choices'][] = [
                'id' => 'cannot_observe',
                'label' => 'ยังสังเกตไม่ได้ในตอนนี้',
                'maps_to' => 'requires_user_choice',
                'maps_to_choice_id' => '',
            ];
            $result['choices'] = array_slice($result['choices'], 0, 5);
        }

        return $result;
    }

    private function schema(): array
    {
        return ['name' => 'question_clarification', 'schema' => [
            'type' => 'object', 'additionalProperties' => false,
            'required' => ['question_text', 'explanation', 'choices', 'requires_user_confirmation'],
            'properties' => [
                'question_text' => ['type' => 'string'],
                'explanation' => ['type' => 'string'],
                'choices' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 5, 'items' => [
                    'type' => 'object', 'additionalProperties' => false,
                    'required' => ['id', 'label', 'maps_to', 'maps_to_choice_id'],
                    'properties' => [
                        'id' => ['type' => 'string'], 'label' => ['type' => 'string'],
                        'maps_to' => ['type' => 'string', 'enum' => ['yes', 'no', 'requires_user_choice']],
                        'maps_to_choice_id' => ['type' => 'string'],
                    ],
                ]],
                'requires_user_confirmation' => ['type' => 'boolean', 'const' => true],
            ],
        ]];
    }
}
