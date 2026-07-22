<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @tags Admin UserController
 */

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) ($request->per_page ?? 20);

        $users = User::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            })
            ->when(
                in_array($request->sort_by, ['name', 'last_login', 'role']),
                function ($q) use ($request) {
                    $direction = $request->sort_direction === 'asc' ? 'asc' : 'desc';

                    if ($request->sort_by === 'name') {
                        $q->orderBy('first_name', $direction)->orderBy('last_name', $direction);
                    } elseif ($request->sort_by === 'last_login') {
                        $q->orderBy('last_login_at', $direction);
                    } elseif ($request->sort_by === 'role') {
                        $q->orderBy('role', $direction);
                    }
                },
                fn($q) => $q->orderBy('created_at', 'desc')
            )
            ->paginate($perPage);   // ← ใช้ paginate() แทน get()

        return UserResource::collection($users);
    }

    public function stats()
    {
        $stats = User::selectRaw("
        COUNT(*) as total,
        SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = '2' THEN 1 ELSE 0 END) as banned
    ")->first();

        return response()->json([
            'data' => [
                'total'  => (int) $stats->total,
                'active' => (int) $stats->active,
                'banned' => (int) $stats->banned,
            ],
        ]);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name'  => 'sometimes|string|max:100',
            'role'       => 'sometimes|in:User,Admin',
            'status'     => 'sometimes|in:1,2',
            'email'      => [
                'sometimes',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')
            ],
        ]);

        $user->update($validated);

        return new UserResource($user);
    }

    public function ban(User $user)
    {
        abort_if($user->role === 'Admin', 422, 'ไม่สามารถปิดใช้งานผู้ใช้ที่เป็น ผู้ดูแลระบบ ได้');

        $user->update(['status' => '2']);

        return new UserResource($user);
    }

    public function unban(User $user)
    {
        $user->update(['status' => '1']);

        return new UserResource($user);
    }
}
