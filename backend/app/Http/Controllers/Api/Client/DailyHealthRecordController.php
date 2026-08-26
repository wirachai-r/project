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
            ->latest('recorded_on')
            ->get();

        return DailyHealthRecordResource::collection($records);
    }

    public function store(StoreDailyHealthRecordRequest $request)
    {
        $data = $request->validated();
        $record = DailyHealthRecord::updateOrCreate(
            ['user_id' => $request->user()->user_id, 'recorded_on' => $data['recorded_on']],
            ['status' => $data['status'], 'note' => $data['note'] ?? null],
        );

        return (new DailyHealthRecordResource($record))
            ->response()
            ->setStatusCode($record->wasRecentlyCreated ? 201 : 200);
    }
}
