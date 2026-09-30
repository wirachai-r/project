<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SymptomRequest;
use App\Http\Resources\Admin\SymptomResource;
use App\Models\MainSymptom;
use App\Support\AdminTableQuery;
use Illuminate\Http\Request;

/**
 * @tags Admin SymptomController
 */
class SymptomController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 200);

        $symptoms = MainSymptom::query()
            ->with('category')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->symptom_category_id, fn ($q) => $q->where('symptom_category_id', $request->symptom_category_id))
            ->when($request->filled('symptom_category_ids'), fn ($q) => $q->whereIn(
                'symptom_category_id',
                array_filter((array) $request->input('symptom_category_ids')),
            ))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch($q, $request->search, 'symptom_id', ['symptom_name', 'symptom_name_en']))
            ->when(
                in_array($request->sort_by, ['id', 'name', 'category', 'created_at', 'updated_at']),
                function ($q) use ($request) {
                    $direction = $request->sort_direction === 'asc' ? 'asc' : 'desc';

                    if ($request->sort_by === 'id') {
                        $q->orderBy('symptom_id', $direction);
                    } elseif ($request->sort_by === 'name') {
                        $q->orderBy('symptom_name', $direction);
                    } elseif ($request->sort_by === 'category') {
                        $q->orderBy('symptom_category_id', $direction);
                    } elseif ($request->sort_by === 'created_at') {
                        AdminTableQuery::orderByCreatedAt($q, $direction, 'symptom_id');
                    } elseif ($request->sort_by === 'updated_at') {
                        $q->orderBy('updated_at', $direction);
                    }
                },
                fn ($q) => $q->orderBy('symptom_id', 'desc')
            )
            ->orderBy('symptom_id', 'desc')
            ->paginate($perPage);

        return SymptomResource::collection($symptoms);
    }

    public function store(SymptomRequest $request)
    {
        $symptom = MainSymptom::create([
            'symptom_id' => $this->generateId(),
            'symptom_name' => $request->symptom_name,
            'symptom_name_en' => $request->symptom_name_en,
            'description' => $request->description,
            'symptom_image' => $request->symptom_image,
            'status' => $request->status ?? '1',
            'symptom_category_id' => $request->symptom_category_id,
            'created_by' => $request->user()->user_id,
            'updated_by' => $request->user()->user_id,
        ]);

        return new SymptomResource($symptom->load('category'));
    }

    public function show(MainSymptom $symptom)
    {
        return new SymptomResource($symptom->load(['category', 'diagrams']));
    }

    public function update(SymptomRequest $request, MainSymptom $symptom)
    {
        $symptom->update([
            'symptom_name' => $request->symptom_name ?? $symptom->symptom_name,
            'symptom_name_en' => $request->has('symptom_name_en') ? $request->symptom_name_en : $symptom->symptom_name_en,
            'description' => $request->has('description') ? $request->description : $symptom->description,
            'symptom_image' => $request->has('symptom_image') ? $request->symptom_image : $symptom->symptom_image,
            'status' => $request->status ?? $symptom->status,
            'symptom_category_id' => $request->symptom_category_id ?? $symptom->symptom_category_id,
            'updated_by' => $request->user()->user_id,
        ]);

        return new SymptomResource($symptom->load('category'));
    }

    public function destroy(MainSymptom $symptom)
    {
        // เช็คผ่าน many-to-many (symptom_diagrams)
        if ($symptom->diagrams()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมี diagram ที่ใช้อาการนี้อยู่',
            ], 422);
        }

        $symptom->delete();

        return response()->json(['message' => 'ลบอาการสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = MainSymptom::max('symptom_id');
        $next = $last ? (int) $last + 1 : 1;

        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
