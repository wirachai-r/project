<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Support\AccountActivityLogger;
use App\Support\ContentImageStorage;
use App\Support\GoogleAvatarStorage;
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
        $user = $request->user();

        // Google image URLs can reject repeated browser hot-link requests with
        // HTTP 429. Cache the avatar on our image disk and return our own URL.
        $remoteImageField = filter_var($user->profile_image, FILTER_VALIDATE_URL)
            ? 'profile_image'
            : (filter_var($user->avatar, FILTER_VALIDATE_URL) ? 'avatar' : null);
        if ($remoteImageField) {
            $cachedAvatar = GoogleAvatarStorage::cache($user, $user->{$remoteImageField});
            if ($cachedAvatar) {
                $user->forceFill([$remoteImageField => $cachedAvatar])->saveQuietly();
            }
        }

        return new UserResource($user);
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
            'assessment_mode' => 'sometimes|in:classic,adaptive',
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
