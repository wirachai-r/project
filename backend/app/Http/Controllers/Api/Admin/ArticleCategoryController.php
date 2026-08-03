<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleCategoryRequest;
use App\Http\Resources\Admin\ArticleCategoryResource;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;

/**
 * @tags Admin ArticleCategoryController
 */

class ArticleCategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) ($request->per_page ?? 20);

        $categories = ArticleCategory::query()
            ->withCount('articles')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('category_name', 'like', '%' . $request->search . '%'))
            ->when(
                in_array($request->sort_by, ['id', 'name']),
                function ($q) use ($request) {
                    $direction = $request->sort_direction === 'asc' ? 'asc' : 'desc';

                    if ($request->sort_by === 'id') {
                        $q->orderBy('article_category_id', $direction);
                    } elseif ($request->sort_by === 'name') {
                        $q->orderBy('category_name', $direction);
                    }
                },
                fn($q) => $q->orderBy('article_category_id', 'desc')
            )
            ->paginate($perPage);

        return ArticleCategoryResource::collection($categories);
    }

    public function store(ArticleCategoryRequest $request)
    {
        $category = ArticleCategory::create([
            'article_category_id' => $this->generateId(),
            'category_name'       => $request->category_name,
            'category_name_en'    => $request->category_name_en,
            'description'         => $request->description,
            'icon'                => $request->icon,
            'status'              => $request->status ?? '1',
            'created_by'          => $request->user()->user_id,
            'updated_by'          => $request->user()->user_id,
        ]);

        return new ArticleCategoryResource($category);
    }

    public function show(ArticleCategory $articleCategory)
    {
        return new ArticleCategoryResource($articleCategory->loadCount('articles'));
    }

    public function update(ArticleCategoryRequest $request, ArticleCategory $articleCategory)
    {
        $articleCategory->update([
            'category_name'    => $request->category_name ?? $articleCategory->category_name,
            'category_name_en' => $request->has('category_name_en') ? $request->category_name_en : $articleCategory->category_name_en,
            'description'      => $request->has('description') ? $request->description : $articleCategory->description,
            'icon'             => $request->has('icon') ? $request->icon : $articleCategory->icon,
            'status'           => $request->status ?? $articleCategory->status,
            'updated_by'       => $request->user()->user_id,
        ]);

        return new ArticleCategoryResource($articleCategory->loadCount('articles'));
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
