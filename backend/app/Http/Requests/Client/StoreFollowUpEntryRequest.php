<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreFollowUpEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'severity' => ['required', 'integer', 'min:1', 'max:10'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'note' => ['nullable', 'string', 'max:2000'],
            'recorded_at' => ['nullable', 'date', 'before_or_equal:now'],
            'answers' => ['sometimes', 'array'],
            'answers.*.question_template_id' => ['required', 'integer', 'distinct', 'exists:follow_up_question_templates,id'],
            'answers.*.value' => ['present'],
        ];
    }
}
