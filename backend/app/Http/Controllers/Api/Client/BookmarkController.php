<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\BookmarkResource;
use App\Models\UserBookmark;
use Illuminate\Http\Request;

/**
 * @tags Client BookmarkController
 */

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $bookmarks = UserBookmark::query()
            ->with('bookmarkable')
            ->where('user_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return BookmarkResource::collection($bookmarks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'bookmarkable_type' => 'required|in:App\Models\Article,App\Models\Disease,App\Models\FirstAid',
            'bookmarkable_id'   => 'required|string',
        ]);

        $bookmark = UserBookmark::firstOrCreate([
            'user_id'           => $request->user()->user_id,
            'bookmarkable_type' => $request->bookmarkable_type,
            'bookmarkable_id'   => $request->bookmarkable_id,
        ]);

        return new BookmarkResource($bookmark);
    }

    public function destroy(Request $request, UserBookmark $bookmark)
    {
        abort_if($bookmark->user_id !== $request->user()->user_id, 403);

        $bookmark->delete();

        return response()->json(['message' => 'ลบ bookmark สำเร็จ']);
    }
}
