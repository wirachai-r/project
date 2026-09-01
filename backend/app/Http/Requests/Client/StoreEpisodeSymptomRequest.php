<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreEpisodeSymptomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symptom_id' => ['nullable', 'required_without:custom_symptom_text', 'string', 'exists:main_symptoms,symptom_id'],
            'custom_symptom_text' => ['nullable', 'required_without:symptom_id', 'string', 'max:200'],
            'first_observed_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}
