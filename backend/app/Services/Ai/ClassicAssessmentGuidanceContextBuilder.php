<?php

namespace App\Services\Ai;

use App\Models\Assessment;

class ClassicAssessmentGuidanceContextBuilder extends AssessmentGuidanceContextBuilder
{
    public function build(Assessment $assessment): array
    {
        $actions = ['find_facility', 'save_result', 'start_follow_up'];
        if ($assessment->results->contains(fn ($result) => $result->rule?->nextDiagrams?->isNotEmpty())) {
            $actions[] = 'start_related_assessment';
        }

        return [
            'instructions' => 'นี่คือผลประเมินแบบแผนภูมิที่คำนวณจากกฎของระบบ '
                .'ให้คงระดับความเร่งด่วน กรอบเวลา ผลปลายทาง และคำแนะนำจากกฎตามข้อมูลที่ส่งให้ ห้ามคำนวณหรือเปลี่ยนผลใหม่ '
                .'self_care ต้องถอดความจาก rule_recommendation, disease_context.self_care หรือ disease_context.recommendations เท่านั้น '
                .'warning_signs ต้องถอดความจาก rule_note, rule_recommendation หรือ disease_context.when_to_see_doctor เท่านั้น หากไม่มีข้อมูลอ้างอิงให้คืนรายการว่าง '
                .ServiceAnalysisRules::guidance(),
            'input' => [
                'assessment_mode' => 'classic',
                'symptom' => $this->symptom($assessment),
                'answered_questions' => $assessment->answers->map(fn ($answer) => [
                    'question' => $answer->box?->question_text,
                    'question_detail' => $this->plainText($answer->box?->detail),
                    'answer' => $answer->choice?->choice_text,
                ])->values()->all(),
                'clarification_observations' => $this->clarificationObservations($assessment),
                'results' => $assessment->results->map(fn ($item) => [
                    'urgency_level' => $item->urgency_level,
                    'should_see_doctor' => $item->should_see_doctor,
                    'time_frame' => $item->rule?->time_frame,
                    'rule_note' => $this->plainText($item->rule?->note),
                    'rule_recommendation' => $this->plainText($item->recommendation),
                    'disease_context' => $this->diseaseContext($item->diseases),
                ])->values()->all(),
                'allowed_actions' => $actions,
            ],
            'actions' => $actions,
        ];
    }
}
