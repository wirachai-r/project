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
        $users = User::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, fn($q) => $q
                ->where('first_name', 'like', '%' . $request->search . '%')
                ->orWhere('last_name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%')
            )
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return UserResource::collection($users);
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
            'email'      => ['sometimes', 'email', 'max:150',
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
