<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class DiagramResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'diagram_id'      => $this->diagram_id,
            'diagram_name'    => $this->diagram_name,
            'diagram_name_en' => $this->diagram_name_en,
            'description'     => $this->description,
            'status'          => $this->status,
            'symptom_id'      => $this->symptom_id,
            'entry_box_id'    => $this->entry_box_id,
            'symptom'         => new SymptomResource($this->whenLoaded('symptom')),
            'entry_box'       => new QuestionBoxResource($this->whenLoaded('entryBox')),
            'question_boxes'  => QuestionBoxResource::collection($this->whenLoaded('questionBoxes')),
            'created_by'      => $this->created_by,
            'updated_by'      => $this->updated_by,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
