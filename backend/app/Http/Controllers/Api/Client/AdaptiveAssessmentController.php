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
            ->reject(fn (string $symptomId) => $symptomId === $assessment->initial_symptom_id)
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
            // With multiple candidates, skip symptoms shared by all of them
            // because those questions cannot narrow the ranking. With one
            // candidate, keep its unasked symptoms so the assessment can
            // collect enough supporting evidence instead of ending at once.
            ->filter(fn (MainSymptom $item) => $candidateIds->count() === 1
                || $item->diseases_count < $candidateIds->count())
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
        $unansweredRules = $rules->whereNotIn('adaptive_question_id', $answeredQuestionIds);
        $remaining = $unansweredRules
            ->reject(function (AdaptiveQuestionRule $rule) use ($assessment) {
                $symptomIds = $rule->question->symptoms->pluck('symptom_id');
                if ($symptomIds->isEmpty()) {
                    $symptomIds->push($rule->question->question_symptom_id);
                }

                // The user already confirmed the initial symptom before the
                // adaptive flow started, so asking it again adds duplicate
                // evidence and can inflate every matching condition equally.
                return $symptomIds->contains($assessment->initial_symptom_id);
            })
            ->values();
        if ($remaining->isEmpty()) {
            // A bank containing only a question about the already selected
            // initial symptom is not useful as a follow-up bank. Let the
            // disease-symptom fallback find another associated symptom.
            $hasUsableConfiguredQuestion = $rules->contains(function (AdaptiveQuestionRule $rule) use ($assessment) {
                $symptomIds = $rule->question->symptoms->pluck('symptom_id');
                if ($symptomIds->isEmpty()) {
                    $symptomIds->push($rule->question->question_symptom_id);
                }

                return ! $symptomIds->contains($assessment->initial_symptom_id);
            });

            return $hasUsableConfiguredQuestion ? null : false;
        }

        $stageOrder = ['local' => 1, 'associated' => 2, 'safety' => 3];
        $requiredRule = $remaining
            ->where('is_required', true)
            ->sortBy(fn ($item) => sprintf(
                '%d-%03d-%06d',
                $stageOrder[$item->question_stage] ?? 9,
                $item->priority,
                $item->id,
            ))
            ->first();
        if ($requiredRule) {
            return $this->formatConfiguredQuestion($requiredRule->question, $assessment);
        }

        $candidateIds = $this->rankedCandidates($assessment)
            ->take(5)
            ->pluck('disease.disease_id')
            ->values();
        if ($candidateIds->isEmpty()) {
            return null;
        }

        if ($candidateIds->count() === 1) {
            $candidateSymptomIds = DB::table('disease_symptoms')
                ->where('disease_id', $candidateIds->first())
                ->pluck('symptom_id');
            $rule = $remaining
                ->filter(function ($item) use ($candidateSymptomIds) {
                    $symptomIds = $item->question->symptoms->pluck('symptom_id')
                        ->whenEmpty(fn ($ids) => $ids->push($item->question->question_symptom_id));

                    return $symptomIds->intersect($candidateSymptomIds)->isNotEmpty();
                })
                ->sortBy(fn ($item) => sprintf(
                    '%d-%03d-%06d',
                    $stageOrder[$item->question_stage] ?? 9,
                    $item->priority,
                    $item->id,
                ))
                ->first();

            return $rule
                ? $this->formatConfiguredQuestion($rule->question, $assessment)
                : false;
        }

        $targetSymptomIds = $remaining
            ->flatMap(fn ($item) => $item->question->symptoms->pluck('symptom_id')
                ->whenEmpty(fn ($ids) => $ids->push($item->question->question_symptom_id)))
            ->unique()
            ->values();
        $diseasesBySymptom = DB::table('disease_symptoms')
            ->whereIn('disease_id', $candidateIds)
            ->whereIn('symptom_id', $targetSymptomIds)
            ->get(['disease_id', 'symptom_id'])
            ->groupBy('symptom_id');
        $idealSplit = $candidateIds->count() / 2;

        // Prefer a reviewed question that divides the leading candidates as
        // evenly as possible. Questions shared by none or all of the leading
        // candidates cannot narrow the result and are skipped.
        $rule = $remaining
            ->map(function ($item) use ($diseasesBySymptom, $idealSplit, $stageOrder) {
                $symptomIds = $item->question->symptoms->pluck('symptom_id')
                    ->whenEmpty(fn ($ids) => $ids->push($item->question->question_symptom_id));
                $linkedDiseaseCount = $symptomIds
                    ->flatMap(fn ($symptomId) => $diseasesBySymptom->get($symptomId, collect())->pluck('disease_id'))
                    ->unique()
                    ->count();

                return [
                    'rule' => $item,
                    'linked_disease_count' => $linkedDiseaseCount,
                    'split_distance' => abs($linkedDiseaseCount - $idealSplit),
                    'stage_order' => $stageOrder[$item->question_stage] ?? 9,
                ];
            })
            ->filter(fn ($item) => $item['linked_disease_count'] > 0
                && $item['linked_disease_count'] < $candidateIds->count())
            ->sortBy(fn ($item) => sprintf(
                '%09.3f-%d-%03d-%06d',
                $item['split_distance'],
                $item['stage_order'],
                $item['rule']->priority,
                $item['rule']->id,
            ))
            ->value('rule');

        if (! $rule) {
            return null;
        }

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

        return $this->compareCandidateEvidence($ranked[0], $ranked[1]) > 0;
    }

    private function rankedCandidates(AdaptiveAssessment $assessment)
    {
        $answers = $this->evidenceAnswers($assessment);
        $evaluatedSymptomCount = $answers->filter(
            fn (string $answer) => in_array($answer, ['yes', 'no'], true),
        )->count();

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
                $evaluatedSymptomCount,
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
                    'evaluated_symptom_count' => $evaluatedSymptomCount,
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

        // The initial symptom only selects the candidate pool. Each condition
        // controls how many subsequent "yes" answers are required before it
        // is shown. The default remains one for existing records until an
        // administrator configures a reviewed condition-specific minimum.
        $diseases = $mostlyUnsure
            ? collect()
            : $this->rankedCandidates($assessment)
                ->filter(fn (array $item) => $item['supporting_yes_count'] >= max(
                    1,
                    (int) ($item['disease']->minimum_supporting_symptoms ?? 1),
                ))
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
