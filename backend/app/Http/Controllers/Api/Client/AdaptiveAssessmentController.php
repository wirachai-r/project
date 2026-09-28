<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\AdaptiveAssessment;
use App\Models\AdaptiveAssessmentAnswer;
use App\Models\AdaptiveAssessmentResult;
use App\Models\AdaptiveQuestion;
use App\Models\AdaptiveQuestionRule;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Disease;
use App\Models\MainSymptom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdaptiveAssessmentController extends Controller
{
    private const GENERATED_EVIDENCE_PREFIX = 'Generated candidate from internal disease-symptom co-occurrence and taxonomy';

    private const GENERATED_EVIDENCE_PREFIX_TH = 'สร้างอัตโนมัติจาก disease_symptoms ภายในระบบ';

    public function start(Request $request)
    {
        $validated = $request->validate(['symptom_id' => 'required|exists:main_symptoms,symptom_id']);
        $user = $request->user('sanctum');
        $sessionToken = $user ? null : Str::random(64);
        $assessment = AdaptiveAssessment::create([
            'user_id' => $user?->user_id,
            'session_token' => $sessionToken,
            'initial_symptom_id' => $validated['symptom_id'],
        ]);

        $question = $this->nextQuestion($assessment);
        if (! $question) {
            $results = $this->complete($assessment);

            return response()->json([
                'assessment_id' => $assessment->id,
                'history_assessment_id' => $assessment->fresh()->assessment_id,
                'session_token' => $sessionToken,
                'status' => 'completed',
                'results' => $results,
            ]);
        }

        return response()->json(['assessment_id' => $assessment->id, 'session_token' => $sessionToken, 'status' => 'question', 'question' => $question]);
    }

    public function answer(Request $request, AdaptiveAssessment $adaptiveAssessment)
    {
        $this->authorizeAssessment($request, $adaptiveAssessment);
        abort_if($adaptiveAssessment->status !== 'processing', 422, 'การประเมินนี้สิ้นสุดแล้ว');
        $validated = $request->validate([
            'question_id' => 'nullable|exists:adaptive_questions,id',
            'symptom_id' => 'required_without:question_id|exists:main_symptoms,symptom_id',
            'answer' => 'required_without:option_ids|nullable|in:yes,no,unsure',
            'option_ids' => 'required_without:answer|array|min:1',
            'option_ids.*' => 'integer|distinct|exists:adaptive_question_options,id',
        ]);

        $expectedQuestion = $this->nextQuestion($adaptiveAssessment);
        abort_unless($expectedQuestion, 422, 'ไม่พบคำถามถัดไปสำหรับการประเมินนี้');

        if (isset($expectedQuestion['question_id'])) {
            abort_unless(
                (int) ($validated['question_id'] ?? 0) === (int) $expectedQuestion['question_id'],
                422,
                'คำถามนี้ไม่ใช่คำถามลำดับปัจจุบัน กรุณาโหลดคำถามล่าสุดแล้วลองอีกครั้ง',
            );
        } else {
            abort_if(! empty($validated['question_id']), 422, 'คำถามนี้ไม่ใช่คำถามลำดับปัจจุบัน');
            abort_unless(
                ($validated['symptom_id'] ?? null) === ($expectedQuestion['symptom_id'] ?? null),
                422,
                'อาการนี้ไม่ใช่คำถามลำดับปัจจุบัน กรุณาโหลดคำถามล่าสุดแล้วลองอีกครั้ง',
            );
        }

        if (! empty($validated['question_id'])) {
            $question = AdaptiveQuestion::with([
                'symptoms:symptom_id',
                'options' => fn ($query) => $query->where('status', '1'),
            ])
                ->whereKey($validated['question_id'])
                ->where('status', 'approved')
                ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX.'%')
                ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX_TH.'%')
                ->whereHas('rules', fn ($query) => $query
                    ->where('initial_symptom_id', $adaptiveAssessment->initial_symptom_id)
                    ->where('status', '1')
                    ->whereIn('evidence_status', ['reviewed', 'verified']))
                ->firstOrFail();
            $payload = $this->answerPayload($question, $validated);
            AdaptiveAssessmentAnswer::updateOrCreate(
                ['adaptive_assessment_id' => $adaptiveAssessment->id, 'adaptive_question_id' => $question->id],
                [
                    'symptom_id' => $question->question_symptom_id,
                    'answer' => $payload['answer'],
                    'answer_payload' => $payload,
                ],
            );
        } else {
            AdaptiveAssessmentAnswer::updateOrCreate(
                ['adaptive_assessment_id' => $adaptiveAssessment->id, 'symptom_id' => $validated['symptom_id']],
                ['answer' => $validated['answer']],
            );
        }
        $adaptiveAssessment->update(['question_count' => $adaptiveAssessment->answers()->count()]);

        $freshAssessment = $adaptiveAssessment->fresh();
        $question = $this->shouldCompleteAssessment($freshAssessment)
            ? null
            : $this->nextQuestion($freshAssessment);
        if ($question) {
            return response()->json(['status' => 'question', 'question' => $question]);
        }

        $results = $this->complete($adaptiveAssessment->fresh());

        return response()->json([
            'status' => 'completed',
            'history_assessment_id' => $adaptiveAssessment->fresh()->assessment_id,
            'results' => $results,
        ]);
    }

    public function back(Request $request, AdaptiveAssessment $adaptiveAssessment)
    {
        $this->authorizeAssessment($request, $adaptiveAssessment);
        abort_if($adaptiveAssessment->status !== 'processing', 422, 'การประเมินนี้สิ้นสุดแล้ว');

        $lastAnswer = $adaptiveAssessment->answers()->latest('id')->first();
        abort_unless($lastAnswer, 422, 'ยังไม่มีคำถามก่อนหน้า');
        $symptom = $lastAnswer->symptom;
        $question = $lastAnswer->question;
        $lastAnswer->delete();
        $adaptiveAssessment->update(['question_count' => $adaptiveAssessment->answers()->count()]);

        return response()->json([
            'status' => 'question',
            'question' => $question
                ? $this->formatConfiguredQuestion($question->load('options'), $adaptiveAssessment->fresh())
                : $this->formatQuestion($symptom, $adaptiveAssessment->fresh()),
        ]);
    }

    public function abandon(Request $request, AdaptiveAssessment $adaptiveAssessment)
    {
        $this->authorizeAssessment($request, $adaptiveAssessment);
        if ($adaptiveAssessment->status === 'processing') {
            $adaptiveAssessment->update(['status' => 'abandoned']);
        }

        return response()->json(['status' => $adaptiveAssessment->fresh()->status]);
    }

    public function result(Request $request, AdaptiveAssessment $adaptiveAssessment)
    {
        $this->authorizeAssessment($request, $adaptiveAssessment);
        abort_if($adaptiveAssessment->status !== 'completed', 422, 'การประเมินยังไม่สิ้นสุด');

        return response()->json([
            'assessment_id' => $adaptiveAssessment->id,
            'history_assessment_id' => $adaptiveAssessment->assessment_id,
            'results' => $this->formatResults($adaptiveAssessment),
        ]);
    }

    private function nextQuestion(AdaptiveAssessment $assessment): ?array
    {
        $configured = $this->configuredNextQuestion($assessment);
        if ($configured !== false) {
            return $configured;
        }

        $answeredIds = $assessment->answers()->pluck('symptom_id')->push($assessment->initial_symptom_id);
        $candidateIds = $this->rankedCandidates($assessment)
            ->take(5)
            ->pluck('disease.disease_id');

        if ($candidateIds->isEmpty()) {
            $candidateIds = Disease::query()
                ->where('status', '1')
                ->whereHas('symptoms', fn ($q) => $q->where('main_symptoms.symptom_id', $assessment->initial_symptom_id))
                ->pluck('disease_id');
        }

        if ($candidateIds->isEmpty()) {
            return null;
        }

        $idealSplit = $candidateIds->count() / 2;

        // When an admin has already scoped candidate questions for this initial
        // symptom, use that scope to constrain the legacy fallback as well. The
        // draft/reviewed question wording is still not published; only its target
        // symptom is used as a whitelist. This prevents unrelated co-disease
        // symptoms (for example fever after selecting toothache) from leaking in.
        $scopedSymptomIds = AdaptiveQuestionRule::query()
            ->where('adaptive_question_rules.initial_symptom_id', $assessment->initial_symptom_id)
            ->where('adaptive_question_rules.status', '1')
            ->join('adaptive_questions', 'adaptive_questions.id', '=', 'adaptive_question_rules.adaptive_question_id')
            ->where(fn ($query) => $query
                ->whereNull('adaptive_questions.evidence_source')
                ->orWhere(fn ($sourceQuery) => $sourceQuery
                    ->where('adaptive_questions.evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX.'%')
                    ->where('adaptive_questions.evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX_TH.'%')))
            ->pluck('adaptive_questions.question_symptom_id')
            ->unique()
            ->values();

        $symptom = MainSymptom::query()
            ->where('status', '1')
            ->whereNotIn('symptom_id', $answeredIds)
            ->when($scopedSymptomIds->isNotEmpty(), fn ($query) => $query->whereIn('symptom_id', $scopedSymptomIds))
            ->whereHas('diseases', fn ($q) => $q->whereIn('diseases.disease_id', $candidateIds))
            ->withCount(['diseases' => fn ($q) => $q->whereIn('diseases.disease_id', $candidateIds)])
            ->orderBy('symptom_name')
            ->get()
            // A symptom present in every candidate cannot distinguish them.
            ->filter(fn (MainSymptom $item) => $item->diseases_count < $candidateIds->count())
            // Prefer the most even split because it removes the most
            // uncertainty from the remaining disease candidates.
            ->sortBy(fn (MainSymptom $item) => abs($item->diseases_count - $idealSplit))
            ->first();

        if (! $symptom) {
            return null;
        }

        $contextDisease = $this->rankedCandidates($assessment)
            ->pluck('disease')
            ->first(fn (Disease $disease) => $disease->symptoms->contains('symptom_id', $symptom->symptom_id));

        return $this->formatQuestion($symptom, $assessment, $contextDisease);
    }

    /** Returns false when no curated bank exists, null when it is exhausted. */
    private function configuredNextQuestion(AdaptiveAssessment $assessment): array|null|false
    {
        $rules = AdaptiveQuestionRule::query()
            ->where('initial_symptom_id', $assessment->initial_symptom_id)
            ->where('status', '1')
            ->whereIn('evidence_status', ['reviewed', 'verified'])
            ->whereHas('question', fn ($query) => $query
                ->where('status', 'approved')
                ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX.'%')
                ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX_TH.'%'))
            ->with([
                'question.symptoms:symptom_id',
                'question.options' => fn ($query) => $query->where('status', '1'),
            ])
            ->get();

        if ($rules->isEmpty()) {
            return false;
        }

        $answeredQuestionIds = $assessment->answers()->whereNotNull('adaptive_question_id')->pluck('adaptive_question_id');
        $remaining = $rules->whereNotIn('adaptive_question_id', $answeredQuestionIds);
        if ($remaining->isEmpty()) {
            return null;
        }

        $stageOrder = ['local' => 1, 'associated' => 2, 'safety' => 3];
        $rule = $remaining
            ->sortBy(fn ($item) => sprintf(
                '%d-%03d-%06d',
                $item->is_required ? 0 : ($stageOrder[$item->question_stage] ?? 9),
                $item->priority,
                $item->id,
            ))
            ->first();

        return $this->formatConfiguredQuestion($rule->question, $assessment);
    }

    private function formatConfiguredQuestion(AdaptiveQuestion $question, AdaptiveAssessment $assessment): array
    {
        $options = $question->answer_type === 'yes_no_unsure'
            ? [
                ['id' => null, 'value' => 'yes', 'text' => 'ใช่'],
                ['id' => null, 'value' => 'no', 'text' => 'ไม่ใช่'],
                ['id' => null, 'value' => 'unsure', 'text' => 'ไม่แน่ใจ'],
            ]
            : $question->options->map(fn ($option) => [
                'id' => $option->id,
                'value' => $option->option_value,
                'text' => $option->option_text,
            ])->values()->all();

        return [
            'question_id' => $question->id,
            'symptom_id' => $question->question_symptom_id,
            'symptom_ids' => $question->symptoms->pluck('symptom_id')->values()->all(),
            'text' => $question->question_text,
            'detail' => $question->explanation_text,
            'answer_type' => $question->answer_type,
            'options' => $options,
            'number' => $assessment->question_count + 1,
        ];
    }

    private function answerPayload(AdaptiveQuestion $question, array $validated): array
    {
        if ($question->answer_type === 'yes_no_unsure') {
            $answer = $validated['answer'];

            return [
                'answer' => $answer,
                'effects' => $question->symptoms
                    ->whenEmpty(fn ($items) => $items->push($question->symptom))
                    ->map(fn ($symptom) => [
                        'symptom_id' => $symptom->symptom_id,
                        'effect' => match ($answer) {
                            'yes' => 'present',
                            'no' => 'absent',
                            default => 'unknown',
                        },
                    ])->values()->all(),
            ];
        }

        $selectedIds = collect($validated['option_ids'] ?? []);
        if ($question->answer_type === 'single_choice' && $selectedIds->count() !== 1) {
            abort(422, 'คำถามนี้เลือกคำตอบได้เพียงหนึ่งข้อ');
        }
        $options = $question->options->whereIn('id', $selectedIds);
        abort_if($options->count() !== $selectedIds->count(), 422, 'ตัวเลือกไม่ตรงกับคำถาม');

        $effects = $options->map(fn ($option) => [
            'symptom_id' => $option->target_symptom_id ?? $question->question_symptom_id,
            'effect' => $option->answer_effect,
        ])->values()->all();
        $effectValues = collect($effects)->pluck('effect');

        return [
            'answer' => $effectValues->contains('present') ? 'yes' : ($effectValues->contains('absent') ? 'no' : 'unsure'),
            'option_ids' => $selectedIds->values()->all(),
            'effects' => $effects,
        ];
    }

    private function formatQuestion(
        MainSymptom $symptom,
        AdaptiveAssessment $assessment,
        ?Disease $contextDisease = null,
    ): array {
        $pivot = $contextDisease?->symptoms->firstWhere('symptom_id', $symptom->symptom_id)?->pivot;
        $questionText = trim((string) ($pivot?->question_text ?? ''));
        if ($questionText === '') {
            $questionText = "มีอาการ{$symptom->symptom_name}ร่วมด้วยหรือไม่?";
        }

        return [
            'symptom_id' => $symptom->symptom_id,
            'text' => $questionText,
            'detail' => $contextDisease
                ? "ภาวะ {$contextDisease->disease_name} อาจมีอาการนี้ร่วมด้วย คำตอบนี้ใช้ประเมินความสอดคล้องเท่านั้น"
                : 'เลือกคำตอบที่ใกล้เคียงกับอาการในขณะนี้มากที่สุด',
            'number' => $assessment->question_count + 1,
        ];
    }

    private function shouldCompleteAssessment(AdaptiveAssessment $assessment): bool
    {
        $hasUnansweredRequired = AdaptiveQuestionRule::query()
            ->where('initial_symptom_id', $assessment->initial_symptom_id)
            ->where('status', '1')
            ->where('is_required', true)
            ->whereIn('evidence_status', ['reviewed', 'verified'])
            ->whereHas('question', fn ($query) => $query
                ->where('status', 'approved')
                ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX.'%')
                ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX_TH.'%'))
            ->whereNotIn('adaptive_question_id', $assessment->answers()->whereNotNull('adaptive_question_id')->select('adaptive_question_id'))
            ->exists();
        if ($hasUnansweredRequired) {
            return false;
        }

        $definiteAnswers = $assessment->answers()->whereIn('answer', ['yes', 'no'])->count();
        if ($definiteAnswers < 5) {
            return false;
        }

        $ranked = $this->rankedCandidates($assessment);
        if ($ranked->count() < 2) {
            return $ranked->isNotEmpty();
        }

        return $ranked[0]['match_percent'] > $ranked[1]['match_percent'];
    }

    private function rankedCandidates(AdaptiveAssessment $assessment)
    {
        $answers = $this->evidenceAnswers($assessment);
        $evaluatedSymptomCount = $answers->filter(
            fn (string $answer) => in_array($answer, ['yes', 'no'], true),
        )->count();

        return Disease::query()
            ->where('status', '1')
            ->whereHas('symptoms', fn ($q) => $q->where('main_symptoms.symptom_id', $assessment->initial_symptom_id))
            ->with('symptoms:symptom_id')
            ->get()
            ->map(function (Disease $disease) use ($answers, $evaluatedSymptomCount) {
                $symptoms = $disease->symptoms->keyBy('symptom_id');
                $positiveEvidence = 1.0;
                $possibleEvidence = 1.0;
                $supportingYesCount = 0;
                foreach ($answers as $symptomId => $answer) {
                    if ($answer === 'unsure') {
                        continue;
                    }

                    $linkedSymptom = $symptoms->get($symptomId);
                    if ($answer === 'yes') {
                        $possibleEvidence += 1.0;
                        if ($linkedSymptom) {
                            $positiveEvidence += max(0.0, (float) ($linkedSymptom->pivot->assessment_weight ?? 1));
                            $supportingYesCount++;
                        }
                    } elseif (
                        $answer === 'no'
                        && $linkedSymptom
                        && (bool) ($linkedSymptom->pivot->is_key_symptom ?? false)
                    ) {
                        // A negative answer only reduces support when a
                        // clinician has explicitly marked this as a key
                        // symptom and supplied an absence penalty.
                        $positiveEvidence -= max(0.0, (float) ($linkedSymptom->pivot->absence_penalty ?? 0));
                    }
                }

                $matchPercent = (int) round(
                    (max(0.0, $positiveEvidence) / max(1.0, $possibleEvidence)) * 100,
                );

                return [
                    'disease' => $disease,
                    'match_percent' => min(100, $matchPercent),
                    'supporting_yes_count' => $supportingYesCount,
                    'evaluated_symptom_count' => $evaluatedSymptomCount,
                ];
            })
            ->sortByDesc('match_percent')
            ->values();
    }

    private function evidenceAnswers(AdaptiveAssessment $assessment)
    {
        $evidence = collect();
        foreach ($assessment->answers()->get() as $answer) {
            $effects = $answer->answer_payload['effects'] ?? null;
            if (! is_array($effects)) {
                $evidence->put($answer->symptom_id, $answer->answer);

                continue;
            }
            foreach ($effects as $effect) {
                $symptomId = $effect['symptom_id'] ?? null;
                if (! $symptomId) {
                    continue;
                }
                $evidence->put($symptomId, match ($effect['effect'] ?? 'unknown') {
                    'present' => 'yes',
                    'absent' => 'no',
                    default => 'unsure',
                });
            }
        }

        return $evidence;
    }

    private function complete(AdaptiveAssessment $assessment): array
    {
        $answers = $assessment->answers()->get();
        $unsureCount = $answers->where('answer', 'unsure')->count();
        $mostlyUnsure = $answers->isNotEmpty() && $unsureCount * 2 > $answers->count();

        // The initial symptom only selects the candidate pool. A condition is
        // shown only when at least one subsequent "yes" answer supports it.
        // No absolute percentage threshold is used because this score is a
        // similarity indicator, not a calibrated disease probability.
        $diseases = $mostlyUnsure
            ? collect()
            : $this->rankedCandidates($assessment)
                ->filter(fn (array $item) => $item['supporting_yes_count'] > 0)
                ->values();

        AdaptiveAssessmentResult::where('adaptive_assessment_id', $assessment->id)->delete();
        foreach ($diseases as $index => $item) {
            AdaptiveAssessmentResult::create([
                'adaptive_assessment_id' => $assessment->id,
                'disease_id' => $item['disease']->disease_id,
                'disease_name' => $item['disease']->disease_name,
                'match_percent' => $item['match_percent'],
                'supporting_symptom_count' => $item['supporting_yes_count'],
                'evaluated_symptom_count' => $item['evaluated_symptom_count'],
                'display_order' => $index,
            ]);
        }
        $assessment->update(['status' => 'completed', 'completed_at' => now()]);
        $this->syncAssessmentRecord($assessment->fresh(), $diseases);

        return $this->formatResults($assessment->fresh());
    }

    private function syncAssessmentRecord(AdaptiveAssessment $adaptiveAssessment, $diseases): void
    {
        DB::transaction(function () use ($adaptiveAssessment, $diseases): void {
            $assessment = $adaptiveAssessment->assessment_id
                ? Assessment::query()->find($adaptiveAssessment->assessment_id)
                : null;

            if (! $assessment) {
                $assessment = Assessment::create([
                    'user_id' => $adaptiveAssessment->user_id,
                    'session_token' => $adaptiveAssessment->session_token,
                    'symptom_id' => $adaptiveAssessment->initial_symptom_id,
                    'diagram_id' => null,
                    'assessment_type' => 'adaptive',
                    'assessment_status' => 'C',
                    'started_at' => $adaptiveAssessment->created_at,
                    'completed_at' => $adaptiveAssessment->completed_at ?? now(),
                    'is_saved' => false,
                ]);
                $adaptiveAssessment->update(['assessment_id' => $assessment->id]);
            }

            $result = AssessmentResult::updateOrCreate(
                ['assessment_id' => $assessment->id],
                [
                    'urgency_level' => 'W',
                    'should_see_doctor' => 'N',
                    'recommendation' => 'ผลคัดกรองจากการประเมินอาการตามคำตอบ กรุณาพิจารณาร่วมกับอาการจริงและคำแนะนำจากบุคลากรทางการแพทย์',
                    'rule_id' => null,
                ],
            );

            $result->diseases()->sync(
                $diseases->mapWithKeys(fn (array $item, int $index) => [
                    $item['disease']->disease_id => [
                        'display_order' => $index,
                        'match_percent' => $item['match_percent'],
                        'supporting_symptom_count' => $item['supporting_yes_count'],
                        'evaluated_symptom_count' => $item['evaluated_symptom_count'],
                    ],
                ])->all(),
            );
        });
    }

    private function formatResults(AdaptiveAssessment $assessment): array
    {
        return $assessment->results()->get()->map(fn ($result) => [
            'disease_id' => $result->disease_id,
            'disease_name' => $result->disease_name,
            'match_percent' => $result->match_percent,
            'supporting_symptom_count' => $result->supporting_symptom_count,
            'evaluated_symptom_count' => $result->evaluated_symptom_count,
            'has_article' => $result->disease_id !== null,
        ])->all();
    }

    private function authorizeAssessment(Request $request, AdaptiveAssessment $assessment): void
    {
        $user = $request->user('sanctum');
        if ($user && $assessment->user_id === $user->user_id) {
            return;
        }
        $token = (string) $request->header('X-Session-Token');
        abort_if(! $assessment->session_token || ! $token || ! hash_equals($assessment->session_token, $token), 403);
    }
}
