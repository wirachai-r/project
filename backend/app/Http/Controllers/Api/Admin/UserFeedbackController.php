<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserFeedback;
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
                    $nested->where('message', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($user) use ($search) {
                            $user->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', $request->sort_direction === 'asc' ? 'asc' : 'desc')
            ->paginate($request->integer('per_page', 20));

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
