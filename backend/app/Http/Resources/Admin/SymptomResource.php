<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class SymptomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'symptom_id' => $this->symptom_id,
            'symptom_name' => $this->symptom_name,
            'symptom_name_en' => $this->symptom_name_en,
            'description' => $this->description,
            'symptom_image' => $this->symptom_image,
            'status' => $this->status,
            'symptom_category_id' => $this->symptom_category_id,
            'category' => new SymptomCategoryResource($this->whenLoaded('category')),
            'assessment' => $this->whenPivotLoaded('disease_symptoms', fn () => [
                'assessment_weight' => (float) $this->pivot->assessment_weight,
                'is_key_symptom' => (bool) $this->pivot->is_key_symptom,
                'absence_penalty' => (float) $this->pivot->absence_penalty,
                'question_text' => $this->pivot->question_text,
                'evidence_source' => $this->pivot->evidence_source,
                'evidence_status' => $this->pivot->evidence_status,
                'reviewed_by' => $this->pivot->reviewed_by,
                'reviewed_at' => $this->pivot->reviewed_at,
            ]),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
