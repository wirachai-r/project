<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DiagnosisRuleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'urgency_level'     => 'required|in:R,P,Y,G,W',
            'time_frame'        => 'nullable|string|max:100',
            'time_frame_en'     => 'nullable|string|max:100',
            'note'              => 'nullable|string',
            'note_en'           => 'nullable|string',
            'medical_reference' => 'nullable|string|max:255',
            'status'            => 'nullable|in:1,2',
            'diagram_id'        => 'required|exists:diagrams,diagram_id',

            // diseases (many-to-many)
            'disease_ids'       => 'nullable|array',
            'disease_ids.*'     => 'exists:diseases,disease_id',

            'next_diagrams'                       => 'nullable|array',
            'next_diagrams.*.diagram_id'          => 'required|distinct|exists:diagrams,diagram_id',
            'next_diagrams.*.prompt_text'         => 'nullable|string|max:255',
            'next_diagrams.*.display_order'       => 'nullable|integer|min:0',

            // conditions
            'conditions'                  => 'nullable|array',
            'conditions.*.box_id'         => 'required|exists:question_boxes,box_id',
            'conditions.*.choice_id'      => 'required|exists:answer_choices,choice_id',
            'conditions.*.logic_operator' => 'nullable|in:AND,OR',
            'conditions.*.status'         => 'nullable|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'urgency_level.required' => 'กรุณาระบุระดับความเร่งด่วน',
            'urgency_level.in'       => 'ระดับความเร่งด่วนต้องเป็น R, P, Y, G หรือ W',
            'diagram_id.required'    => 'กรุณาระบุ diagram',
            'diagram_id.exists'      => 'ไม่พบ diagram ที่ระบุ',
            'disease_ids.*.exists'   => 'ไม่พบโรคที่ระบุ',
            'next_diagrams.*.diagram_id.exists' => 'ไม่พบแผนภูมิที่แนะนำ',
        ];
    }
}
