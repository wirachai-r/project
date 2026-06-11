<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AnswerChoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'choice_id'      => $this->choice_id,
            'choice_text'    => $this->choice_text,
            'choice_text_en' => $this->choice_text_en,
            'order'          => $this->order,
            'status'         => $this->status,
            'box_id'         => $this->box_id,
            'next_box_id'    => $this->next_box_id,
            'next_box'       => new QuestionBoxResource($this->whenLoaded('nextBox')),
            'created_by'     => $this->created_by,
            'updated_by'     => $this->updated_by,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
