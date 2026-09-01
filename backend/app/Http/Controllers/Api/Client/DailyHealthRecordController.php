<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreDailyHealthRecordRequest;
use App\Http\Resources\DailyHealthRecordResource;
use App\Models\DailyHealthRecord;
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
            ->with('symptoms')
            ->latest('recorded_on')
            ->latest('created_at')
            ->get();

        return DailyHealthRecordResource::collection($records);
    }

    public function store(StoreDailyHealthRecordRequest $request)
    {
        $data = $request->validated();
        $record = DailyHealthRecord::create([
            'user_id' => $request->user()->user_id,
            'recorded_on' => $data['recorded_on'],
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
        ]);

        $symptomIds = $data['status'] === 'unwell' ? ($data['symptom_ids'] ?? []) : [];
        $record->symptoms()->sync(collect($symptomIds)->mapWithKeys(
            fn (string $id, int $index) => [$id => ['display_order' => $index]],
        ));

        return (new DailyHealthRecordResource($record->load('symptoms')))
            ->response()
            ->setStatusCode(201);
    }
}
