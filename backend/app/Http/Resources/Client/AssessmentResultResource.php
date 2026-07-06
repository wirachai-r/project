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
                        'prevention'           => $disease->prevention,
                        'disease_image'        => $disease->disease_image,
                    ]);
            }),
        ];
    }
}
