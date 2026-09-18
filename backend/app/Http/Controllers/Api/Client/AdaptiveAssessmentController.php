<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\AdaptiveAssessment;
use App\Models\AdaptiveAssessmentAnswer;
use App\Models\AdaptiveAssessmentResult;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Disease;
use App\Models\MainSymptom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdaptiveAssessmentController extends Controller
{
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
            'symptom_id' => 'required|exists:main_symptoms,symptom_id',
            'answer' => 'required|in:yes,no,unsure',
        ]);

        AdaptiveAssessmentAnswer::updateOrCreate(
            ['adaptive_assessment_id' => $adaptiveAssessment->id, 'symptom_id' => $validated['symptom_id']],
            ['answer' => $validated['answer']],
        );
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
        $lastAnswer->delete();
        $adaptiveAssessment->update(['question_count' => $adaptiveAssessment->answers()->count()]);

        return response()->json([
            'status' => 'question',
            'question' => $this->formatQuestion($symptom, $adaptiveAssessment->fresh()),
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

        $symptom = MainSymptom::query()
            ->where('status', '1')
            ->whereNotIn('symptom_id', $answeredIds)
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
        $answers = $assessment->answers()->get()->keyBy('symptom_id');

        return Disease::query()
            ->where('status', '1')
            ->whereHas('symptoms', fn ($q) => $q->where('main_symptoms.symptom_id', $assessment->initial_symptom_id))
            ->with('symptoms:symptom_id')
            ->get()
            ->map(function (Disease $disease) use ($answers) {
                $symptoms = $disease->symptoms->keyBy('symptom_id');
                $positiveEvidence = 1.0;
                $possibleEvidence = 1.0;
                foreach ($answers as $answer) {
                    if ($answer->answer === 'unsure') {
                        continue;
                    }

                    $linkedSymptom = $symptoms->get($answer->symptom_id);
                    if ($answer->answer === 'yes') {
                        $possibleEvidence += 1.0;
                        if ($linkedSymptom) {
                            $positiveEvidence += max(0.0, (float) ($linkedSymptom->pivot->assessment_weight ?? 1));
                        }
                    } elseif (
                        $answer->answer === 'no'
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

                return ['disease' => $disease, 'match_percent' => min(100, $matchPercent)];
            })
            ->sortByDesc('match_percent')
            ->values();
    }

    private function complete(AdaptiveAssessment $assessment): array
    {
        $rankedDiseases = $this->rankedCandidates($assessment);

        // Return only the best-supported condition(s). Equal top scores are
        // kept because the available answers cannot safely distinguish them.
        $bestPercent = $rankedDiseases->max('match_percent');
        $diseases = $rankedDiseases
            ->filter(fn (array $item) => $item['match_percent'] === $bestPercent)
            ->take(5)
            ->values();

        AdaptiveAssessmentResult::where('adaptive_assessment_id', $assessment->id)->delete();
        foreach ($diseases as $index => $item) {
            AdaptiveAssessmentResult::create([
                'adaptive_assessment_id' => $assessment->id,
                'disease_id' => $item['disease']->disease_id,
                'disease_name' => $item['disease']->disease_name,
                'match_percent' => $item['match_percent'],
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
                    'recommendation' => 'ผลคัดกรองจากระบบประเมินแบบคำถาม กรุณาพิจารณาร่วมกับอาการจริงและคำแนะนำจากบุคลากรทางการแพทย์',
                    'rule_id' => null,
                ],
            );

            $result->diseases()->sync(
                $diseases->mapWithKeys(fn (array $item, int $index) => [
                    $item['disease']->disease_id => ['display_order' => $index],
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
