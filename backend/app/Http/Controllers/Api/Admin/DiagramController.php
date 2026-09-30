<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiagramRequest;
use App\Http\Resources\Admin\DiagramResource;
use App\Models\Diagram;
use App\Support\AdminTableQuery;
use Illuminate\Http\Request;

/**
 * @tags Admin DiagramController
 */
class DiagramController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 200);

        $diagrams = Diagram::query()
            ->with(['symptoms', 'entryBox'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->input('symptom_ids'), fn ($q, $symptomIds) => $q->whereHas(
                'symptoms',
                fn ($symptoms) => $symptoms->whereIn('main_symptoms.symptom_id', (array) $symptomIds),
            ))
            ->when(
                ! $request->input('symptom_ids') && $request->symptom_id,
                fn ($q) => $q->whereHas('symptoms', fn ($symptoms) => $symptoms->where('main_symptoms.symptom_id', $request->symptom_id)),
            )
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch($q, $request->search, 'diagram_id', ['diagram_name', 'diagram_name_en']))
            ->when(
                in_array($request->sort_by, ['id', 'name', 'created_at', 'updated_at']),
                function ($q) use ($request) {
                    $direction = $request->sort_direction === 'asc' ? 'asc' : 'desc';

                    if ($request->sort_by === 'id') {
                        $q->orderBy('diagram_id', $direction);
                    } elseif ($request->sort_by === 'name') {
                        $q->orderBy('diagram_name', $direction);
                    } elseif ($request->sort_by === 'created_at') {
                        AdminTableQuery::orderByCreatedAt($q, $direction, 'diagram_id');
                    } elseif ($request->sort_by === 'updated_at') {
                        $q->orderBy('updated_at', $direction);
                    }
                },
                fn ($q) => $q->orderBy('diagram_id', 'desc')
            )
            ->orderBy('diagram_id', 'desc')
            ->paginate($perPage);

        return DiagramResource::collection($diagrams);
    }

    public function store(DiagramRequest $request)
    {
        $diagram = Diagram::create([
            'diagram_id' => $this->generateId(),
            'diagram_name' => $request->diagram_name,
            'diagram_name_en' => $request->diagram_name_en,
            'description' => $request->description,
            'status' => $request->status ?? '1',
            'created_by' => $request->user()->user_id,
            'updated_by' => $request->user()->user_id,
        ]);

        // ผูก symptoms (many-to-many)
        if ($request->filled('symptom_ids')) {
            $diagram->symptoms()->sync($request->symptom_ids);
        }

        return new DiagramResource($diagram->load(['symptoms', 'entryBox']));
    }

    public function show(Diagram $diagram)
    {
        return new DiagramResource($diagram->load([
            'symptoms',
            'entryBox',
            'questionBoxes.choices',
        ]));
    }

    public function update(DiagramRequest $request, Diagram $diagram)
    {
        $diagram->update([
            'diagram_name' => $request->has('diagram_name') ? $request->diagram_name : $diagram->diagram_name,
            'diagram_name_en' => $request->has('diagram_name_en') ? $request->diagram_name_en : $diagram->diagram_name_en,
            'description' => $request->has('description') ? $request->description : $diagram->description,
            'status' => $request->status ?? $diagram->status,
            'entry_box_id' => $request->has('entry_box_id') ? $request->entry_box_id : $diagram->entry_box_id,
            'updated_by' => $request->user()->user_id,
        ]);

        // sync symptoms ถ้าส่งมา
        if ($request->has('symptom_ids')) {
            $diagram->symptoms()->sync($request->symptom_ids ?? []);
        }

        return new DiagramResource($diagram->load(['symptoms', 'entryBox']));
    }

    public function destroy(Diagram $diagram)
    {
        if ($diagram->questionBoxes()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีคำถามใน diagram นี้อยู่',
            ], 422);
        }

        // ลบ pivot ก่อน แล้วค่อยลบ diagram
        $diagram->symptoms()->detach();
        $diagram->delete();

        return response()->json(['message' => 'ลบ diagram สำเร็จ']);
    }

    private function generateId(): string
    {
        $last = Diagram::max('diagram_id');
        $next = $last ? (int) $last + 1 : 1;

        return str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
