<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionBoxResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'box_id'              => $this->box_id,
            'frame_number'        => $this->frame_number,
            'question_text'       => $this->question_text,
            'question_text_en'    => $this->question_text_en,
            'question_image'      => $this->question_image,
            'question_type'       => $this->question_type,
            'status'              => $this->status,
            'diagram_id'          => $this->diagram_id,

            // ➕ เพิ่มคอลัมน์ใหม่ที่เกี่ยวกับเงื่อนไขคำถาม (จาก Migration ก่อนหน้า)
            'min_required'        => $this->min_required,
            'yes_next_box_id'     => $this->yes_next_box_id,
            'yes_next_diagram_id' => $this->yes_next_diagram_id,
            'no_next_box_id'      => $this->no_next_box_id,
            'no_next_diagram_id'  => $this->no_next_diagram_id,

            // ➕ เพิ่มคอลัมน์รายละเอียด (detail) ที่เพิ่งเพิ่มล่าสุด
            'detail'              => $this->detail,

            'choices'             => AnswerChoiceResource::collection($this->whenLoaded('choices')),
            'created_by'          => $this->created_by,
            'updated_by'          => $this->updated_by,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
