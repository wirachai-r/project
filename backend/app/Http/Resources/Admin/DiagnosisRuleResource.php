<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosisRuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'rule_id'           => $this->rule_id,
            'time_frame'        => $this->time_frame,
            'time_frame_en'     => $this->time_frame_en,
            'note'              => $this->note,
            'note_en'           => $this->note_en,
            'diseases'          => $this->whenLoaded(
                'diseases',
                fn() =>
                $this->diseases->map(fn($d) => [
                    'disease_id'   => $d->disease_id,
                    'disease_name' => $d->disease_name,
                    'order'        => $d->pivot->display_order,
                ])
            ),
            'medical_reference' => $this->medical_reference,
            'urgency_level'     => $this->urgency_level,
            'status'            => $this->status,
            'diagram_id'        => $this->diagram_id,
            'diagram' => $this->whenLoaded('diagram', fn() => [
                'diagram_id'   => $this->diagram->diagram_id,
                'diagram_name' => $this->diagram->diagram_name,
            ]),
            'conditions'        => RuleConditionResource::collection($this->whenLoaded('conditions')),
            'created_by'        => $this->created_by,
            'updated_by'        => $this->updated_by,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
