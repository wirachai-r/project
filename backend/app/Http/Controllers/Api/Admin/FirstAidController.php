<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FirstAidRequest;
use App\Http\Resources\Admin\FirstAidResource;
use App\Models\FirstAid;
use App\Support\AdminTableQuery;
use App\Support\ContentImageStorage;
use App\Support\NotificationContent;
use Illuminate\Http\Request;

/**
 * @tags Admin FirstAidController
 */
class FirstAidController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $firstAids = FirstAid::query()
            ->with('category')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->first_aid_category_id, fn ($q) => $q->where('first_aid_category_id', $request->first_aid_category_id))
            ->when($request->filled('first_aid_category_ids'), fn ($q) => $q->whereIn(
                'first_aid_category_id',
                array_filter((array) $request->input('first_aid_category_ids')),
            ))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch($q, $request->search, 'first_aid_id', ['title', 'title_en']))
            ->when(
                in_array($request->sort_by, ['id', 'title', 'published_at', 'created_at', 'updated_at']),
                function ($q) use ($request) {
                    $direction = $request->sort_direction === 'asc' ? 'asc' : 'desc';

                    if ($request->sort_by === 'id') {
                        $q->orderBy('first_aid_id', $direction);
                    } elseif ($request->sort_by === 'title') {
                        $q->orderBy('title', $direction);
                    } elseif ($request->sort_by === 'published_at') {
                        $q->orderBy('published_at', $direction);
                    } elseif ($request->sort_by === 'created_at') {
                        AdminTableQuery::orderByCreatedAt($q, $direction, 'first_aid_id');
                    } elseif ($request->sort_by === 'updated_at') {
                        $q->orderBy('updated_at', $direction);
                    }
                },
                fn ($q) => $q->orderBy('first_aid_id', 'desc')
            )
            ->orderBy('first_aid_id', 'desc')
            ->paginate($perPage);

        return FirstAidResource::collection($firstAids);
    }

    public function store(FirstAidRequest $request)
    {
        $firstAid = FirstAid::create([
            'first_aid_id' => $this->generateId(),
            'title' => $request->title,
            'title_en' => $request->title_en,
            'content' => NotificationContent::normalizeImageUrls($request->content),
            'content_en' => NotificationContent::normalizeImageUrls($request->content_en),
            'thumbnail' => $request->thumbnail,
            'status' => $request->status ?? '2',
            'first_aid_category_id' => $request->first_aid_category_id,
            'references' => $request->input('references', []),
            'created_by' => $request->user()->user_id,
            'updated_by' => $request->user()->user_id,
        ]);

        return new FirstAidResource($firstAid->load('category'));
    }

    public function show(FirstAid $firstAid)
    {
        return new FirstAidResource($firstAid->load('category'));
    }

    public function update(FirstAidRequest $request, FirstAid $firstAid)
    {
        $oldImages = [$firstAid->thumbnail, $firstAid->content, $firstAid->content_en];

        $firstAid->update([
            'title' => $request->has('title') ? $request->title : $firstAid->title,
            'title_en' => $request->has('title_en') ? $request->title_en : $firstAid->title_en,
            'content' => $request->has('content') ? NotificationContent::normalizeImageUrls($request->content) : $firstAid->content,
            'content_en' => $request->has('content_en') ? NotificationContent::normalizeImageUrls($request->content_en) : $firstAid->content_en,
            'thumbnail' => $request->has('thumbnail') ? $request->thumbnail : $firstAid->thumbnail,
            'status' => $request->status ?? $firstAid->status,
            'first_aid_category_id' => $request->has('first_aid_category_id') ? $request->first_aid_category_id : $firstAid->first_aid_category_id,
            'references' => $request->has('references') ? $request->input('references') : $firstAid->references,
            'updated_by' => $request->user()->user_id,
        ]);

        ContentImageStorage::deleteRemoved($oldImages, [
            $firstAid->thumbnail,
            $firstAid->content,
            $firstAid->content_en,
        ]);

        return new FirstAidResource($firstAid->load('category'));
    }

    public function destroy(FirstAid $firstAid)
    {
        $images = ContentImageStorage::paths([
            $firstAid->thumbnail,
            $firstAid->content,
            $firstAid->content_en,
        ]);
        $firstAid->delete();
        ContentImageStorage::delete($images);

        return response()->json(['message' => 'ลบข้อมูลปฐมพยาบาลสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = FirstAid::max('first_aid_id');
        $next = $last ? (int) $last + 1 : 1;

        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
