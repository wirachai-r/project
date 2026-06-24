<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user_id'       => $this->user_id,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'date_of_birth' => $this->date_of_birth,
            'sex'           => $this->sex,
            'role'          => $this->role,
            'status'        => $this->status,
            'profile_image' => $this->profile_image,
            'created_at'    => $this->created_at,
        ];
    }
}
