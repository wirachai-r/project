<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class AnswerClarificationQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['choice_id' => ['required', 'integer', 'exists:ai_clarification_choices,id']];
    }
}
