<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class ClarifyAssessmentQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'box_id' => ['required', 'string', 'exists:question_boxes,box_id'],
            'attempt' => ['sometimes', 'integer', 'min:1', 'max:'.config('ai.max_clarification_attempts')],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
