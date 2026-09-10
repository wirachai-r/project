<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreEpisodeSymptomRequest;
use App\Http\Requests\Client\StoreFollowUpEntryRequest;
use App\Models\Assessment;
use App\Models\DailyHealthRecord;
use App\Models\EpisodeSymptom;
use App\Models\FollowUpEntry;
use App\Models\FollowUpQuestionTemplate;
use App\Models\HealthEpisode;
use App\Support\HealthTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthEpisodeController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d', 'before_or_equal:to'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        [$fromUtc, $toUtc] = isset($validated['from'], $validated['to'])
            ? HealthTime::utcRange($validated['from'], $validated['to'])
            : [null, null];
        $episodes = HealthEpisode::query()
            ->with([
                'assessments.symptom',
                'symptoms.symptom.category',
                'symptoms.entries' => function ($query) use ($fromUtc, $toUtc) {
                    $query->latest('recorded_at');
                    if ($fromUtc && $toUtc) {
                        $query->whereBetween('recorded_at', [$fromUtc, $toUtc]);
                    } else {
                        $query->limit(1);
                    }
                },
            ])
            ->where('user_id', $request->user()->user_id)
            ->latest('started_at')->get();

        return response()->json(['data' => $episodes->map(fn ($episode) => $this->serialize($episode))]);
    }

    public function startFromAssessment(Request $request, Assessment $assessment)
    {
        abort_if($assessment->user_id !== $request->user()->user_id, 403);
        abort_if($assessment->assessment_status !== 'C', 422, 'การประเมินยังไม่เสร็จสิ้น');
        abort_unless($assessment->is_saved, 422, 'กรุณาบันทึกผลประเมินลงประวัติก่อนเริ่มติดตามอาการ');

        $data = $request->validate([
            'health_episode_id' => ['nullable', 'integer', 'exists:health_episodes,id'],
        ]);

        $episode = DB::transaction(function () use ($request, $assessment, $data) {
            $existing = HealthEpisode::query()->whereHas('assessments', fn ($query) => $query->whereKey($assessment->id))->first();
            if ($existing) {
                abort_if($existing->user_id !== $request->user()->user_id, 403);

                return $existing;
            }

            if (isset($data['health_episode_id'])) {
                $episode = HealthEpisode::query()->lockForUpdate()->findOrFail($data['health_episode_id']);
                abort_if($episode->user_id !== $request->user()->user_id, 403);
                abort_if($episode->status !== 'A', 422, 'เพิ่มผลประเมินได้เฉพาะรายการที่กำลังติดตาม');
                $relationshipType = 'related';
            } else {
                $episode = HealthEpisode::create([
                    'source_assessment_id' => $assessment->id,
                    'user_id' => $request->user()->user_id,
                    'status' => 'A',
                    'started_at' => now(),
                ]);
                $relationshipType = 'initial';
            }

            $episode->assessments()->attach($assessment->id, [
                'relationship_type' => $relationshipType,
                'attached_at' => now(),
            ]);
            $episode->symptoms()->firstOrCreate(
                ['symptom_id' => $assessment->symptom_id],
                ['is_primary' => true, 'status' => 'A', 'first_observed_at' => now()],
            );

            return $episode;
        });

        return response()->json(['data' => $this->serialize($episode->load('assessments.symptom', 'symptoms.symptom.category', 'symptoms.entries'))], 201);
    }

    public function startFromDailyRecord(Request $request, DailyHealthRecord $dailyHealthRecord)
    {
        abort_if($dailyHealthRecord->user_id !== $request->user()->user_id, 403);
        $data = $request->validate([
            'symptom_ids' => ['required', 'array', 'min:1'],
            'symptom_ids.*' => ['required', 'string', 'distinct', 'exists:main_symptoms,symptom_id'],
            'health_episode_id' => ['nullable', 'integer', 'exists:health_episodes,id'],
        ]);

        $recordedSymptomIds = $dailyHealthRecord->symptoms()->pluck('main_symptoms.symptom_id');
        abort_unless(
            collect($data['symptom_ids'])->every(fn ($id) => $recordedSymptomIds->contains($id)),
            422,
            'เริ่มติดตามได้เฉพาะอาการที่อยู่ในบันทึกสุขภาพนี้'
        );

        $episode = DB::transaction(function () use ($request, $dailyHealthRecord, $data) {
            if (isset($data['health_episode_id'])) {
                $episode = HealthEpisode::query()->lockForUpdate()->findOrFail($data['health_episode_id']);
                abort_if($episode->user_id !== $request->user()->user_id, 403);
                abort_if($episode->status !== 'A', 422, 'เพิ่มอาการได้เฉพาะรายการที่กำลังติดตาม');
            } else {
                $episode = HealthEpisode::create([
                    'user_id' => $request->user()->user_id,
                    'source_assessment_id' => null,
                    'status' => 'A',
                    'started_at' => $dailyHealthRecord->recorded_at ?? now(),
                ]);
            }
            $hasPrimary = $episode->symptoms()->where('is_primary', true)->exists();
            foreach ($data['symptom_ids'] as $index => $symptomId) {
                $episode->symptoms()->firstOrCreate(
                    ['symptom_id' => $symptomId],
                    [
                        'is_primary' => ! $hasPrimary && $index === 0,
                        'status' => 'A',
                        'first_observed_at' => $dailyHealthRecord->recorded_at ?? now(),
                    ]
                );
            }
            $dailyHealthRecord->healthEpisodes()->syncWithoutDetaching([$episode->id]);

            return $episode;
        });

        return response()->json([
            'data' => $this->serialize($episode->load('assessments.symptom', 'symptoms.symptom.category', 'symptoms.entries')),
        ], 201);
    }

    public function show(Request $request, HealthEpisode $healthEpisode)
    {
        $this->authorizeOwner($request, $healthEpisode);

        return response()->json(['data' => $this->serialize($healthEpisode->load([
            'assessments.symptom', 'dailyHealthRecords.symptoms',
            'symptoms.symptom.category', 'symptoms.entries' => fn ($query) => $query->with('answers')->latest('recorded_at'),
        ]))]);
    }

    public function updateStatus(Request $request, HealthEpisode $healthEpisode)
    {
        $this->authorizeOwner($request, $healthEpisode);
        $data = $request->validate([
            'status' => ['required', 'in:A,P,E'],
            'end_reason' => ['nullable', 'required_if:status,E', 'in:recovered,improved,consulted_provider,stopped_by_user,other'],
            'end_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $healthEpisode->update([
            'status' => $data['status'],
            'paused_at' => $data['status'] === 'P' ? now() : null,
            'ended_at' => $data['status'] === 'E' ? now() : null,
            'end_reason' => $data['status'] === 'E' ? $data['end_reason'] : null,
            'end_note' => $data['status'] === 'E' ? ($data['end_note'] ?? null) : null,
        ]);

        if ($data['status'] === 'E') {
            $healthEpisode->symptoms()->where('status', 'A')->update(['status' => 'E', 'ended_at' => now()]);
            $healthEpisode->reminders()->update(['is_enabled' => false, 'next_run_at' => null]);
        }

        return response()->json(['data' => $this->serialize($healthEpisode->load('assessments.symptom', 'symptoms.symptom.category', 'symptoms.entries'))]);
    }

    public function addSymptom(StoreEpisodeSymptomRequest $request, HealthEpisode $healthEpisode)
    {
        $this->authorizeOwner($request, $healthEpisode);
        abort_if($healthEpisode->status !== 'A', 422, 'การติดตามนี้สิ้นสุดแล้ว');
        $data = $request->validated();

        $duplicate = $healthEpisode->symptoms()
            ->when($data['symptom_id'] ?? null, fn ($query, $id) => $query->where('symptom_id', $id))
            ->when(! ($data['symptom_id'] ?? null), fn ($query) => $query->whereRaw('LOWER(custom_symptom_text) = ?', [mb_strtolower(trim($data['custom_symptom_text']))]))
            ->exists();
        abort_if($duplicate, 422, 'อาการนี้อยู่ในการติดตามแล้ว');

        $episodeSymptom = $healthEpisode->symptoms()->create([
            'symptom_id' => $data['symptom_id'] ?? null,
            'custom_symptom_text' => isset($data['custom_symptom_text']) ? trim($data['custom_symptom_text']) : null,
            'is_primary' => false,
            'status' => 'A',
            'first_observed_at' => $data['first_observed_at'] ?? now(),
        ]);

        return response()->json(['data' => $this->serializeSymptom($episodeSymptom->load('symptom.category', 'entries'))], 201);
    }

    public function updateSymptomStatus(Request $request, EpisodeSymptom $episodeSymptom)
    {
        $this->authorizeOwner($request, $episodeSymptom->episode);
        $data = $request->validate(['status' => ['required', 'in:A,E']]);
        $episodeSymptom->update([
            'status' => $data['status'],
            'ended_at' => $data['status'] === 'E' ? now() : null,
        ]);

        return response()->json(['data' => $this->serializeSymptom($episodeSymptom->load('symptom.category', 'entries'))]);
    }

    public function storeEntry(StoreFollowUpEntryRequest $request, EpisodeSymptom $episodeSymptom)
    {
        $this->authorizeOwner($request, $episodeSymptom->episode);
        abort_if($episodeSymptom->episode->status !== 'A', 422, 'รายการติดตามนี้ไม่ได้อยู่ในสถานะกำลังติดตาม');
        abort_if($episodeSymptom->status !== 'A', 422, 'อาการนี้หยุดติดตามแล้ว');
        $data = $request->validated();
        $templates = $this->templatesFor($episodeSymptom, $episodeSymptom->is_primary);
        $submitted = collect($data['answers'] ?? [])->keyBy('question_template_id');
        foreach ($templates->where('is_required_effective', true) as $template) {
            abort_unless($submitted->has($template->id), 422, "กรุณาตอบคำถาม: {$template->question_text}");
        }

        $entry = DB::transaction(function () use ($episodeSymptom, $data, $templates, $submitted) {
            $entry = $episodeSymptom->entries()->create([
                'severity' => $data['severity'] ?? null,
                'temperature' => $data['temperature'] ?? null,
                'note' => $data['note'] ?? null,
                'recorded_at' => $data['recorded_at'] ?? now(),
            ]);
            foreach ($templates as $template) {
                if (! $submitted->has($template->id)) {
                    continue;
                }
                $value = $submitted->get($template->id)['value'];
                $this->validateTemplateAnswer($template, $value);
                $entry->answers()->create([
                    'question_template_id' => $template->id,
                    'question_text_snapshot' => $template->question_text,
                    'answer_type_snapshot' => $template->answer_type,
                    'answer_value' => ['value' => $value],
                    'answered_at' => $entry->recorded_at,
                ]);
            }

            return $entry->load('answers');
        });

        return response()->json(['data' => $entry], 201);
    }

    public function destroyEntry(Request $request, FollowUpEntry $followUpEntry)
    {
        $this->authorizeOwner($request, $followUpEntry->episodeSymptom->episode);
        $followUpEntry->delete();

        return response()->json(['message' => 'ลบบันทึกเรียบร้อยแล้ว']);
    }

    public function updateEntry(StoreFollowUpEntryRequest $request, FollowUpEntry $followUpEntry)
    {
        $episodeSymptom = $followUpEntry->episodeSymptom;
        $this->authorizeOwner($request, $episodeSymptom->episode);
        abort_if($episodeSymptom->episode->status !== 'A', 422, 'รายการติดตามนี้ไม่ได้อยู่ในสถานะกำลังติดตาม');
        abort_if($episodeSymptom->status !== 'A', 422, 'อาการนี้หยุดติดตามแล้ว');
        $data = $request->validated();
        $templates = $this->templatesFor($episodeSymptom, $episodeSymptom->is_primary);
        $submitted = collect($data['answers'] ?? [])->keyBy('question_template_id');
        foreach ($templates->where('is_required_effective', true) as $template) {
            abort_unless($submitted->has($template->id), 422, "กรุณาตอบคำถาม: {$template->question_text}");
        }

        DB::transaction(function () use ($followUpEntry, $data, $templates, $submitted) {
            $followUpEntry->update([
                'severity' => $data['severity'] ?? null,
                'temperature' => $data['temperature'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
            $followUpEntry->answers()->delete();
            foreach ($templates as $template) {
                if (! $submitted->has($template->id)) {
                    continue;
                }
                $value = $submitted->get($template->id)['value'];
                $this->validateTemplateAnswer($template, $value);
                $followUpEntry->answers()->create([
                    'question_template_id' => $template->id,
                    'question_text_snapshot' => $template->question_text,
                    'answer_type_snapshot' => $template->answer_type,
                    'answer_value' => ['value' => $value],
                    'answered_at' => now(),
                ]);
            }
        });

        return response()->json(['data' => $followUpEntry->fresh()->load('answers')]);
    }

    private function authorizeOwner(Request $request, HealthEpisode $episode): void
    {
        abort_if($episode->user_id !== $request->user()->user_id, 403);
    }

    private function serialize(HealthEpisode $episode): array
    {
        return [
            'id' => $episode->id,
            'source_assessment_id' => $episode->source_assessment_id,
            'status' => $episode->status,
            'started_at' => $episode->started_at,
            'ended_at' => $episode->ended_at,
            'paused_at' => $episode->paused_at,
            'end_reason' => $episode->end_reason,
            'end_note' => $episode->end_note,
            'assessments' => $episode->relationLoaded('assessments') ? $episode->assessments->map(fn ($assessment) => [
                'id' => $assessment->id,
                'symptom_id' => $assessment->symptom_id,
                'symptom_name' => $assessment->symptom?->symptom_name,
                'completed_at' => $assessment->completed_at,
                'relationship_type' => $assessment->pivot->relationship_type,
                'attached_at' => $assessment->pivot->attached_at,
            ])->values() : [],
            'daily_health_records' => $episode->relationLoaded('dailyHealthRecords') ? $episode->dailyHealthRecords->map(fn ($record) => [
                'id' => $record->id,
                'recorded_at' => $record->recorded_at ?? $record->created_at,
                'status' => $record->status,
                'note' => $record->note,
            ])->values() : [],
            'symptoms' => $episode->symptoms->map(fn ($item) => $this->serializeSymptom($item))->values(),
        ];
    }

    private function serializeSymptom(EpisodeSymptom $item): array
    {
        return [
            'id' => $item->id,
            'symptom_id' => $item->symptom_id,
            'symptom_name' => $item->symptom?->symptom_name ?? $item->custom_symptom_text,
            'symptom_icon' => $item->symptom?->symptom_image ?? $item->symptom?->category?->icon,
            'is_primary' => $item->is_primary,
            'status' => $item->status,
            'first_observed_at' => $item->first_observed_at,
            'entries' => $item->relationLoaded('entries') ? $item->entries : [],
            'questions' => $this->templatesFor($item, $item->is_primary)->map(fn ($template) => [
                'id' => $template->id,
                'question_text' => $template->question_text,
                'description' => $template->description,
                'answer_type' => $template->answer_type,
                'options' => $template->options ?? [],
                'unit' => $template->unit,
                'is_required' => $template->is_required_effective,
                'is_global' => (bool) $template->applies_to_all_symptoms,
            ])->values(),
        ];
    }

    private function templatesFor(EpisodeSymptom $item, bool $includeGlobal = true)
    {
        return FollowUpQuestionTemplate::query()
            ->leftJoin('symptom_follow_up_questions as link', function ($join) use ($item) {
                $join->on('link.question_template_id', '=', 'follow_up_question_templates.id')
                    ->where('link.symptom_id', '=', $item->symptom_id)
                    ->where('link.status', '=', '1');
            })
            ->where('follow_up_question_templates.status', '1')
            ->where(function ($query) use ($includeGlobal) {
                if ($includeGlobal) {
                    $query->where('follow_up_question_templates.applies_to_all_symptoms', true)
                        ->orWhereNotNull('link.id');
                } else {
                    $query->whereNotNull('link.id');
                }
            })
            ->select('follow_up_question_templates.*', 'link.sequence', 'link.is_required_override')
            ->orderByRaw('CASE WHEN link.id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('link.sequence')
            ->orderBy('follow_up_question_templates.id')
            ->get()
            ->each(function ($template) {
                $template->is_required_effective = $template->is_required_override ?? $template->is_required;
            });
    }

    private function validateTemplateAnswer(FollowUpQuestionTemplate $template, mixed $value): void
    {
        if ($template->answer_type === 'boolean') {
            abort_unless(is_bool($value), 422, "คำตอบของ {$template->question_text} ไม่ถูกต้อง");
        }
        if (in_array($template->answer_type, ['single_choice', 'scale'], true)) {
            abort_unless(is_string($value) && in_array($value, $template->options ?? [], true), 422, "คำตอบของ {$template->question_text} ไม่ถูกต้อง");
        }
        if ($template->answer_type === 'multiple_choice') {
            abort_unless(is_array($value) && count($value) > 0 && count($value) === count(array_unique($value)), 422, "คำตอบของ {$template->question_text} ไม่ถูกต้อง");
            abort_unless(collect($value)->every(fn ($item) => is_string($item) && in_array($item, $template->options ?? [], true)), 422, "คำตอบของ {$template->question_text} ไม่ถูกต้อง");
        }
        if ($template->answer_type === 'number') {
            abort_unless(is_numeric($value), 422, "คำตอบของ {$template->question_text} ต้องเป็นตัวเลข");
        }
        if ($template->answer_type === 'date') {
            abort_unless(is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1, 422, "คำตอบของ {$template->question_text} ต้องเป็นวันที่");
        }
        if ($template->answer_type === 'time') {
            abort_unless(is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1, 422, "คำตอบของ {$template->question_text} ต้องเป็นเวลา");
        }
        if ($template->answer_type === 'text') {
            abort_unless(is_string($value) && mb_strlen($value) <= 2000, 422, "คำตอบของ {$template->question_text} ไม่ถูกต้อง");
        }
    }
}
