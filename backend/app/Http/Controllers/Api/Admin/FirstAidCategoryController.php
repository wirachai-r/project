<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FirstAidCategoryRequest;
use App\Http\Resources\Admin\FirstAidCategoryResource;
use App\Models\FirstAidCategory;
use App\Support\AdminTableQuery;
use Illuminate\Http\Request;

/**
 * @tags Admin FirstAidCategoryController
 */
class FirstAidCategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 200);

        $categories = FirstAidCategory::query()
            ->withCount('firstAids')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch($q, $request->search, 'first_aid_category_id', ['category_name', 'category_name_en']))
            ->when(
                in_array($request->sort_by, ['id', 'name', 'created_at', 'updated_at']),
                function ($q) use ($request) {
                    $direction = $request->sort_direction === 'asc' ? 'asc' : 'desc';

                    if ($request->sort_by === 'id') {
                        $q->orderBy('first_aid_category_id', $direction);
                    } elseif ($request->sort_by === 'name') {
                        $q->orderBy('category_name', $direction);
                    } elseif ($request->sort_by === 'created_at') {
                        AdminTableQuery::orderByCreatedAt($q, $direction, 'first_aid_category_id');
                    } elseif ($request->sort_by === 'updated_at') {
                        $q->orderBy('updated_at', $direction);
                    }
                },
                fn ($q) => $q->orderBy('first_aid_category_id', 'desc')
            )
            ->orderBy('first_aid_category_id', 'desc')
            ->paginate($perPage);

        return FirstAidCategoryResource::collection($categories);
    }

    public function store(FirstAidCategoryRequest $request)
    {
        $category = FirstAidCategory::create([
            'first_aid_category_id' => $this->generateId(),
            'category_name' => $request->category_name,
            'category_name_en' => $request->category_name_en,
            'description' => $request->description,
            'icon' => $request->icon,
            'status' => $request->status ?? '1',
            'created_by' => $request->user()->user_id,
            'updated_by' => $request->user()->user_id,
        ]);

        return new FirstAidCategoryResource($category);
    }

    public function show(FirstAidCategory $firstAidCategory)
    {
        return new FirstAidCategoryResource($firstAidCategory->loadCount('firstAids'));
    }

    public function update(FirstAidCategoryRequest $request, FirstAidCategory $firstAidCategory)
    {
        $firstAidCategory->update([
            'category_name' => $request->category_name ?? $firstAidCategory->category_name,
            'category_name_en' => $request->has('category_name_en') ? $request->category_name_en : $firstAidCategory->category_name_en,
            'description' => $request->has('description') ? $request->description : $firstAidCategory->description,
            'icon' => $request->has('icon') ? $request->icon : $firstAidCategory->icon,
            'status' => $request->status ?? $firstAidCategory->status,
            'updated_by' => $request->user()->user_id,
        ]);

        return new FirstAidCategoryResource($firstAidCategory->loadCount('firstAids'));
    }

    public function destroy(FirstAidCategory $firstAidCategory)
    {
        if ($firstAidCategory->firstAids()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีข้อมูลปฐมพยาบาลในหมวดหมู่นี้อยู่',
            ], 422);
        }

        $firstAidCategory->delete();

        return response()->json(['message' => 'ลบหมวดหมู่ปฐมพยาบาลสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = FirstAidCategory::max('first_aid_category_id');
        $next = $last ? (int) $last + 1 : 1;

        return str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
