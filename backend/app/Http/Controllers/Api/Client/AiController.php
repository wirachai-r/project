<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\AnswerClarificationQuestionRequest;
use App\Http\Requests\Client\ClarifyAssessmentQuestionRequest;
use App\Models\AiClarificationAnswer;
use App\Models\AiClarificationChoice;
use App\Models\AiClarificationQuestion;
use App\Models\AiClarificationSession;
use App\Models\Assessment;
use App\Models\DailyHealthRecord;
use App\Models\FollowUpEntry;
use App\Models\QuestionBox;
use App\Services\Ai\AssessmentClarificationService;
use App\Services\Ai\AssessmentGuidanceService;
use App\Services\Ai\HealthTrendSummaryService;
use App\Services\HealthTrendStatistics;
use App\Support\HealthTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AiController extends Controller
{
    public function clarifyQuestion(
        ClarifyAssessmentQuestionRequest $request,
        Assessment $assessment,
        AssessmentClarificationService $service,
    ) {
        $this->ensureEnabled();
        $this->authorizeAssessment($request, $assessment);
        abort_if($assessment->assessment_status !== 'P', 422, 'assessment นี้ไม่ได้อยู่ระหว่างดำเนินการ');

        $box = QuestionBox::query()
            ->whereKey($request->validated('box_id'))
            ->where('diagram_id', $assessment->diagram_id)
            ->firstOrFail();

        abort_if(
            AiClarificationSession::query()
                ->where('assessment_id', $assessment->id)
                ->where('box_id', $box->box_id)
                ->where('status', 'unresolved')
                ->exists(),
            422,
            'ใช้คำถามช่วยสำหรับคำถามนี้ครบแล้ว กรุณาเลือกคำตอบหลักหรือย้อนกลับ'
        );

        $session = AiClarificationSession::query()
            ->where('assessment_id', $assessment->id)
            ->where('box_id', $box->box_id)
            ->where('status', 'active')
            ->latest('id')
            ->first();
        if (! $session) {
            $session = AiClarificationSession::create([
                'assessment_id' => $assessment->id,
                'box_id' => $box->box_id,
                'status' => 'active',
                'expires_at' => now()->addDays(30),
            ]);
        }

        $pendingQuestion = $session->questions()
            ->whereDoesntHave('answer')
            ->with('choices')
            ->latest('sequence')
            ->first();
        if ($pendingQuestion) {
            return response()->json(['data' => $this->formatClarificationQuestion(
                $session,
                $pendingQuestion,
            )]);
        }

        $attempt = $session->questions()->count() + 1;
        abort_if($attempt > config('ai.max_clarification_attempts'), 429, 'ใช้ตัวช่วยสำหรับคำถามนี้ครบแล้ว');

        $previousClarifications = $session->questions()
            ->with(['answer.choice', 'choices'])
            ->orderBy('sequence')
            ->get()
            ->filter(fn ($item) => $item->answer?->choice)
            ->map(fn ($item) => [
                'question' => $item->question_text,
                'answer' => $item->answer->choice->choice_text,
            ])
            ->values()
            ->all();
        $result = $service->clarify(
            $box,
            $request->validated('message'),
            $previousClarifications,
            $this->clarificationAssessmentContext($assessment),
        );
        $question = DB::transaction(function () use ($session, $result, $attempt) {
            $question = $session->questions()->create([
                'question_text' => $result['question_text'],
                'explanation' => $result['explanation'],
                'source' => 'ai',
                'sequence' => $attempt,
            ]);
            foreach ($result['choices'] as $index => $choice) {
                $question->choices()->create([
                    'external_id' => $choice['id'],
                    'choice_text' => $choice['label'],
                    'maps_to' => $choice['maps_to'],
                    'maps_to_choice_id' => $choice['maps_to_choice_id'] ?: null,
                    'sequence' => $index + 1,
                ]);
            }

            return $question->load('choices');
        });

        return response()->json(['data' => $this->formatClarificationQuestion(
            $session,
            $question,
        )]);
    }

    private function clarificationAssessmentContext(Assessment $assessment): array
    {
        $assessment->loadMissing(['symptom', 'answers.box', 'answers.choice']);

        return [
            'current_symptom' => $assessment->symptom?->symptom_name,
            'answered_questions' => $assessment->answers->map(fn ($answer) => [
                'question' => $answer->box?->question_text,
                'answer' => $answer->choice?->choice_text,
            ])->filter(fn (array $item) => $item['question'] !== null && $item['answer'] !== null)
                ->values()
                ->all(),
        ];
    }

    private function formatClarificationQuestion(
        AiClarificationSession $session,
        AiClarificationQuestion $question,
    ): array {
        $history = AiClarificationQuestion::query()
            ->whereHas('session', fn ($query) => $query
                ->where('assessment_id', $session->assessment_id)
                ->where('box_id', $session->box_id))
            ->with(['answer.choice', 'choices'])
            ->orderBy('session_id')
            ->orderBy('sequence')
            ->get()
            ->filter(fn ($item) => $item->answer?->choice)
            ->map(fn ($item) => [
                'question_id' => $item->id,
                'question_text' => $item->question_text,
                'answer_text' => $item->answer->choice->choice_text,
                'selected_choice_id' => $item->answer->choice_id,
                'attempt' => $item->sequence,
                'choices' => $item->choices->map(fn ($choice) => [
                    'id' => $choice->id,
                    'label' => $choice->choice_text,
                    'maps_to' => $choice->maps_to,
                    'maps_to_choice_id' => $choice->maps_to_choice_id,
                ])->values(),
            ])
            ->values();

        return [
            'session_id' => $session->id,
            'question_id' => $question->id,
            'attempt' => $question->sequence,
            'max_attempts' => (int) config('ai.max_clarification_attempts'),
            'question_text' => $question->question_text,
            'explanation' => $question->explanation,
            'choices' => $question->choices->map(fn ($choice) => [
                'id' => $choice->id,
                'label' => $choice->choice_text,
                'maps_to' => $choice->maps_to,
                'maps_to_choice_id' => $choice->maps_to_choice_id,
            ]),
            'requires_user_confirmation' => true,
            'history' => $history,
        ];
    }

    public function answerClarification(
        AnswerClarificationQuestionRequest $request,
        AiClarificationQuestion $question,
    ) {
        $question->load(['session.assessment', 'session.box.choices']);
        $this->authorizeAssessment($request, $question->session->assessment);
        abort_if($question->session->assessment->assessment_status !== 'P', 422, 'assessment นี้ไม่ได้อยู่ระหว่างดำเนินการ');
        if ($question->session->status !== 'active') {
            $question->session->update([
                'status' => 'active', 'resolved_to' => null, 'resolved_at' => null,
            ]);
        }

        $choice = AiClarificationChoice::query()
            ->whereKey($request->validated('choice_id'))
            ->where('question_id', $question->id)
            ->firstOrFail();

        AiClarificationAnswer::updateOrCreate(
            ['question_id' => $question->id],
            ['choice_id' => $choice->id, 'answered_at' => now()],
        );

        $mapsTo = $choice->maps_to;
        $mapsToChoiceId = $choice->maps_to_choice_id;
        $mainChoices = $question->session->box->choices
            ->where('status', '1')
            ->sortBy('order')
            ->values();
        if ($mainChoices->count() === 2 && $choice->sequence <= 2) {
            $mapsTo = $choice->sequence === 1 ? 'yes' : 'no';
            $mapsToChoiceId = $mainChoices[$choice->sequence - 1]->choice_id;
        }

        $isLastAttempt = $question->sequence >= config('ai.max_clarification_attempts');
        if ($mapsTo === 'requires_user_choice' && $isLastAttempt) {
            $question->session->update([
                'status' => 'unresolved', 'resolved_to' => null, 'resolved_at' => now(),
            ]);
        }

        return response()->json(['data' => [
            'status' => $question->session->fresh()->status,
            'maps_to' => $mapsTo,
            'maps_to_choice_id' => $mapsToChoiceId,
            'attempt' => $question->sequence,
            'can_retry' => ! $isLastAttempt,
        ]]);
    }

    public function markClarificationUnresolved(Request $request, AiClarificationSession $session)
    {
        $session->load('assessment');
        $this->authorizeAssessment($request, $session->assessment);
        abort_if($session->status !== 'active', 422, 'ตัวช่วยสำหรับคำถามนี้สิ้นสุดแล้ว');
        abort_if(
            $session->questions()->count() < config('ai.max_clarification_attempts'),
            422,
            'ยังสามารถใช้คำถามช่วยรอบถัดไปได้'
        );
        $session->update([
            'status' => 'unresolved', 'resolved_to' => null, 'resolved_at' => now(),
        ]);

        return response()->json(['data' => ['status' => 'unresolved']]);
    }

    public function guidance(Request $request, Assessment $assessment, AssessmentGuidanceService $service)
    {
        $this->ensureEnabled();
        $this->authorizeAssessment($request, $assessment);
        abort_if($assessment->assessment_status !== 'C', 422, 'assessment ยังไม่เสร็จสิ้น');

        $cached = $assessment->aiGuidance()->first();
        if ($cached && data_get($cached->content, 'guidance_version') === AssessmentGuidanceService::VERSION) {
            return response()->json(['data' => [
                ...$cached->content,
                'cached' => true,
                'generated_at' => $cached->created_at?->toIso8601String(),
            ]]);
        }
        $cached?->delete();

        $content = $service->generate($assessment);
        $content['guidance_version'] = AssessmentGuidanceService::VERSION;
        $guidance = $assessment->aiGuidance()->create([
            'content' => $content,
            'provider' => config('ai.provider'),
            'model' => config('ai.model'),
        ]);

        return response()->json(['data' => [
            ...$content,
            'cached' => false,
            'generated_at' => $guidance->created_at?->toIso8601String(),
        ]]);
    }

    public function healthTrendSummary(
        Request $request,
        HealthTrendSummaryService $service,
        HealthTrendStatistics $statistics,
    ) {
        $this->ensureEnabled();
        $validated = $request->validate([
            'days' => ['sometimes', 'integer', 'in:7,30,90,365'],
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d', 'before_or_equal:to'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $localTo = isset($validated['to']) ? HealthTime::localDate($validated['to']) : HealthTime::today();
        abort_if($localTo->isAfter(HealthTime::today()), 422, 'วันที่สิ้นสุดต้องไม่เกินวันนี้');
        $localFrom = isset($validated['from'])
            ? HealthTime::localDate($validated['from'])
            : $localTo->subDays(((int) ($validated['days'] ?? 30)) - 1);
        abort_if($localFrom->diffInDays($localTo) > 364, 422, 'ช่วงวันที่ต้องไม่เกิน 365 วัน');
        [$from, $to] = HealthTime::utcRange($localFrom->toDateString(), $localTo->toDateString());

        $followUps = FollowUpEntry::query()
            ->with([
                'episodeSymptom.symptom',
                'episodeSymptom.episode.sourceAssessment.results.diseases',
            ])
            ->whereHas('episodeSymptom.episode', fn ($query) => $query->where('user_id', $request->user()->user_id))
            ->whereBetween('recorded_at', [$from, $to])
            ->oldest('recorded_at')
            ->get();
        $series = $followUps->groupBy('episode_symptom_id')->map(function ($items, $episodeSymptomId) {
            return [
                'episode_symptom_id' => (int) $episodeSymptomId,
                'symptom_name' => $items->first()->episodeSymptom->symptom?->symptom_name
                    ?? $items->first()->episodeSymptom->custom_symptom_text,
                'record_count' => $items->count(),
                'first_severity' => $items->first()->severity,
                'latest_severity' => $items->last()->severity,
                'change' => $items->count() > 1 ? $items->last()->severity - $items->first()->severity : null,
                'records' => $items->map(fn ($item) => [
                    'severity' => $item->severity,
                    'temperature' => $item->temperature,
                    'recorded_at' => $item->recorded_at,
                ])->values()->all(),
            ];
        })->values()->all();

        $assessmentModels = Assessment::query()
            ->with(['symptom', 'results.diseases'])
            ->where('user_id', $request->user()->user_id)
            ->where('assessment_status', 'C')
            ->where('is_saved', true)
            ->whereBetween('completed_at', [$from, $to])
            ->latest('completed_at')
            ->get();
        $assessments = $assessmentModels->map(fn ($assessment) => [
                'symptom_name' => $assessment->symptom?->symptom_name,
                'completed_at' => $assessment->completed_at,
                'results' => $assessment->results->map(fn ($result) => [
                    'recommendation' => $result->recommendation,
                    'possible_conditions' => $result->diseases->map(fn ($disease) => [
                        'name' => $disease->disease_name,
                        'self_care' => $disease->self_care,
                        'when_to_see_doctor' => $disease->when_to_see_doctor,
                        'recommendations' => $disease->recommendations,
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all();

        $careContext = $followUps
            ->map(fn ($followUp) => $followUp->episodeSymptom->episode->sourceAssessment)
            ->filter()
            ->unique('id')
            ->flatMap(fn ($assessment) => $assessment->results->flatMap(
                fn ($result) => $result->diseases->map(fn ($disease) => [
                    'name' => $disease->disease_name,
                    'self_care' => $disease->self_care,
                    'when_to_see_doctor' => $disease->when_to_see_doctor,
                ])
            ))
            ->unique(fn (array $condition) => implode('|', $condition))
            ->values()
            ->all();

        $symptomStats = collect();
        $addSymptom = function ($id, $name, string $source) use (&$symptomStats): void {
            $name = trim((string) ($name ?: 'ไม่ระบุอาการ'));
            $key = $id ? 'symptom:'.$id : 'custom:'.mb_strtolower($name);
            $item = $symptomStats->get($key, [
                'symptom_name' => $name,
                'count' => 0,
                'assessment_count' => 0,
                'follow_up_count' => 0,
                'daily_record_count' => 0,
            ]);
            $item['count']++;
            $item[$source.'_count']++;
            $symptomStats->put($key, $item);
        };
        foreach ($assessmentModels as $assessment) {
            $addSymptom($assessment->symptom_id, $assessment->symptom?->symptom_name, 'assessment');
        }

        foreach ($followUps as $followUp) {
            $episodeSymptom = $followUp->episodeSymptom;
            $addSymptom(
                $episodeSymptom->symptom_id,
                $episodeSymptom->symptom?->symptom_name ?? $episodeSymptom->custom_symptom_text,
                'follow_up',
            );
        }

        $dailyRecordModels = DailyHealthRecord::query()
            ->with('symptoms')
            ->where('user_id', $request->user()->user_id)
            ->whereDate('recorded_on', '>=', $localFrom->toDateString())
            ->whereDate('recorded_on', '<=', $localTo->toDateString())
            ->oldest('recorded_on')
            ->get();
        foreach ($dailyRecordModels as $record) {
            foreach ($record->symptoms as $symptom) {
                $addSymptom($symptom->symptom_id, $symptom->symptom_name, 'daily_record');
            }
        }
        $dailyRecords = $dailyRecordModels->map(fn ($record) => [
            'recorded_on' => $record->recorded_on,
            'status' => $record->status,
            'note' => $record->note,
            'symptom_names' => $record->symptoms->pluck('symptom_name')->values()->all(),
        ])->values()->all();

        $healthData = [
            'follow_up_series' => $series,
            'assessments' => $assessments,
            'daily_records' => $dailyRecords,
            'care_context' => $careContext,
            'dashboard_context' => [
                'summary' => [
                    'assessment_count' => $assessmentModels->count(),
                    'follow_up_count' => $followUps->count(),
                ],
                'top_symptoms' => $symptomStats->sortByDesc('count')->values()->take(5)->all(),
                'statistical_analysis' => $statistics->analyze(
                    $followUps->filter(fn ($entry) => $entry->episodeSymptom->is_primary)->values(),
                    $dailyRecordModels,
                    $localFrom,
                    $localTo,
                ),
            ],
        ];
        $period = [
            'from' => $localFrom->toDateString(), 'to' => $localTo->toDateString(),
        ];
        $fingerprint = hash('sha256', json_encode([$period, $healthData], JSON_UNESCAPED_UNICODE));
        $cacheKey = 'ai:health-trend:v'.HealthTrendSummaryService::VERSION.":{$request->user()->user_id}:{$fingerprint}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return response()->json(['data' => [...$cached, 'cached' => true]]);
        }

        $summary = $service->generate($healthData, $period);
        if (($summary['source'] ?? null) === 'ai') {
            Cache::put($cacheKey, $summary, now()->addMinutes(config('ai.trend_cache_minutes')));
        }

        return response()->json(['data' => [...$summary, 'cached' => false]]);
    }

    private function authorizeAssessment(Request $request, Assessment $assessment): void
    {
        $user = $request->user('sanctum');
        if ($user && $assessment->user_id === $user->user_id) {
            return;
        }
        $token = (string) $request->header('X-Session-Token');
        abort_if($assessment->session_token === null || $token === '' || ! hash_equals($assessment->session_token, $token), 403);
    }

    private function ensureEnabled(): void
    {
        abort_unless(config('ai.enabled'), 503, 'AI assistance is not configured.');
    }
}
