<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreEpisodeSymptomRequest;
use App\Http\Requests\Client\StoreFollowUpEntryRequest;
use App\Models\Assessment;
use App\Models\EpisodeSymptom;
use App\Models\FollowUpEntry;
use App\Models\FollowUpQuestionTemplate;
use App\Models\HealthEpisode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthEpisodeController extends Controller
{
    public function index(Request $request)
    {
        $episodes = HealthEpisode::query()
            ->with(['symptoms.symptom', 'symptoms.entries' => fn ($query) => $query->latest('recorded_at')->limit(1)])
            ->where('user_id', $request->user()->user_id)
            ->latest('started_at')->get();

        return response()->json(['data' => $episodes->map(fn ($episode) => $this->serialize($episode))]);
    }

    public function startFromAssessment(Request $request, Assessment $assessment)
    {
        abort_if($assessment->user_id !== $request->user()->user_id, 403);
        abort_if($assessment->assessment_status !== 'C', 422, 'การประเมินยังไม่เสร็จสิ้น');

        $episode = DB::transaction(function () use ($request, $assessment) {
            $episode = HealthEpisode::firstOrCreate(
                ['source_assessment_id' => $assessment->id],
                ['user_id' => $request->user()->user_id, 'status' => 'A', 'started_at' => now()],
            );
            $episode->symptoms()->firstOrCreate(
                ['symptom_id' => $assessment->symptom_id],
                ['is_primary' => true, 'status' => 'A', 'first_observed_at' => now()],
            );

            return $episode;
        });

        return response()->json(['data' => $this->serialize($episode->load('symptoms.symptom', 'symptoms.entries'))], 201);
    }

    public function show(Request $request, HealthEpisode $healthEpisode)
    {
        $this->authorizeOwner($request, $healthEpisode);

        return response()->json(['data' => $this->serialize($healthEpisode->load([
            'symptoms.symptom', 'symptoms.entries' => fn ($query) => $query->with('answers')->latest('recorded_at'),
        ]))]);
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

        return response()->json(['data' => $this->serializeSymptom($episodeSymptom->load('symptom', 'entries'))], 201);
    }

    public function updateSymptomStatus(Request $request, EpisodeSymptom $episodeSymptom)
    {
        $this->authorizeOwner($request, $episodeSymptom->episode);
        $data = $request->validate(['status' => ['required', 'in:A,E']]);
        $episodeSymptom->update([
            'status' => $data['status'],
            'ended_at' => $data['status'] === 'E' ? now() : null,
        ]);

        return response()->json(['data' => $this->serializeSymptom($episodeSymptom->load('symptom', 'entries'))]);
    }

    public function storeEntry(StoreFollowUpEntryRequest $request, EpisodeSymptom $episodeSymptom)
    {
        $this->authorizeOwner($request, $episodeSymptom->episode);
        abort_if($episodeSymptom->status !== 'A', 422, 'อาการนี้หยุดติดตามแล้ว');
        $data = $request->validated();
        $templates = $this->templatesFor($episodeSymptom);
        $submitted = collect($data['answers'] ?? [])->keyBy('question_template_id');
        foreach ($templates->where('is_required_effective', true) as $template) {
            abort_unless($submitted->has($template->id), 422, "กรุณาตอบคำถาม: {$template->question_text}");
        }

        $entry = DB::transaction(function () use ($episodeSymptom, $data, $templates, $submitted) {
            $entry = $episodeSymptom->entries()->create([
                'severity' => $data['severity'],
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

    private function authorizeOwner(Request $request, HealthEpisode $episode): void
    {
        abort_if($episode->user_id !== $request->user()->user_id, 403);
    }

    private function serialize(HealthEpisode $episode): array
    {
        return [
            'id' => $episode->id,
            'status' => $episode->status,
            'started_at' => $episode->started_at,
            'ended_at' => $episode->ended_at,
            'symptoms' => $episode->symptoms->map(fn ($item) => $this->serializeSymptom($item))->values(),
        ];
    }

    private function serializeSymptom(EpisodeSymptom $item): array
    {
        return [
            'id' => $item->id,
            'symptom_id' => $item->symptom_id,
            'symptom_name' => $item->symptom?->symptom_name ?? $item->custom_symptom_text,
            'is_primary' => $item->is_primary,
            'status' => $item->status,
            'first_observed_at' => $item->first_observed_at,
            'entries' => $item->relationLoaded('entries') ? $item->entries : [],
            'questions' => $this->templatesFor($item)->map(fn ($template) => [
                'id' => $template->id,
                'question_text' => $template->question_text,
                'description' => $template->description,
                'answer_type' => $template->answer_type,
                'options' => $template->options ?? [],
                'unit' => $template->unit,
                'is_required' => $template->is_required_effective,
            ])->values(),
        ];
    }

    private function templatesFor(EpisodeSymptom $item)
    {
        return FollowUpQuestionTemplate::query()
            ->leftJoin('symptom_follow_up_questions as link', function ($join) use ($item) {
                $join->on('link.question_template_id', '=', 'follow_up_question_templates.id')
                    ->where('link.symptom_id', '=', $item->symptom_id)
                    ->where('link.status', '=', '1');
            })
            ->where('follow_up_question_templates.status', '1')
            ->where(function ($query) {
                $query->where('follow_up_question_templates.applies_to_all_symptoms', true)
                    ->orWhereNotNull('link.id');
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
