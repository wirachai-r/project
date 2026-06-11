<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DiagnosisRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disease_id' => 'required|exists:diseases,disease_id',
            'choice_id'  => 'required|exists:answer_choices,choice_id',
            'score'      => 'nullable|numeric|min:0',
            'status'     => 'nullable|in:1,0',
        ];
    }

    public function messages(): array
    {
        return [
            'disease_id.required' => 'กรุณาเลือกโรค',
            'disease_id.exists'   => 'ไม่พบโรคที่เลือก',
            'choice_id.required'  => 'กรุณาเลือกตัวเลือกคำตอบ',
            'choice_id.exists'    => 'ไม่พบตัวเลือกคำตอบที่เลือก',
        ];
    }
}
