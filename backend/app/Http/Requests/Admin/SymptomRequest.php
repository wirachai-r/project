<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesTextInput;
use App\Rules\UniqueNameIgnoringWhitespace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SymptomRequest extends FormRequest
{
    use NormalizesTextInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeTextInput(['symptom_name', 'symptom_name_en']);
    }

    public function rules(): array
    {
        return [
            'symptom_name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
                Rule::unique('main_symptoms', 'symptom_name')->ignore($this->route('symptom')?->getKey(), 'symptom_id'),
                (new UniqueNameIgnoringWhitespace('main_symptoms', 'symptom_name'))
                    ->ignore($this->route('symptom')?->getKey(), 'symptom_id'),
            ],
            'symptom_name_en' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'symptom_image' => 'nullable|string|max:255',
            'status' => 'nullable|in:1,2',
            'symptom_category_id' => 'sometimes|required|exists:symptom_categories,symptom_category_id',
        ];
    }

    public function messages(): array
    {
        return [
            'symptom_name.required' => 'กรุณากรอกชื่ออาการ',
            'symptom_name.unique' => 'มีชื่ออาการนี้อยู่แล้ว',
            'symptom_category_id.required' => 'กรุณาเลือกหมวดหมู่',
            'symptom_category_id.exists' => 'ไม่พบหมวดหมู่ที่เลือก',
        ];
    }
}
