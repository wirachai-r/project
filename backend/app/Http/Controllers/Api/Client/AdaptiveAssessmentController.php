<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\AdaptiveAssessment;
use App\Models\AdaptiveAssessmentAnswer;
use App\Models\AdaptiveAssessmentResult;
use App\Models\Disease;
use App\Models\MainSymptom;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdaptiveAssessmentController extends Controller
{
    private const MAX_QUESTIONS = 7;

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

            return response()->json(['assessment_id' => $assessment->id, 'session_token' => $sessionToken, 'status' => 'completed', 'results' => $results]);
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

        $question = $adaptiveAssessment->question_count < self::MAX_QUESTIONS
            ? $this->nextQuestion($adaptiveAssessment->fresh())
            : null;
        if ($question) {
            return response()->json(['status' => 'question', 'question' => $question]);
        }

        return response()->json(['status' => 'completed', 'results' => $this->complete($adaptiveAssessment->fresh())]);
    }

    public function result(Request $request, AdaptiveAssessment $adaptiveAssessment)
    {
        $this->authorizeAssessment($request, $adaptiveAssessment);
        abort_if($adaptiveAssessment->status !== 'completed', 422, 'การประเมินยังไม่สิ้นสุด');

        return response()->json(['assessment_id' => $adaptiveAssessment->id, 'results' => $this->formatResults($adaptiveAssessment)]);
    }

    private function nextQuestion(AdaptiveAssessment $assessment): ?array
    {
        $answeredIds = $assessment->answers()->pluck('symptom_id')->push($assessment->initial_symptom_id);
        $candidateIds = Disease::query()
            ->where('status', '1')
            ->whereHas('symptoms', fn ($q) => $q->where('main_symptoms.symptom_id', $assessment->initial_symptom_id))
            ->pluck('disease_id');

        $symptom = MainSymptom::query()
            ->where('status', '1')
            ->whereNotIn('symptom_id', $answeredIds)
            ->whereHas('diseases', fn ($q) => $q->whereIn('diseases.disease_id', $candidateIds))
            ->withCount(['diseases' => fn ($q) => $q->whereIn('diseases.disease_id', $candidateIds)])
            ->orderByDesc('diseases_count')
            ->orderBy('symptom_name')
            ->first();

        if (! $symptom) {
            return null;
        }

        return [
            'symptom_id' => $symptom->symptom_id,
            'text' => "มีอาการ{$symptom->symptom_name}ร่วมด้วยหรือไม่?",
            'detail' => 'เลือกคำตอบที่ใกล้เคียงกับอาการในขณะนี้มากที่สุด',
            'number' => $assessment->question_count + 1,
            'maximum' => self::MAX_QUESTIONS,
        ];
    }

    private function complete(AdaptiveAssessment $assessment): array
    {
        $answers = $assessment->answers()->get()->keyBy('symptom_id');
        $diseases = Disease::query()
            ->where('status', '1')
            ->whereHas('symptoms', fn ($q) => $q->where('main_symptoms.symptom_id', $assessment->initial_symptom_id))
            ->with('symptoms:symptom_id')
            ->get()
            ->map(function (Disease $disease) use ($answers) {
                $symptomIds = $disease->symptoms->pluck('symptom_id');
                $known = 1;
                $matched = 1;
                foreach ($answers as $answer) {
                    if ($answer->answer === 'unsure') {
                        continue;
                    }
                    $known++;
                    $hasSymptom = $symptomIds->contains($answer->symptom_id);
                    if (($answer->answer === 'yes' && $hasSymptom) || ($answer->answer === 'no' && ! $hasSymptom)) {
                        $matched++;
                    }
                }

                return ['disease' => $disease, 'match_percent' => (int) round(($matched / $known) * 100)];
            })
            ->sortByDesc('match_percent')
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

        return $this->formatResults($assessment->fresh());
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
