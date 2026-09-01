<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Disease;
use App\Models\FirstAid;
use App\Models\MainSymptom;
use App\Models\UserFeedback;
use App\Support\AdminTableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserFeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = UserFeedback::query()
            ->with(['user:user_id,first_name,last_name,email', 'reviewer:user_id,first_name,last_name'])
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->feedback_type, fn ($query, $type) => $query->where('feedback_type', $type))
            ->when($request->search, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    AdminTableQuery::fuzzySearch($nested, $search, 'id', ['message']);
                    $nested->orWhereHas('user', fn ($user) => AdminTableQuery::fuzzySearch(
                        $user,
                        $search,
                        'user_id',
                        ['first_name', 'last_name', 'email'],
                    ));
                });
            })
            ->orderBy('created_at', $request->sort_direction === 'asc' ? 'asc' : 'desc')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        $targetModels = [
            'article' => [Article::class, 'article_id', 'title'],
            'disease' => [Disease::class, 'disease_id', 'disease_name'],
            'symptom' => [MainSymptom::class, 'symptom_id', 'symptom_name'],
            'first_aid' => [FirstAid::class, 'first_aid_id', 'title'],
        ];
        $targetNames = [];

        foreach ($targetModels as $type => [$model, $key, $name]) {
            $ids = $items->getCollection()
                ->where('target_type', $type)
                ->pluck('target_id')
                ->filter()
                ->unique();
            $targetNames[$type] = $ids->isEmpty()
                ? collect()
                : $model::query()->whereIn($key, $ids)->pluck($name, $key);
        }

        $items->getCollection()->each(function (UserFeedback $feedback) use ($targetNames) {
            $feedback->setAttribute(
                'target_name',
                ($targetNames[$feedback->target_type] ?? collect())->get($feedback->target_id),
            );
        });

        return response()->json($items);
    }

    public function update(Request $request, UserFeedback $feedback): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['in_review', 'resolved', 'dismissed'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $feedback->update([
            ...$validated,
            'reviewed_by' => $request->user()->user_id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['message' => 'อัปเดตสถานะเรียบร้อยแล้ว', 'data' => $feedback]);
    }
}
