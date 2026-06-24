<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionBoxResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'box_id'           => $this->box_id,
            'question_text'    => $this->question_text,
            'question_text_en' => $this->question_text_en,
            'question_image'   => $this->question_image,
            'question_type'    => $this->question_type,
            'status'           => $this->status,
            'diagram_id'       => $this->diagram_id,
            'choices'          => AnswerChoiceResource::collection($this->whenLoaded('choices')),
            'created_by'       => $this->created_by,
            'updated_by'       => $this->updated_by,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
