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
    private const ASKABLE_EVIDENCE_STATUSES = [
        'unreviewed',
        'source_linked',
        'reviewed',
        'verified',
    ];

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
                'evidence_summary' => $this->formatEvidenceSummary($assessment->fresh()),
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
                    ->where('status', '1')
                    ->whereIn('evidence_status', self::ASKABLE_EVIDENCE_STATUSES))
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
        $question = $this->nextQuestion($freshAssessment);
        if ($question) {
            return response()->json(['status' => 'question', 'question' => $question]);
        }

        $results = $this->complete($adaptiveAssessment->fresh());

        return response()->json([
            'status' => 'completed',
            'history_assessment_id' => $adaptiveAssessment->fresh()->assessment_id,
            'results' => $results,
            'evidence_summary' => $this->formatEvidenceSummary($adaptiveAssessment->fresh()),
        ]);
    }

    public function back(Request $request, AdaptiveAssessment $adaptiveAssessment)
    {
        $this->authorizeAssessment($request, $adaptiveAssessment);
        abort_if($adaptiveAssessment->status !== 'processing', 422, 'การประเมินนี้สิ้นสุดแล้ว');

        $lastAnswer = $adaptiveAssessment->answers()->latest('id')->first();
        abort_unless($lastAnswer, 422, 'ยังไม่มีคำถามก่อนหน้า');
        $question = $lastAnswer->question;
        if (! $question) {
            $question = AdaptiveQuestion::query()
                ->where('status', 'approved')
                ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX.'%')
                ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX_TH.'%')
                ->whereHas('symptoms', fn ($query) => $query->where(
                    'main_symptoms.symptom_id',
                    $lastAnswer->symptom_id,
                ))
                ->whereHas('rules', fn ($query) => $query
                    ->where('status', '1')
                    ->whereIn('evidence_status', self::ASKABLE_EVIDENCE_STATUSES))
                ->with([
                    'symptoms:symptom_id',
                    'options' => fn ($query) => $query->where('status', '1'),
                ])
                ->first();
            abort_unless($question, 422, 'ไม่พบคำถามที่อนุมัติแล้วสำหรับคำตอบเดิม');
        }
        $lastAnswer->delete();
        $adaptiveAssessment->update(['question_count' => $adaptiveAssessment->answers()->count()]);

        return response()->json([
            'status' => 'question',
            'question' => $this->formatConfiguredQuestion(
                $question->loadMissing(['symptoms', 'options']),
                $adaptiveAssessment->fresh(),
            ),
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
            'evidence_summary' => $this->formatEvidenceSummary($adaptiveAssessment),
        ]);
    }

    private function nextQuestion(AdaptiveAssessment $assessment): ?array
    {
        // Questions explicitly marked "ask first" must be completed before
        // the adaptive stopping rule is allowed to finish the assessment.
        $required = $this->configuredNextQuestion($assessment, true);
        if (is_array($required)) {
            return $required;
        }

        if ($this->hasEnoughSeparatingEvidence($assessment)) {
            return null;
        }

        $definiteAnswers = $assessment->answers()->whereIn('answer', ['yes', 'no'])->count();
        $minimumClearAnswers = (int) config('adaptive_assessment.minimum_clear_answers', 5);

        // Use the ordered group to establish a minimum evidence base. Once
        // that base exists, prefer a disease-symptom question that best
        // separates the leading candidates instead of exhausting the group.
        if ($definiteAnswers < $minimumClearAnswers) {
            $configured = $this->configuredNextQuestion($assessment, false);
            if (is_array($configured)) {
                return $configured;
            }

            return $this->discriminationNextQuestion($assessment);
        }

        $discrimination = $this->discriminationNextQuestion($assessment);
        if (is_array($discrimination)) {
            return $discrimination;
        }

        return $this->configuredNextQuestion($assessment, false);
    }

    private function discriminationNextQuestion(AdaptiveAssessment $assessment): ?array
    {
        $initialCategoryId = MainSymptom::query()
            ->whereKey($assessment->initial_symptom_id)
            ->value('symptom_category_id');
        $candidateIds = $this->rankedCandidates($assessment)
            ->take((int) config('adaptive_assessment.candidate_limit', 5))
            ->pluck('disease.disease_id')
            ->values();
        if ($candidateIds->isEmpty()) {
            return null;
        }

        $evidenceSymptomIds = $this->evidenceAnswers($assessment)->keys();
        $answeredQuestionIds = $assessment->answers()
            ->whereNotNull('adaptive_question_id')
            ->pluck('adaptive_question_id');

        $relationships = DB::table('disease_symptoms as ds')
            ->join('main_symptoms as symptom', 'symptom.symptom_id', '=', 'ds.symptom_id')
            ->whereIn('ds.disease_id', $candidateIds)
            ->where('symptom.status', '1')
            ->whereIn('ds.evidence_status', self::ASKABLE_EVIDENCE_STATUSES)
            ->whereNotIn('ds.symptom_id', $evidenceSymptomIds)
            ->get([
                'ds.disease_id',
                'ds.symptom_id',
                'ds.assessment_weight',
                'ds.is_key_symptom',
                'symptom.symptom_category_id',
            ]);
        $candidateSymptomIds = $relationships->pluck('symptom_id')->unique()->values();
        if ($candidateSymptomIds->isEmpty()) {
            return null;
        }

        $questions = AdaptiveQuestion::query()
            ->where('status', 'approved')
            ->whereNotIn('id', $answeredQuestionIds)
            ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX.'%')
            ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX_TH.'%')
            ->whereHas('symptoms', fn ($query) => $query->whereIn(
                'main_symptoms.symptom_id',
                $candidateSymptomIds,
            ))
            ->whereHas('rules', fn ($query) => $query
                ->where('status', '1')
                ->whereIn('evidence_status', self::ASKABLE_EVIDENCE_STATUSES))
            ->with([
                'symptoms:symptom_id',
                'options' => fn ($query) => $query->where('status', '1'),
                'rules' => fn ($query) => $query
                    ->where('status', '1')
                    ->whereIn('evidence_status', self::ASKABLE_EVIDENCE_STATUSES),
            ])
            ->get()
            // A multi-symptom question must not overwrite evidence already
            // collected for any of its mapped symptoms.
            ->reject(fn (AdaptiveQuestion $question) => $question->symptoms
                ->pluck('symptom_id')
                ->intersect($evidenceSymptomIds)
                ->isNotEmpty());

        $candidateCount = $candidateIds->count();
        $stageOrder = ['local' => 1, 'associated' => 2, 'safety' => 3];

        $rankedQuestions = $questions->flatMap(function (AdaptiveQuestion $question) use (
            $relationships,
            $candidateSymptomIds,
            $candidateCount,
            $stageOrder,
            $initialCategoryId,
        ) {
            $rule = $question->rules->sortBy(fn (AdaptiveQuestionRule $item) => sprintf(
                '%d-%d-%03d-%06d',
                $item->is_required ? 0 : 1,
                $stageOrder[$item->question_stage] ?? 9,
                $item->priority,
                $item->id,
            ))->first();

            return $question->symptoms
                ->whereIn('symptom_id', $candidateSymptomIds)
                ->map(function (MainSymptom $symptom) use (
                    $question,
                    $rule,
                    $relationships,
                    $candidateCount,
                    $stageOrder,
                    $initialCategoryId,
                ) {
                    $links = $relationships->where('symptom_id', $symptom->symptom_id);
                    $presentCount = $links->pluck('disease_id')->unique()->count();
                    $absentCount = $candidateCount - $presentCount;

                    return [
                        'question' => $question,
                        'symptom_id' => $symptom->symptom_id,
                        // A balanced split maximizes this value. Shared-by-all
                        // symptoms score zero and are used only for one candidate.
                        'split_score' => min($presentCount, $absentCount),
                        'specificity' => $candidateCount > 0
                            ? 1 - ($presentCount / $candidateCount)
                            : 0,
                        'key_count' => $links->where('is_key_symptom', true)->count(),
                        'weight' => (float) $links->max('assessment_weight'),
                        'same_category' => $initialCategoryId !== null
                            && $links->contains('symptom_category_id', $initialCategoryId),
                        'required_order' => $rule?->is_required ? 0 : 1,
                        'stage_order' => $stageOrder[$rule?->question_stage] ?? 9,
                        'priority' => $rule?->priority ?? 999,
                    ];
                });
        });

        $best = $rankedQuestions
            ->filter(fn (array $item) => $candidateCount === 1 || $item['split_score'] > 0)
            ->sort(function (array $left, array $right): int {
                foreach ([
                    ['split_score', 'desc'],
                    ['same_category', 'desc'],
                    ['key_count', 'desc'],
                    ['weight', 'desc'],
                    ['specificity', 'desc'],
                    ['required_order', 'asc'],
                    ['stage_order', 'asc'],
                    ['priority', 'asc'],
                ] as [$key, $direction]) {
                    $comparison = $left[$key] <=> $right[$key];
                    if ($comparison !== 0) {
                        return $direction === 'desc' ? -$comparison : $comparison;
                    }
                }

                return $left['question']->id <=> $right['question']->id;
            })
            ->first();

        return $best
            ? $this->formatConfiguredQuestion($best['question'], $assessment, 'discrimination')
            : null;
    }

    /** Return the next configured required or optional question. */
    private function configuredNextQuestion(
        AdaptiveAssessment $assessment,
        bool $required,
    ): ?array {
        $rules = AdaptiveQuestionRule::query()
            ->where('initial_symptom_id', $assessment->initial_symptom_id)
            ->where('is_required', $required)
            ->where('status', '1')
            ->whereIn('evidence_status', self::ASKABLE_EVIDENCE_STATUSES)
            ->whereHas('question', fn ($query) => $query
                ->where('status', 'approved')
                ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX.'%')
                ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX_TH.'%'))
            ->with([
                'question.symptoms:symptom_id',
                'question.options' => fn ($query) => $query->where('status', '1'),
            ])
            ->get();

        $answeredQuestionIds = $assessment->answers()
            ->whereNotNull('adaptive_question_id')
            ->pluck('adaptive_question_id');
        $remaining = $rules->whereNotIn('adaptive_question_id', $answeredQuestionIds);
        if ($remaining->isEmpty()) {
            return null;
        }

        $stageOrder = ['local' => 1, 'associated' => 2, 'safety' => 3];
        $next = $remaining->sortBy(fn (AdaptiveQuestionRule $rule) => sprintf(
            '%d-%d-%03d-%06d',
            $rule->is_required ? 0 : 1,
            $stageOrder[$rule->question_stage] ?? 9,
            $rule->priority,
            $rule->id,
        ))->first();

        return $this->formatConfiguredQuestion($next->question, $assessment, 'frame');
    }

    private function formatConfiguredQuestion(
        AdaptiveQuestion $question,
        AdaptiveAssessment $assessment,
        string $phase = 'frame',
    ): array {
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
            'phase' => $phase,
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

    private function hasEnoughSeparatingEvidence(AdaptiveAssessment $assessment): bool
    {
        $definiteAnswers = $assessment->answers()->whereIn('answer', ['yes', 'no'])->count();
        if ($definiteAnswers < (int) config('adaptive_assessment.minimum_clear_answers', 5)) {
            return false;
        }

        $ranked = $this->rankedCandidates($assessment);
        $top = $ranked->first();
        if (! $top || $top['supporting_yes_count'] < max(
            1,
            (int) ($top['disease']->minimum_supporting_symptoms ?? 1),
        )) {
            return false;
        }

        if ($ranked->count() < 2) {
            return true;
        }

        return $this->compareCandidateEvidence($ranked[0], $ranked[1]) > 0;
    }

    private function rankedCandidates(AdaptiveAssessment $assessment)
    {
        $answers = $this->evidenceAnswers($assessment);

        $candidates = Disease::query()
            ->where('status', '1')
            ->whereHas('symptoms', fn ($q) => $q->where('main_symptoms.symptom_id', $assessment->initial_symptom_id))
            ->with('symptoms:symptom_id')
            ->get();

        $candidateCount = $candidates->count();
        $yesAnswers = $answers->filter(fn (string $answer) => $answer === 'yes');
        $symptomFrequency = $yesAnswers->mapWithKeys(function (string $answer, string $symptomId) use ($candidates) {
            return [
                $symptomId => $candidates->filter(
                    fn (Disease $disease) => $disease->symptoms->contains('symptom_id', $symptomId),
                )->count(),
            ];
        });
        $evidenceWeights = $symptomFrequency->map(
            // This is a statistical specificity weight, not a medical
            // probability: symptoms shared by fewer candidates distinguish
            // those candidates more than symptoms shared by nearly all of them.
            fn (int $frequency) => log(($candidateCount + 1) / ($frequency + 1)) + 1,
        );
        $possibleEvidence = (float) $evidenceWeights->sum();

        return $candidates
            ->map(function (Disease $disease) use (
                $answers,
                $evidenceWeights,
                $possibleEvidence,
            ) {
                $symptoms = $disease->symptoms->keyBy('symptom_id');
                $supportScore = 0.0;
                $contradictionScore = 0.0;
                $supportingYesCount = 0;
                $supportingKeyYesCount = 0;
                foreach ($answers as $symptomId => $answer) {
                    if ($answer === 'unsure') {
                        continue;
                    }

                    $linkedSymptom = $symptoms->get($symptomId);
                    if ($answer === 'yes') {
                        if ($linkedSymptom) {
                            $specificityWeight = (float) ($evidenceWeights->get($symptomId) ?? 1.0);
                            $assessmentWeight = max(0.0, (float) ($linkedSymptom->pivot->assessment_weight ?? 1));
                            $supportScore += $specificityWeight * $assessmentWeight;
                            $supportingYesCount++;
                            if ((bool) ($linkedSymptom->pivot->is_key_symptom ?? false)) {
                                $supportingKeyYesCount++;
                            }
                        }
                    } elseif (
                        $answer === 'no'
                        && $linkedSymptom
                        && (bool) ($linkedSymptom->pivot->is_key_symptom ?? false)
                    ) {
                        // A negative answer only reduces support when a
                        // clinician has explicitly marked this as a key
                        // symptom and supplied an absence penalty.
                        $contradictionScore += max(0.0, (float) ($linkedSymptom->pivot->absence_penalty ?? 0));
                    }
                }

                $rankingScore = max(0.0, $supportScore - $contradictionScore);
                $matchPercent = $possibleEvidence > 0
                    ? (int) round(($rankingScore / $possibleEvidence) * 100)
                    : 0;

                return [
                    'disease' => $disease,
                    'match_percent' => min(100, $matchPercent),
                    'supporting_yes_count' => $supportingYesCount,
                    'supporting_key_yes_count' => $supportingKeyYesCount,
                    // This count is displayed as "matched symptoms / symptoms
                    // of this disease". It must therefore be disease-specific,
                    // not the number of clear answers collected globally.
                    'evaluated_symptom_count' => $symptoms->count(),
                    'ranking_score' => $rankingScore,
                ];
            })
            ->sort(fn (array $left, array $right) => $this->compareCandidateEvidence($right, $left))
            ->values();
    }

    private function compareCandidateEvidence(array $left, array $right): int
    {
        foreach (['supporting_key_yes_count', 'ranking_score', 'supporting_yes_count'] as $key) {
            $comparison = ($left[$key] ?? 0) <=> ($right[$key] ?? 0);
            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    private function evidenceAnswers(AdaptiveAssessment $assessment)
    {
        // Selecting the initial symptom is an explicit present observation. It
        // supports disease scoring but is not stored as an answered question.
        $evidence = collect([$assessment->initial_symptom_id => 'yes']);
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

        // The initial symptom only selects the candidate pool. Each condition
        // controls how many subsequent "yes" answers are required before it
        // is shown. The default remains one for existing records until an
        // administrator configures a reviewed condition-specific minimum.
        $rankedCandidates = $this->rankedCandidates($assessment);
        $diseases = $mostlyUnsure
            ? collect()
            : $rankedCandidates
                ->filter(fn (array $item) => $item['supporting_yes_count'] >= max(
                    1,
                    (int) ($item['disease']->minimum_supporting_symptoms ?? 1),
                ))
                ->values();

        // If nothing reaches its configured display threshold, show only the
        // strongest candidate when there is at least one positive symptom.
        // Responses expose that it is below threshold; zero-support results
        // remain empty so a condition is never inferred without evidence.
        if ($diseases->isEmpty()) {
            $fallback = $rankedCandidates->first(
                fn (array $item) => $item['supporting_yes_count'] > 0,
            );
            if ($fallback) {
                $diseases = collect([$fallback]);
            }
        }

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
        $evidence = $this->evidenceAnswers($assessment);
        $symptomNames = MainSymptom::query()
            ->whereIn('symptom_id', $evidence->keys())
            ->pluck('symptom_name', 'symptom_id');
        $ranked = $this->rankedCandidates($assessment)->keyBy('disease.disease_id');

        return $assessment->results()->with('disease.symptoms')->get()->map(function ($result) use (
            $evidence,
            $symptomNames,
            $ranked,
        ) {
            $diseaseSymptoms = $result->disease?->symptoms->keyBy('symptom_id') ?? collect();
            $supportingIds = $evidence
                ->filter(fn (string $answer, string $symptomId) => $answer === 'yes'
                    && $diseaseSymptoms->has($symptomId))
                ->keys();
            $keySupportingIds = $supportingIds->filter(
                fn (string $symptomId) => (bool) $diseaseSymptoms->get($symptomId)?->pivot?->is_key_symptom,
            );
            $rankedItem = $ranked->get($result->disease_id);

            return [
                'disease_id' => $result->disease_id,
                'disease_name' => $result->disease_name,
                'match_percent' => $result->match_percent,
                'supporting_symptom_count' => $result->supporting_symptom_count,
                'evaluated_symptom_count' => $result->evaluated_symptom_count,
                'meets_minimum_support' => $result->supporting_symptom_count >= max(
                    1,
                    (int) ($result->disease?->minimum_supporting_symptoms ?? 1),
                ),
                'supporting_symptoms' => $supportingIds
                    ->map(fn (string $symptomId) => [
                        'symptom_id' => $symptomId,
                        'symptom_name' => $symptomNames->get($symptomId),
                    ])->values()->all(),
                'key_supporting_symptoms' => $keySupportingIds
                    ->map(fn (string $symptomId) => [
                        'symptom_id' => $symptomId,
                        'symptom_name' => $symptomNames->get($symptomId),
                    ])->values()->all(),
                'score_components' => [
                    'supporting_symptoms' => $rankedItem['supporting_yes_count'] ?? 0,
                    'key_supporting_symptoms' => $rankedItem['supporting_key_yes_count'] ?? 0,
                    'evidence_score' => round((float) ($rankedItem['ranking_score'] ?? 0), 4),
                ],
                'has_article' => $result->disease_id !== null,
            ];
        })->all();
    }

    private function formatEvidenceSummary(AdaptiveAssessment $assessment): array
    {
        $evidence = $this->evidenceAnswers($assessment);
        $symptoms = MainSymptom::query()
            ->whereIn('symptom_id', $evidence->keys())
            ->pluck('symptom_name', 'symptom_id');
        $frameQuestionIds = AdaptiveQuestionRule::query()
            ->where('initial_symptom_id', $assessment->initial_symptom_id)
            ->where('status', '1')
            ->whereIn('evidence_status', self::ASKABLE_EVIDENCE_STATUSES)
            ->pluck('adaptive_question_id');
        $answers = $assessment->answers()->get();

        $items = fn (string $answer) => $evidence
            ->filter(fn (string $value) => $value === $answer)
            ->map(fn (string $value, string $symptomId) => [
                'symptom_id' => $symptomId,
                'symptom_name' => $symptoms->get($symptomId),
                'source' => $symptomId === $assessment->initial_symptom_id ? 'initial' : 'answer',
            ])->values()->all();

        return [
            'initial_symptom_id' => $assessment->initial_symptom_id,
            'initial_symptom_name' => $symptoms->get($assessment->initial_symptom_id),
            'answered_question_count' => $answers->count(),
            'frame_question_count' => $answers
                ->whereIn('adaptive_question_id', $frameQuestionIds)
                ->count(),
            'adaptive_question_count' => $answers
                ->whereNotNull('adaptive_question_id')
                ->whereNotIn('adaptive_question_id', $frameQuestionIds)
                ->count(),
            'present_symptoms' => $items('yes'),
            'absent_symptoms' => $items('no'),
            'unknown_symptoms' => $items('unsure'),
        ];
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
