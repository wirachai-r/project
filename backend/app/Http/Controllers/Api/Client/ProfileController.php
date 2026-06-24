<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @tags Client ProfileController
 */

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return new UserResource($request->user());
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name'    => 'sometimes|string|max:100',
            'last_name'     => 'sometimes|string|max:100',
            'phone'         => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'sex'           => 'nullable|in:M,F',
            'profile_image' => 'nullable|string|max:255',
            'email'         => ['sometimes', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
        ]);

        $user->update($validated);

        return new UserResource($user);
    }
}
