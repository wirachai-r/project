<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;

class FakeAiClient implements AiClient
{
    public function generateStructured(string $instructions, array $input, array $schema): array
    {
        return match ($schema['name'] ?? null) {
            'question_clarification' => [
                'question_text' => 'คำถามช่วยรอบ '.(count($input['previous_clarifications'] ?? []) + 1).': ข้อใดใกล้เคียงกับสิ่งที่คุณสังเกตได้มากที่สุด?',
                'explanation' => 'เลือกข้อความที่ใกล้กับสิ่งที่สังเกตได้มากที่สุด แล้วระบบจะให้คุณยืนยันคำตอบอีกครั้ง',
                'choices' => collect($input['answer_choices'] ?? [])
                    ->take(4)
                    ->map(fn ($choice, $index) => [
                        'id' => 'existing_'.($index + 1),
                        'label' => (string) ($choice['text'] ?? ''),
                        'maps_to' => $index === 0 ? 'yes' : ($index === 1 ? 'no' : 'requires_user_choice'),
                        'maps_to_choice_id' => (string) ($choice['id'] ?? ''),
                    ])->push([
                        'id' => 'cannot_observe',
                        'label' => 'ยังสังเกตไม่ได้ในตอนนี้',
                        'maps_to' => 'requires_user_choice',
                        'maps_to_choice_id' => '',
                    ])->take(5)->values()->all(),
                'requires_user_confirmation' => true,
            ],
            'assessment_guidance' => [
                'summary' => 'โปรดอ่านผลการประเมินและคำแนะนำหลักจากระบบด้านบน',
                'assessment_overview' => collect($input['answered_questions'] ?? [])
                    ->take(4)->map(fn ($item) => "{$item['question']}: {$item['answer']}")->values()->all(),
                'self_care' => collect($input['results'] ?? [])->pluck('rule_recommendation')
                    ->filter()->unique()->take(4)->values()->all(),
                'warning_signs' => ['หากอาการรุนแรงขึ้นหรือไม่แน่ใจ ควรติดต่อสถานพยาบาล'],
                'next_steps' => collect($input['allowed_actions'] ?? [])->take(3)->map(fn ($action) => [
                    'action' => $action,
                    'label' => $this->actionLabel($action),
                ])->values()->all(),
                'disclaimer' => 'ข้อความนี้ช่วยสรุปข้อมูล ไม่ใช่การวินิจฉัยทางการแพทย์',
            ],
            'health_trend_summary' => [
                'summary' => 'สรุปจากข้อมูลสุขภาพที่บันทึกไว้ในช่วงเวลาที่เลือก',
                'observations' => ['ข้อมูลอาจยังไม่เพียงพอสำหรับสรุปแนวโน้มที่แน่นอน'],
                'self_care' => [],
                'warning_signs' => [],
                'allowed_actions' => collect($input['allowed_actions'] ?? [])->take(2)->values()->all(),
                'disclaimer' => 'แนวโน้มนี้เป็นการสรุปข้อมูล ไม่ใช่การวินิจฉัยทางการแพทย์',
            ],
            default => [],
        };
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'find_facility' => 'ค้นหาสถานพยาบาล',
            'save_result' => 'บันทึกผลการประเมิน',
            'start_follow_up' => 'เริ่มติดตามอาการ',
            'start_related_assessment' => 'ทำแบบประเมินที่เกี่ยวข้อง',
            default => 'ดูข้อมูลเพิ่มเติม',
        };
    }
}
