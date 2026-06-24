<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class RuleConditionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'condition_id'   => $this->condition_id,
            'logic_operator' => $this->logic_operator, // AND, OR
            'status'         => $this->status,
            'rule_id'        => $this->rule_id,
            'box_id'         => $this->box_id,
            'choice_id'      => $this->choice_id,
            'question_box'   => $this->whenLoaded('box', fn() => [
                'box_id'        => $this->box->box_id,
                'question_text' => $this->box->question_text,
            ]),
            'answer_choice'  => $this->whenLoaded('choice', fn() => [
                'choice_id'   => $this->choice->choice_id,
                'choice_text' => $this->choice->choice_text,
            ]),
            'created_by'     => $this->created_by,
            'updated_by'     => $this->updated_by,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
