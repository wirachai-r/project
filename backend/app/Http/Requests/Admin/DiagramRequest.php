<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesTextInput;
use App\Rules\UniqueNameIgnoringWhitespace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiagramRequest extends FormRequest
{
    use NormalizesTextInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeTextInput(['diagram_name', 'diagram_name_en']);
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'diagram_name' => [
                ...($isUpdate ? ['sometimes'] : []),
                'required',
                'string',
                'max:150',
                Rule::unique('diagrams', 'diagram_name')->ignore($this->route('diagram')?->getKey(), 'diagram_id'),
                (new UniqueNameIgnoringWhitespace('diagrams', 'diagram_name'))
                    ->ignore($this->route('diagram')?->getKey(), 'diagram_id'),
            ],
            'diagram_name_en' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'status' => 'nullable|in:1,2',
            'entry_box_id' => 'nullable|exists:question_boxes,box_id',
            'symptom_ids' => 'nullable|array',
            'symptom_ids.*' => 'exists:main_symptoms,symptom_id',
        ];
    }

    public function messages(): array
    {
        return [
            'diagram_name.required' => 'กรุณากรอกชื่อแผนภูมิ',
            'diagram_name.unique' => 'มีชื่อแผนภูมินี้อยู่แล้ว',
            'symptom_ids.*.exists' => 'ไม่พบอาการที่เลือก',
        ];
    }
}
