<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'rule_id'           => $this->rule_id,
            'urgency_level'     => $this->urgency_level,
            'should_see_doctor' => $this->should_see_doctor,
            'recommendation'    => $this->recommendation,
            'disease_id'        => $this->disease_id,
            'disease'           => new DiseaseResource($this->whenLoaded('disease')),
        ];
    }
}
