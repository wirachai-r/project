<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FollowUpQuestionTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FollowUpQuestionTemplateController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => FollowUpQuestionTemplate::with('symptoms')
                ->orderByDesc('id')
                ->get(),
        ]);
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
        $followUpQuestionTemplate->update($this->validated($request, $followUpQuestionTemplate));
        $this->syncSymptoms($followUpQuestionTemplate, $request->input('symptoms', []));

        return response()->json(['data' => $followUpQuestionTemplate->load('symptoms')]);
    }

    public function destroy(FollowUpQuestionTemplate $followUpQuestionTemplate)
    {
        $followUpQuestionTemplate->delete();

        return response()->json(['message' => 'ลบคำถามติดตามอาการแล้ว']);
    }

    private function validated(
        Request $request,
        ?FollowUpQuestionTemplate $currentTemplate = null,
    ): array {
        $data = $request->validate([
            'question_text' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'answer_type' => ['required', Rule::in(['boolean', 'single_choice', 'multiple_choice', 'scale', 'number', 'text', 'date', 'time'])],
            'options' => ['nullable', Rule::requiredIf(fn () => in_array($request->input('answer_type'), ['single_choice', 'multiple_choice', 'scale'], true)), 'array', 'min:2', 'max:101'],
            'options.*' => ['string', 'max:200', 'distinct'],
            'unit' => ['nullable', 'string', 'max:30'],
            'response_rules' => ['nullable', 'array', 'max:10'],
            'response_rules.*.operator' => ['sometimes', Rule::in([
                'equals', 'not_equals', 'greater_than', 'greater_than_or_equal',
                'less_than', 'less_than_or_equal', 'between', 'contains',
                'not_contains', 'is_empty', 'is_not_empty',
            ])],
            'response_rules.*.value' => ['present', 'nullable'],
            'response_rules.*.value_to' => ['sometimes', 'nullable'],
            'response_rules.*.action' => ['required', Rule::in(['prompt_end_tracking', 'prompt_add_symptom', 'show_alert'])],
            'response_rules.*.alert_level' => ['nullable', Rule::in(['info', 'warning', 'important'])],
            'response_rules.*.title' => ['nullable', 'string', 'max:150'],
            'response_rules.*.message' => ['nullable', 'string', 'max:500'],
            'response_rules.*.requires_acknowledgement' => ['nullable', 'boolean'],
            'is_required' => ['required', 'boolean'],
            'applies_to_all_symptoms' => ['required', 'boolean'],
            'status' => ['sometimes', Rule::in(['0', '1'])],
            'symptoms' => ['sometimes', 'array'],
            'symptoms.*.symptom_id' => ['required', 'string', 'exists:main_symptoms,symptom_id', 'distinct'],
            'symptoms.*.sequence' => ['required', 'integer', 'min:1', 'max:999'],
            'symptoms.*.is_required' => ['nullable', 'boolean'],
        ]);

        $data['question_text'] = trim(preg_replace('/\s+/u', ' ', $data['question_text']));
        $normalizedQuestion = mb_strtolower($data['question_text']);
        $hasDuplicate = FollowUpQuestionTemplate::query()
            ->when($currentTemplate, fn ($query) => $query->whereKeyNot($currentTemplate->getKey()))
            ->pluck('question_text')
            ->contains(fn ($question) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $question))) === $normalizedQuestion);

        if ($hasDuplicate) {
            throw ValidationException::withMessages([
                'question_text' => 'มีคำถามนี้อยู่แล้ว กรุณาใช้คำถามอื่น',
            ]);
        }

        if ($data['answer_type'] === 'boolean' && count($data['options'] ?? []) !== 2) {
            throw ValidationException::withMessages([
                'options' => 'คำถามแบบใช่/ไม่ใช่ต้องมีข้อความคำตอบ 2 ตัวเลือก',
            ]);
        }
        if (in_array($data['answer_type'], ['single_choice', 'multiple_choice'], true)
            && count($data['options'] ?? []) > 10) {
            throw ValidationException::withMessages([
                'options' => 'คำถามแบบเลือกมีตัวเลือกได้ไม่เกิน 10 ตัวเลือก',
            ]);
        }
        if ($data['answer_type'] === 'scale') {
            $values = collect($data['options'] ?? [])->map(fn ($value) => is_numeric($value) ? (float) $value : null);
            $isAscending = $values->every(fn ($value, $index) => $value !== null
                && ($index === 0 || $value > $values[$index - 1]));
            if (! $isAscending) {
                throw ValidationException::withMessages([
                    'options' => 'ค่าระดับต้องเป็นตัวเลขเรียงจากน้อยไปมาก',
                ]);
            }
        }

        foreach ($data['response_rules'] ?? [] as $index => $responseRule) {
            if ($responseRule['action'] === 'show_alert' && blank($responseRule['message'] ?? null)) {
                throw ValidationException::withMessages([
                    "response_rules.{$index}.message" => 'กรุณาระบุข้อความแจ้งเตือน',
                ]);
            }
            if ($responseRule['action'] === 'show_alert') {
                $data['response_rules'][$index]['alert_level'] = $responseRule['alert_level'] ?? 'warning';
                $data['response_rules'][$index]['requires_acknowledgement'] =
                    ($responseRule['alert_level'] ?? 'warning') === 'important'
                    || ($responseRule['requires_acknowledgement'] ?? false);
            }
        }

        return $data;
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
