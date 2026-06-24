<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SymptomCategoryRequest;
use App\Http\Resources\Admin\SymptomCategoryResource;
use App\Models\SymptomCategory;
use Illuminate\Http\Request;

/**
 * @tags Admin SymptomCategoryController
 */

class SymptomCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = SymptomCategory::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('category_name', 'like', '%' . $request->search . '%'))
            ->orderBy('category_name')
            ->paginate(20);

        return SymptomCategoryResource::collection($categories);
    }

    public function store(SymptomCategoryRequest $request)
    {
        $category = SymptomCategory::create([
            'symptom_category_id' => $this->generateId(),
            'category_name'       => $request->category_name,
            'category_name_en'    => $request->category_name_en,
            'description'         => $request->description,
            'icon'                => $request->icon,
            'status'              => $request->status ?? '1',
            'created_by'          => $request->user()->user_id,
            'updated_by'          => $request->user()->user_id,
        ]);

        return new SymptomCategoryResource($category);
    }

    public function show(SymptomCategory $symptomCategory)
    {
        return new SymptomCategoryResource($symptomCategory);
    }

    public function update(SymptomCategoryRequest $request, SymptomCategory $symptomCategory)
    {
        $symptomCategory->update([
            'category_name'    => $request->category_name,
            'category_name_en' => $request->category_name_en,
            'description'      => $request->description,
            'icon'             => $request->icon,
            'status'           => $request->status ?? $symptomCategory->status,
            'updated_by'       => $request->user()->user_id,
        ]);

        return new SymptomCategoryResource($symptomCategory);
    }

    public function destroy(SymptomCategory $symptomCategory)
    {
        if ($symptomCategory->symptoms()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีอาการในหมวดหมู่นี้อยู่'
            ], 422);
        }

        $symptomCategory->delete();

        return response()->json(['message' => 'ลบหมวดหมู่อาการสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = SymptomCategory::max('symptom_category_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
