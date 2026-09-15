<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Support\AccountActivityLogger;
use App\Support\ContentImageStorage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @tags Client ProfileController
 */
class ProfileController extends Controller
{
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $user->update([
            'password' => $request->validated('password'),
            'login_attempts' => 0,
            'locked_until' => null,
        ]);

        $currentTokenId = $user->currentAccessToken()?->id;
        $user->tokens()
            ->when($currentTokenId, fn ($query) => $query->where('id', '!=', $currentTokenId))
            ->delete();
        AccountActivityLogger::record($user, 'password_changed', $request);

        return response()->json(['message' => 'เปลี่ยนรหัสผ่านสำเร็จ']);
    }

    public function show(Request $request)
    {
        return new UserResource($request->user());
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $oldProfileImage = $user->profile_image;

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'sex' => 'nullable|in:M,F',
            'profile_image' => 'nullable|string|max:255',
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
        ]);

        $user->update($validated);

        if (array_key_exists('profile_image', $validated)) {
            ContentImageStorage::deleteRemoved(
                [$oldProfileImage],
                [$user->profile_image],
            );
        }

        return new UserResource($user);
    }
}
