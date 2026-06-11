<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleCategoryRequest;
use App\Http\Resources\Admin\ArticleCategoryResource;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;

class ArticleCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = ArticleCategory::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('category_name', 'like', '%' . $request->search . '%'))
            ->orderBy('category_name')
            ->paginate(20);

        return ArticleCategoryResource::collection($categories);
    }

    public function store(ArticleCategoryRequest $request)
    {
        $category = ArticleCategory::create([
            'article_category_id' => $this->generateId(),
            'category_name'       => $request->category_name,
            'category_name_en'    => $request->category_name_en,
            'description'         => $request->description,
            'status'              => $request->status ?? '1',
            'created_by'          => $request->user()->user_id,
            'updated_by'          => $request->user()->user_id,
        ]);

        return new ArticleCategoryResource($category);
    }

    public function show(ArticleCategory $articleCategory)
    {
        return new ArticleCategoryResource($articleCategory->load('articles'));
    }

    public function update(ArticleCategoryRequest $request, ArticleCategory $articleCategory)
    {
        $articleCategory->update([
            'category_name'    => $request->category_name,
            'category_name_en' => $request->category_name_en,
            'description'      => $request->description,
            'status'           => $request->status ?? $articleCategory->status,
            'updated_by'       => $request->user()->user_id,
        ]);

        return new ArticleCategoryResource($articleCategory);
    }

    public function destroy(ArticleCategory $articleCategory)
    {
        if ($articleCategory->articles()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีบทความในหมวดหมู่นี้อยู่'
            ], 422);
        }

        $articleCategory->delete();

        return response()->json(['message' => 'ลบหมวดหมู่บทความสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = ArticleCategory::max('article_category_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
