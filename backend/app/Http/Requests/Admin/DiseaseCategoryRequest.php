<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesTextInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiseaseCategoryRequest extends FormRequest
{
    use NormalizesTextInput;

    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->normalizeTextInput(['category_name', 'category_name_en']);
    }

    public function rules(): array
    {
        return [
            'category_name'    => ['sometimes', 'required', 'string', 'max:100', Rule::unique('disease_categories', 'category_name')->ignore($this->route('diseaseCategory')?->getKey(), 'disease_category_id')],
            'category_name_en' => 'nullable|string|max:100',
            'description'      => 'nullable|string',
            'icon'             => 'nullable|string|max:255',
            'status'           => 'nullable|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'กรุณากรอกชื่อหมวดหมู่',
            'category_name.unique'   => 'มีชื่อหมวดหมู่โรคนี้อยู่แล้ว',
        ];
    }
}
