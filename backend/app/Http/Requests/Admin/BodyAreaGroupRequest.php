<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BodyAreaGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('body_area_group')?->id;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('body_area_groups')->ignore($id)],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'image' => [$this->isMethod('post') ? 'required' : 'nullable', 'image', 'mimes:png', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['1', '2'])],
            'symptom_ids' => ['sometimes', 'array'],
            'symptom_ids.*' => ['string', 'distinct', 'exists:main_symptoms,symptom_id'],
        ];
    }
}
