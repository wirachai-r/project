<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosisRuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'rule_id'       => $this->rule_id,
            'disease_id'    => $this->disease_id,
            'choice_id'     => $this->choice_id,
            'score'         => $this->score,
            'status'        => $this->status,
            'disease'       => new DiseaseResource($this->whenLoaded('disease')),
            'answer_choice' => new AnswerChoiceResource($this->whenLoaded('answerChoice')),
            'created_by'    => $this->created_by,
            'updated_by'    => $this->updated_by,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
