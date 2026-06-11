<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TreatmentOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'treatment_text'    => 'required|string',
            'treatment_text_en' => 'nullable|string',
            'order'             => 'nullable|integer|min:0',
            'status'            => 'nullable|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'treatment_text.required' => 'กรุณากรอกคำสั่งการรักษา',
        ];
    }
}
