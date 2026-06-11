<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiseaseCategoryRequest;
use App\Http\Resources\Admin\DiseaseCategoryResource;
use App\Models\DiseaseCategory;
use Illuminate\Http\Request;

class DiseaseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = DiseaseCategory::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('category_name', 'like', '%' . $request->search . '%'))
            ->orderBy('category_name')
            ->paginate(20);

        return DiseaseCategoryResource::collection($categories);
    }

    public function store(DiseaseCategoryRequest $request)
    {
        $category = DiseaseCategory::create([
            'disease_category_id' => $this->generateId(),
            'category_name'       => $request->category_name,
            'category_name_en'    => $request->category_name_en,
            'description'         => $request->description,
            'status'              => $request->status ?? '1',
            'created_by'          => $request->user()->user_id,
            'updated_by'          => $request->user()->user_id,
        ]);

        return new DiseaseCategoryResource($category);
    }

    public function show(DiseaseCategory $diseaseCategory)
    {
        return new DiseaseCategoryResource($diseaseCategory->load('diseases'));
    }

    public function update(DiseaseCategoryRequest $request, DiseaseCategory $diseaseCategory)
    {
        $diseaseCategory->update([
            'category_name'    => $request->category_name,
            'category_name_en' => $request->category_name_en,
            'description'      => $request->description,
            'status'           => $request->status ?? $diseaseCategory->status,
            'updated_by'       => $request->user()->user_id,
        ]);

        return new DiseaseCategoryResource($diseaseCategory);
    }

    public function destroy(DiseaseCategory $diseaseCategory)
    {
        if ($diseaseCategory->diseases()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีโรคในหมวดหมู่นี้อยู่'
            ], 422);
        }

        $diseaseCategory->delete();

        return response()->json(['message' => 'ลบหมวดหมู่โรคสำเร็จ']);
    }


    private function generateId(): string
    {
        $last = DiseaseCategory::max('disease_category_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
