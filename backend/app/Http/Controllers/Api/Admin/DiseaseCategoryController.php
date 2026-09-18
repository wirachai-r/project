<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiseaseCategoryRequest;
use App\Http\Resources\Admin\DiseaseCategoryResource;
use App\Models\DiseaseCategory;
use App\Support\AdminTableQuery;
use Illuminate\Http\Request;

/**
 * @tags Admin DiseaseCategoryController
 */
class DiseaseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $categories = DiseaseCategory::query()
            ->withCount('diseases')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch($q, $request->search, 'disease_category_id', ['category_name', 'category_name_en']))
            ->when(
                in_array($request->sort_by, ['id', 'name', 'created_at', 'updated_at']),
                function ($q) use ($request) {
                    $direction = $request->sort_direction === 'asc' ? 'asc' : 'desc';

                    if ($request->sort_by === 'id') {
                        $q->orderBy('disease_category_id', $direction);
                    } elseif ($request->sort_by === 'name') {
                        $q->orderBy('category_name', $direction);
                    } elseif ($request->sort_by === 'created_at') {
                        $q->orderBy('created_at', $direction);
                    } elseif ($request->sort_by === 'updated_at') {
                        $q->orderBy('updated_at', $direction);
                    }
                },
                fn ($q) => $q->orderBy('disease_category_id', 'desc')
            )
            ->orderBy('disease_category_id', 'desc')
            ->paginate($perPage);

        return DiseaseCategoryResource::collection($categories);
    }

    public function store(DiseaseCategoryRequest $request)
    {
        $category = DiseaseCategory::create([
            'disease_category_id' => $this->generateId(),
            'category_name' => $request->category_name,
            'category_name_en' => $request->category_name_en,
            'description' => $request->description,
            'icon' => $request->icon,
            'status' => $request->status ?? '1',
            'created_by' => $request->user()->user_id,
            'updated_by' => $request->user()->user_id,
        ]);

        return new DiseaseCategoryResource($category);
    }

    public function show(DiseaseCategory $diseaseCategory)
    {
        return new DiseaseCategoryResource($diseaseCategory->loadCount('diseases'));
    }

    public function update(DiseaseCategoryRequest $request, DiseaseCategory $diseaseCategory)
    {
        $diseaseCategory->update([
            'category_name' => $request->category_name ?? $diseaseCategory->category_name,
            'category_name_en' => $request->has('category_name_en') ? $request->category_name_en : $diseaseCategory->category_name_en,
            'description' => $request->has('description') ? $request->description : $diseaseCategory->description,
            'icon' => $request->has('icon') ? $request->icon : $diseaseCategory->icon,
            'status' => $request->status ?? $diseaseCategory->status,
            'updated_by' => $request->user()->user_id,
        ]);

        return new DiseaseCategoryResource($diseaseCategory->loadCount('diseases'));
    }

    public function destroy(DiseaseCategory $diseaseCategory)
    {
        if ($diseaseCategory->diseases()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีโรคในหมวดหมู่นี้อยู่',
            ], 422);
        }

        $diseaseCategory->delete();

        return response()->json(['message' => 'ลบหมวดหมู่โรคสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = DiseaseCategory::max('disease_category_id');
        $next = $last ? (int) $last + 1 : 1;

        return str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
