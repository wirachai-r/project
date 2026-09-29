<?php

namespace App\Http\Resources\Client;

use App\Support\ImageStorage;
use App\Support\NotificationContent;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'urgency_level' => $this->urgency_level,
            'should_see_doctor' => $this->should_see_doctor,
            'recommendation' => $this->recommendation,
            'rule_id' => $this->rule_id,
            // ค่าที่ผู้ดูแลกำหนดไว้ในผลลัพธ์ปลายทาง
            'time_frame' => $this->whenLoaded('rule', fn () => $this->rule?->time_frame),
            'time_frame_en' => $this->whenLoaded('rule', fn () => $this->rule?->time_frame_en),
            'medical_reference' => $this->whenLoaded('rule', fn () => $this->rule?->medical_reference),
            'next_diagrams' => $this->whenLoaded('rule', function () {
                if (! $this->rule || ! $this->rule->relationLoaded('nextDiagrams')) {
                    return [];
                }

                return $this->rule->nextDiagrams->map(fn ($diagram) => [
                    'diagram_id' => $diagram->diagram_id,
                    'diagram_name' => $diagram->diagram_name,
                    'diagram_name_en' => $diagram->diagram_name_en,
                    'prompt_text' => $diagram->pivot->prompt_text,
                    'target_box_id' => $diagram->pivot->target_box_id,
                    'order' => $diagram->pivot->display_order,
                ]);
            }),
            'diseases' => $this->whenLoaded('diseases', function () use ($request) {
                return $this->diseases
                    ->sortBy(fn ($disease) => $disease->pivot->display_order ?? 0)
                    ->values()
                    ->map(fn ($disease) => [
                        'disease_id' => $disease->disease_id,
                        'disease_name' => $disease->disease_name,
                        'disease_name_en' => $disease->disease_name_en,
                        'order' => $disease->pivot->display_order ?? 0,
                        'match_percent' => $disease->pivot->match_percent,
                        'supporting_symptom_count' => $disease->pivot->supporting_symptom_count,
                        'evaluated_symptom_count' => $disease->pivot->evaluated_symptom_count,
                        'meets_minimum_support' => $disease->pivot->supporting_symptom_count >= max(
                            1,
                            (int) ($disease->minimum_supporting_symptoms ?? 1),
                        ),
                        // ข้อมูลโรคแบบละเอียด สำหรับแสดงใน accordion หน้าผลลัพธ์
                        // ไม่ต้องเรียก GET /diseases/{id} ซ้ำอีกรอบ
                        'description' => NotificationContent::resolveImageUrls($disease->description, $request),
                        'cause' => NotificationContent::resolveImageUrls($disease->cause, $request),
                        'symptom_description' => NotificationContent::resolveImageUrls($disease->symptom_description, $request),
                        'complications' => NotificationContent::resolveImageUrls($disease->complications, $request),
                        'diagnosis' => NotificationContent::resolveImageUrls($disease->diagnosis, $request),
                        'medical_treatment' => NotificationContent::resolveImageUrls($disease->medical_treatment, $request),
                        'references' => $disease->references ?? [],
                        'self_care' => NotificationContent::resolveImageUrls($disease->self_care, $request),
                        'when_to_see_doctor' => NotificationContent::resolveImageUrls($disease->when_to_see_doctor, $request),
                        'prevention' => NotificationContent::resolveImageUrls($disease->prevention, $request),
                        'recommendations' => NotificationContent::resolveImageUrls($disease->recommendations, $request),
                        'disease_image' => $disease->disease_image
                            ? ImageStorage::disk()->url($disease->disease_image)
                            : null,
                        'reference' => $disease->reference,
                    ]);
            }),
        ];
    }
}
