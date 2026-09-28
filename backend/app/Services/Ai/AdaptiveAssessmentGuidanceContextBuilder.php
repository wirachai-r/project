<?php

namespace App\Services\Ai;

use App\Models\Assessment;

class AdaptiveAssessmentGuidanceContextBuilder extends AssessmentGuidanceContextBuilder
{
    public function build(Assessment $assessment): array
    {
        $actions = ['find_facility', 'save_result', 'start_follow_up'];

        return [
            'instructions' => 'นี่คือผลประเมินแบบปรับตามคำตอบ ซึ่งแสดงเพียงภาวะที่มีอาการร่วมสอดคล้อง '
                .'ระบบนี้ไม่ได้ประเมินระดับความเร่งด่วน โอกาสเป็นโรค ความเสี่ยง หรือผลจากแผนภูมิ ห้ามสร้างหรือตีความข้อมูลเหล่านี้ '
                .'ห้ามแปลงจำนวนอาการที่สอดคล้องเป็นเปอร์เซ็นต์ ระดับสูง/กลาง/ต่ำ หรือความน่าจะเป็น '
                .'กล่าวถึงจำนวน supporting_symptom_count จาก evaluated_symptom_count ได้ตามค่าที่ส่งให้เท่านั้น '
                .'self_care ใช้ได้เฉพาะ disease_context.self_care หรือ disease_context.recommendations '
                .'warning_signs ใช้ได้เฉพาะ disease_context.when_to_see_doctor หากไม่มีข้อมูลอ้างอิงให้คืนรายการว่าง '
                .ServiceAnalysisRules::adaptiveGuidance(),
            'input' => [
                'assessment_mode' => 'adaptive',
                'symptom' => $this->symptom($assessment),
                'answered_questions' => $assessment->adaptiveAssessment?->answers->map(fn ($answer) => [
                    'question' => 'มีอาการ'.($answer->symptom?->symptom_name ?? 'ที่สอบถาม').'ร่วมด้วยหรือไม่?',
                    'question_detail' => null,
                    'answer' => match ($answer->answer) {
                        'yes' => 'ใช่',
                        'no' => 'ไม่ใช่',
                        default => 'ไม่แน่ใจ',
                    },
                ])->values()->all() ?? [],
                'clarification_observations' => [],
                'results' => $assessment->results->map(fn ($item) => [
                    'disease_context' => $this->diseaseContext($item->diseases, true),
                ])->values()->all(),
                'allowed_actions' => $actions,
            ],
            'actions' => $actions,
        ];
    }
}
