<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FollowUpQuestionTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FollowUpQuestionTemplateController extends Controller
{
    public function index()
    {
        return response()->json(['data' => FollowUpQuestionTemplate::with('symptoms')->latest()->get()]);
    }

    public function store(Request $request)
    {
        $template = FollowUpQuestionTemplate::create($this->validated($request));
        $this->syncSymptoms($template, $request->input('symptoms', []));

        return response()->json(['data' => $template->load('symptoms')], 201);
    }

    public function show(FollowUpQuestionTemplate $followUpQuestionTemplate)
    {
        return response()->json(['data' => $followUpQuestionTemplate->load('symptoms')]);
    }

    public function update(Request $request, FollowUpQuestionTemplate $followUpQuestionTemplate)
    {
        $followUpQuestionTemplate->update($this->validated($request));
        $this->syncSymptoms($followUpQuestionTemplate, $request->input('symptoms', []));

        return response()->json(['data' => $followUpQuestionTemplate->load('symptoms')]);
    }

    public function destroy(FollowUpQuestionTemplate $followUpQuestionTemplate)
    {
        $followUpQuestionTemplate->delete();

        return response()->json(['message' => 'ลบคำถามติดตามอาการแล้ว']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question_text' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'answer_type' => ['required', Rule::in(['boolean', 'single_choice', 'multiple_choice', 'scale', 'number', 'text', 'date', 'time'])],
            'options' => ['nullable', Rule::requiredIf(fn () => in_array($request->input('answer_type'), ['single_choice', 'multiple_choice', 'scale'], true)), 'array', 'min:2', 'max:10'],
            'options.*' => ['string', 'max:200', 'distinct'],
            'unit' => ['nullable', 'string', 'max:30'],
            'is_required' => ['required', 'boolean'],
            'applies_to_all_symptoms' => ['required', 'boolean'],
            'status' => ['sometimes', Rule::in(['0', '1'])],
            'symptoms' => ['sometimes', 'array'],
            'symptoms.*.symptom_id' => ['required', 'string', 'exists:main_symptoms,symptom_id', 'distinct'],
            'symptoms.*.sequence' => ['required', 'integer', 'min:1', 'max:999'],
            'symptoms.*.is_required' => ['nullable', 'boolean'],
        ]);
    }

    private function syncSymptoms(FollowUpQuestionTemplate $template, array $symptoms): void
    {
        $template->symptoms()->sync(collect($symptoms)->mapWithKeys(fn ($item) => [
            $item['symptom_id'] => [
                'sequence' => $item['sequence'],
                'is_required_override' => $item['is_required'] ?? null,
                'status' => '1',
            ],
        ])->all());
    }
}
