<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\UnifiedSearchRequest;
use App\Models\Article;
use App\Models\Disease;
use App\Models\FirstAid;
use App\Models\MainSymptom;
use App\Support\AdminTableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class UnifiedSearchController extends Controller
{
    public function __invoke(UnifiedSearchRequest $request): JsonResponse
    {
        $query = trim($request->validated('q'));
        $limit = (int) ($request->validated('limit') ?? 8);

        return response()->json(['data' => [
            'query' => $query,
            'diseases' => Disease::query()
                ->where('status', '1')
                ->tap(fn ($builder) => AdminTableQuery::fuzzySearch($builder, $query, 'disease_id', ['disease_name', 'disease_name_en', 'description']))
                ->limit($limit)
                ->get(['disease_id as id', 'disease_name as title', 'disease_name_en as title_en', 'description', 'disease_image as thumbnail'])
                ->map(fn (Disease $item) => $this->result($item, 'disease')),
            'symptoms' => MainSymptom::query()
                ->where('status', '1')
                ->tap(fn ($builder) => AdminTableQuery::fuzzySearch($builder, $query, 'symptom_id', ['symptom_name', 'symptom_name_en', 'description']))
                ->limit($limit)
                ->get(['symptom_id as id', 'symptom_name as title', 'symptom_name_en as title_en', 'description', 'symptom_image as thumbnail'])
                ->map(fn (MainSymptom $item) => $this->result($item, 'symptom')),
            'articles' => Article::query()
                ->where('status', '1')
                ->tap(fn ($builder) => AdminTableQuery::fuzzySearch($builder, $query, 'article_id', ['title', 'title_en', 'content']))
                ->latest('published_at')
                ->limit($limit)
                ->get(['article_id as id', 'title', 'title_en', 'content as description', 'thumbnail'])
                ->map(fn (Article $item) => $this->result($item, 'article')),
            'first_aids' => FirstAid::query()
                ->where('status', '1')
                ->tap(fn ($builder) => AdminTableQuery::fuzzySearch($builder, $query, 'first_aid_id', ['title', 'title_en', 'content']))
                ->latest('published_at')
                ->limit($limit)
                ->get(['first_aid_id as id', 'title', 'title_en', 'content as description', 'thumbnail'])
                ->map(fn (FirstAid $item) => $this->result($item, 'first_aid')),
        ]]);
    }

    private function result(object $item, string $type): array
    {
        return [
            'id' => $item->id,
            'type' => $type,
            'title' => $item->title,
            'title_en' => $item->title_en,
            'summary' => Str::limit(trim(strip_tags((string) $item->description)), 160),
            'thumbnail' => $this->publicImageUrl($item->thumbnail ?? null),
        ];
    }

    private function publicImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return url('/api/media/'.ltrim($path, '/'));
    }
}
