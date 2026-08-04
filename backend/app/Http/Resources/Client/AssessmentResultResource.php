<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'urgency_level'      => $this->urgency_level,
            'should_see_doctor'  => $this->should_see_doctor,
            'recommendation'     => $this->recommendation,
            'rule_id'            => $this->rule_id,
            'next_diagrams'      => $this->whenLoaded('rule', function () {
                if (!$this->rule->relationLoaded('nextDiagrams')) {
                    return [];
                }

                return $this->rule->nextDiagrams->map(fn($diagram) => [
                    'diagram_id'      => $diagram->diagram_id,
                    'diagram_name'    => $diagram->diagram_name,
                    'diagram_name_en' => $diagram->diagram_name_en,
                    'prompt_text'     => $diagram->pivot->prompt_text,
                    'order'           => $diagram->pivot->display_order,
                ]);
            }),
            'diseases'           => $this->whenLoaded('diseases', function () {
                return $this->diseases
                    ->sortBy(fn ($disease) => $disease->pivot->display_order ?? 0)
                    ->values()
                    ->map(fn ($disease) => [
                        'disease_id'          => $disease->disease_id,
                        'disease_name'        => $disease->disease_name,
                        'disease_name_en'     => $disease->disease_name_en,
                        'order'               => $disease->pivot->display_order ?? 0,
                        // ข้อมูลโรคแบบละเอียด สำหรับแสดงใน accordion หน้าผลลัพธ์
                        // ไม่ต้องเรียก GET /diseases/{id} ซ้ำอีกรอบ
                        'description'          => $disease->description,
                        'cause'                => $disease->cause,
                        'symptom_description'  => $disease->symptom_description,
                        'complications'        => $disease->complications,
                        'diagnosis'            => $disease->diagnosis,
                        'medical_treatment'    => $disease->medical_treatment,
                        'self_care'            => $disease->self_care,
                        'when_to_see_doctor'   => $disease->when_to_see_doctor,
                        'prevention'           => $disease->prevention,
                        'recommendations'      => $disease->recommendations,
                        'disease_image'        => $disease->disease_image,
                    ]);
            }),
        ];
    }
}
