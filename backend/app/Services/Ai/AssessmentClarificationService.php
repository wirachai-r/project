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
        array $assessmentContext = [],
    ): array {
        $choices = $box->choices()->where('status', '1')->orderBy('order')->get();
        $attempt = min(count($previousClarifications) + 1, (int) config('ai.max_clarification_attempts'));
        $isFinalAttempt = $attempt >= (int) config('ai.max_clarification_attempts');
        $result = $this->client->generateStructured(
            'คุณเป็นผู้ช่วยทำให้คำถามสุขภาพเดิมเข้าใจและสังเกตได้ง่ายขึ้น ไม่ใช่ผู้วินิจฉัย '
            .'ถามเพียงหนึ่งประเด็นต่อรอบ ใช้ภาษาไทยสั้น เป็นกลาง และอ้างอิงเฉพาะข้อมูลที่ส่งให้ '
            .'ห้ามเพิ่มอาการ เกณฑ์ ตัวเลข ระยะเวลา โรค การวินิจฉัย ยา ความเร่งด่วน หรือคำถามจากแผนภูมิอื่น '
            .'ห้ามเดา ตัดสิน หรือตีความความไม่แน่ใจว่าเป็นคำตอบใด '
            .'ข้อความของตัวเลือกปรับให้เหมาะกับคำถามได้ แต่ชนิดและการจับคู่คำตอบต้องเป็นไปตาม option_rules และ mapping_rule เท่านั้น'
            .CoreAnalysisRules::instructions()
            .ServiceAnalysisRules::clarification(),
            [
                'attempt' => $attempt,
                'max_attempts' => (int) config('ai.max_clarification_attempts'),
                'is_final_attempt' => $isFinalAttempt,
                'round_strategy' => $this->roundStrategy($attempt),
                'question' => $box->question_text,
                'detail' => $box->detail,
                'answer_choices' => $choices->map(fn ($choice) => [
                    'id' => $choice->choice_id,
                    'text' => $choice->choice_text,
                ])->values()->all(),
                'user_message' => $message,
                'assessment_context' => $assessmentContext,
                'previous_clarifications' => $previousClarifications,
                'clarification_rule' => 'สร้างคำถามช่วยที่ต่างจากทุกคำถามใน previous_clarifications อย่างมีสาระ ใช้คำตอบก่อนหน้าเพื่อลดความไม่แน่ใจ ห้ามถามซ้ำหรือเพียงเปลี่ยนถ้อยคำ และห้ามสร้างเกณฑ์ทางการแพทย์ใหม่',
                'option_rules' => count($choices) === 2
                    ? 'สร้าง 3 ตัวเลือกเท่านั้น: ตัวเลือกที่ 1 เป็นข้อความสังเกตได้ซึ่งสนับสนุน answer_choices ลำดับแรก ตัวเลือกที่ 2 สนับสนุนลำดับที่สอง และตัวเลือกที่ 3 ต้องหมายถึงยังสังเกตหรือระบุไม่ได้ ห้ามสลับลำดับ'
                    : 'สร้าง 3 ถึง 5 ตัวเลือกตามบริบท ใช้ข้อความที่ผู้ใช้สังเกตและแยกจากกันได้ชัดเจน จับคู่กับ answer_choices เฉพาะเมื่อมีหลักฐานตรงกัน และต้องมีหนึ่งตัวเลือกสำหรับยังสังเกตหรือระบุไม่ได้เสมอ',
                'mapping_rule' => 'เมื่อคำตอบช่วยสนับสนุนคำตอบหลักอย่างชัดเจน maps_to_choice_id ต้องตรงกับ id ใน answer_choices เท่านั้น หากยังไม่ชัดเจนให้ maps_to เป็น requires_user_choice และ maps_to_choice_id เป็นสตริงว่าง ห้ามตีความ unknown เป็น no',
                'final_attempt_rule' => $isFinalAttempt
                    ? 'นี่คือรอบสุดท้าย หากผู้ใช้ยังระบุไม่ได้ ให้คงตัวเลือก requires_user_choice ไว้เพื่อให้ระบบบันทึกเป็น unresolved ห้ามเดาคำตอบแทนผู้ใช้'
                    : 'หากยังระบุไม่ได้ ให้ใช้ requires_user_choice เพื่อให้ระบบสามารถถามช่วยในรอบถัดไปได้',
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
            $unknownChoice = [
                'id' => 'cannot_observe',
                'label' => 'ยังสังเกตไม่ได้ในตอนนี้',
                'maps_to' => 'requires_user_choice',
                'maps_to_choice_id' => '',
            ];
            if (count($result['choices']) >= 5) {
                $result['choices'][4] = $unknownChoice;
            } else {
                $result['choices'][] = $unknownChoice;
            }
        }

        return $result;
    }

    private function roundStrategy(int $attempt): string
    {
        return match ($attempt) {
            1 => 'อธิบายความหมายของคำถามเดิมใหม่ด้วยภาษาที่ง่ายขึ้น โดยไม่เพิ่มรายละเอียดทางการแพทย์ที่ไม่มีในข้อมูลต้นทาง',
            2 => 'เปลี่ยนมุมถามเป็นลักษณะที่ผู้ใช้สังเกตได้ด้วยตนเอง โดยใช้ข้อมูลจากคำตอบรอบแรกและไม่ยกเกณฑ์ใหม่',
            default => 'ถามแยกความแตกต่างที่เหลืออยู่เป็นครั้งสุดท้ายอย่างกระชับ และเปิดทางให้ตอบว่ายังระบุไม่ได้โดยไม่คาดเดา',
        };
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
