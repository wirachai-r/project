<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recorded_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', 'in:well,unwell'],
            'note' => ['nullable', 'string'],
            'symptom_ids' => ['nullable', 'array'],
            'symptom_ids.*' => ['string', 'distinct', 'exists:main_symptoms,symptom_id'],
        ];
    }
}
