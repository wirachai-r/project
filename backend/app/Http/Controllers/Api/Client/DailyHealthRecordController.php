<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreDailyHealthRecordRequest;
use App\Http\Resources\DailyHealthRecordResource;
use App\Models\DailyHealthRecord;
use App\Models\HealthEpisode;
use App\Support\HealthTime;
use Illuminate\Http\Request;

class DailyHealthRecordController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $records = DailyHealthRecord::query()
            ->where('user_id', $request->user()->user_id)
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('recorded_on', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('recorded_on', '<=', $to))
            ->with(['symptoms', 'healthEpisodes.symptoms.symptom'])
            ->latest('recorded_on')
            ->latest('created_at')
            ->get();

        return DailyHealthRecordResource::collection($records);
    }

    public function store(StoreDailyHealthRecordRequest $request)
    {
        $data = $request->validated();
        $episodeIds = collect($data['health_episode_ids'] ?? [])->map(fn ($id) => (int) $id)->all();
        $ownedCount = HealthEpisode::query()
            ->where('user_id', $request->user()->user_id)->whereIn('id', $episodeIds)->count();
        abort_unless($ownedCount === count($episodeIds), 422, 'มีรายการติดตามที่ไม่สามารถเชื่อมกับบันทึกนี้ได้');
        $recordedAt = $data['recorded_at'] ?? HealthTime::localDate($data['recorded_on'])
            ->setTimeFrom(now(HealthTime::TIMEZONE))
            ->utc();
        $record = DailyHealthRecord::create([
            'user_id' => $request->user()->user_id,
            'recorded_on' => $data['recorded_on'],
            'recorded_at' => $recordedAt,
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
        ]);

        $symptomIds = $data['status'] === 'unwell' ? ($data['symptom_ids'] ?? []) : [];
        $record->symptoms()->sync(collect($symptomIds)->mapWithKeys(
            fn (string $id, int $index) => [$id => ['display_order' => $index]],
        ));
        $record->healthEpisodes()->sync($episodeIds);

        return (new DailyHealthRecordResource($record->load(['symptoms', 'healthEpisodes.symptoms.symptom'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreDailyHealthRecordRequest $request, DailyHealthRecord $dailyHealthRecord)
    {
        abort_if($dailyHealthRecord->user_id !== $request->user()->user_id, 403);
        $data = $request->validated();
        $episodeIds = collect($data['health_episode_ids'] ?? [])->map(fn ($id) => (int) $id)->all();
        $ownedCount = HealthEpisode::query()
            ->where('user_id', $request->user()->user_id)->whereIn('id', $episodeIds)->count();
        abort_unless($ownedCount === count($episodeIds), 422, 'มีรายการติดตามที่ไม่สามารถเชื่อมกับบันทึกนี้ได้');
        $dailyHealthRecord->update([
            'recorded_on' => $data['recorded_on'],
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
        ]);
        $symptomIds = $data['status'] === 'unwell' ? ($data['symptom_ids'] ?? []) : [];
        $dailyHealthRecord->symptoms()->sync(collect($symptomIds)->mapWithKeys(
            fn (string $id, int $index) => [$id => ['display_order' => $index]],
        ));
        $dailyHealthRecord->healthEpisodes()->sync($episodeIds);

        return new DailyHealthRecordResource(
            $dailyHealthRecord->load(['symptoms', 'healthEpisodes.symptoms.symptom'])
        );
    }
}
