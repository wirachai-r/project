<?php

namespace App\Http\Resources;

use App\Support\ImageStorage;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth,
            'sex' => $this->sex,
            'role' => $this->role,
            'status' => $this->status,
            'system_profile_image' => $this->profile_image,
            'profile_image' => $this->profileImageUrl(),
            'avatar' => $this->avatar,
            'google_id' => $this->google_id,
            'assessment_mode' => $this->assessment_mode ?? 'classic',
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function profileImageUrl(): ?string
    {
        $image = $this->profile_image ?: $this->avatar;

        if (! $image || filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        return ImageStorage::disk()->url(ltrim($image, '/'));
    }
}
