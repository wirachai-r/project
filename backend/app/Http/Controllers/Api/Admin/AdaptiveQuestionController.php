<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdaptiveQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdaptiveQuestionController extends Controller
{
    private const GENERATED_EVIDENCE_PREFIX = 'Generated candidate from internal disease-symptom co-occurrence and taxonomy';

    public function index(Request $request)
    {
        $questions = AdaptiveQuestion::query()
            ->with(['symptom:symptom_id,symptom_name', 'symptoms:symptom_id,symptom_name,symptom_name_en', 'options', 'rules.question:id,question_text'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($query) => $query->where('question_text', 'like', '%'.$request->string('search').'%'))
            ->latest('id')
            ->get();

        return response()->json(['data' => $questions]);
    }

    public function store(Request $request)
    {
        $question = DB::transaction(function () use ($request) {
            $data = $this->validated($request);
            $question = AdaptiveQuestion::create($this->questionData($data, $request));
            $this->syncRelations($question, $data);

            return $question;
        });

        return response()->json(['data' => $this->load($question)], 201);
    }

    public function show(AdaptiveQuestion $adaptiveQuestion)
    {
        return response()->json(['data' => $this->load($adaptiveQuestion)]);
    }

    public function update(Request $request, AdaptiveQuestion $adaptiveQuestion)
    {
        DB::transaction(function () use ($request, $adaptiveQuestion) {
            $data = $this->validated($request);
            $adaptiveQuestion->update($this->questionData($data, $request));
            $this->syncRelations($adaptiveQuestion, $data);
        });

        return response()->json(['data' => $this->load($adaptiveQuestion)]);
    }

    public function destroy(AdaptiveQuestion $adaptiveQuestion)
    {
        abort_if($adaptiveQuestion->status === 'approved', 422, 'กรุณาปิดใช้งานคำถามก่อนลบ');
        $adaptiveQuestion->delete();

        return response()->json(['message' => 'ลบคำถาม Adaptive แล้ว']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question_symptom_ids' => ['required', 'array', 'min:1', 'max:20'],
            'question_symptom_ids.*' => ['required', 'exists:main_symptoms,symptom_id', 'distinct'],
            'question_text' => ['required', 'string', 'max:500'],
            'explanation_text' => ['nullable', 'string', 'max:1000'],
            'answer_type' => ['required', Rule::in(['yes_no_unsure', 'single_choice', 'multiple_choice'])],
            'status' => ['required', Rule::in(['draft', 'reviewed', 'approved', 'inactive'])],
            'evidence_source' => ['nullable', 'string', 'max:2000'],
            'options' => ['array', 'max:20'],
            'options.*.option_text' => ['required', 'string', 'max:200'],
            'options.*.option_value' => ['required', 'string', 'max:80', 'distinct'],
            'options.*.target_symptom_id' => ['nullable', 'exists:main_symptoms,symptom_id'],
            'options.*.answer_effect' => ['required', Rule::in(['present', 'absent', 'unknown'])],
            'options.*.display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'options.*.status' => ['nullable', Rule::in(['0', '1'])],
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.initial_symptom_id' => ['required', 'exists:main_symptoms,symptom_id', 'distinct'],
            'rules.*.question_stage' => ['required', Rule::in(['local', 'associated', 'safety'])],
            'rules.*.priority' => ['required', 'integer', 'min:1', 'max:999'],
            'rules.*.is_required' => ['required', 'boolean'],
            'rules.*.status' => ['nullable', Rule::in(['0', '1'])],
        ]);

        if ($data['answer_type'] !== 'yes_no_unsure' && count($data['options'] ?? []) < 2) {
            throw ValidationException::withMessages(['options' => 'คำถามแบบเลือกต้องมีอย่างน้อย 2 ตัวเลือก']);
        }

        if ($data['status'] === 'approved' && blank($data['evidence_source'] ?? null)) {
            throw ValidationException::withMessages(['evidence_source' => 'คำถามที่อนุมัติต้องระบุแหล่งอ้างอิงหรือผู้ตรวจสอบ']);
        }

        if (
            $data['status'] === 'approved'
            && str_starts_with((string) ($data['evidence_source'] ?? ''), self::GENERATED_EVIDENCE_PREFIX)
        ) {
            throw ValidationException::withMessages([
                'evidence_source' => 'คำถามที่ระบบสร้างอัตโนมัติต้องผ่านการตรวจสอบและแก้ไขแหล่งอ้างอิงก่อนอนุมัติ',
            ]);
        }

        return $data;
    }

    private function questionData(array $data, Request $request): array
    {
        return [
            // Kept as the primary symptom for backward compatibility with
            // existing answers and the disease-scoring pipeline.
            'question_symptom_id' => $data['question_symptom_ids'][0],
            'question_text' => trim($data['question_text']),
            'explanation_text' => $data['explanation_text'] ?? null,
            'answer_type' => $data['answer_type'],
            'status' => $data['status'],
            'evidence_source' => $data['evidence_source'] ?? null,
            'approved_by' => $data['status'] === 'approved' ? $request->user()->user_id : null,
            'approved_at' => $data['status'] === 'approved' ? now() : null,
        ];
    }

    private function syncRelations(AdaptiveQuestion $question, array $data): void
    {
        $question->symptoms()->sync(collect($data['question_symptom_ids'])->mapWithKeys(fn ($symptomId, $index) => [
            $symptomId => ['display_order' => $index],
        ])->all());

        $question->options()->delete();
        if ($data['answer_type'] !== 'yes_no_unsure') {
            foreach ($data['options'] ?? [] as $index => $option) {
                $question->options()->create([
                    ...$option,
                    'display_order' => $option['display_order'] ?? $index,
                    'status' => $option['status'] ?? '1',
                ]);
            }
        }

        $question->rules()->delete();
        foreach ($data['rules'] as $rule) {
            $question->rules()->create([...$rule, 'status' => $rule['status'] ?? '1']);
        }
    }

    private function load(AdaptiveQuestion $question): AdaptiveQuestion
    {
        return $question->fresh()->load([
            'symptom:symptom_id,symptom_name',
            'symptoms:symptom_id,symptom_name,symptom_name_en',
            'options',
            'rules',
        ]);
    }
}
