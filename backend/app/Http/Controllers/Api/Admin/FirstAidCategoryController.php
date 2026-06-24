<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FirstAidCategoryRequest;
use App\Http\Resources\Admin\FirstAidCategoryResource;
use App\Models\FirstAidCategory;
use Illuminate\Http\Request;

/**
 * @tags Admin FirstAidCategoryController
 */

class FirstAidCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = FirstAidCategory::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('category_name', 'like', '%' . $request->search . '%'))
            ->orderBy('category_name')
            ->paginate(20);

        return FirstAidCategoryResource::collection($categories);
    }

    public function store(FirstAidCategoryRequest $request)
    {
        $category = FirstAidCategory::create([
            'first_aid_category_id' => $this->generateId(),
            'category_name'         => $request->category_name,
            'category_name_en'      => $request->category_name_en,
            'description'           => $request->description,
            'status'                => $request->status ?? '1',
            'created_by'            => $request->user()->user_id,
            'updated_by'            => $request->user()->user_id,
        ]);

        return new FirstAidCategoryResource($category);
    }

    public function show(FirstAidCategory $firstAidCategory)
    {
        return new FirstAidCategoryResource($firstAidCategory->load('firstAids'));
    }

    public function update(FirstAidCategoryRequest $request, FirstAidCategory $firstAidCategory)
    {
        $firstAidCategory->update([
            'category_name'    => $request->category_name,
            'category_name_en' => $request->category_name_en,
            'description'      => $request->description,
            'status'           => $request->status ?? $firstAidCategory->status,
            'updated_by'       => $request->user()->user_id,
        ]);

        return new FirstAidCategoryResource($firstAidCategory);
    }

    public function destroy(FirstAidCategory $firstAidCategory)
    {
        if ($firstAidCategory->firstAids()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีข้อมูลปฐมพยาบาลในหมวดหมู่นี้อยู่'
            ], 422);
        }

        $firstAidCategory->delete();

        return response()->json(['message' => 'ลบหมวดหมู่ปฐมพยาบาลสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = FirstAidCategory::max('first_aid_category_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
