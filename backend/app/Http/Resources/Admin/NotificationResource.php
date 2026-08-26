<?php

namespace App\Http\Resources\Admin;

use App\Support\NotificationContent;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => NotificationContent::resolveImageUrls($this->body, $request),
            'body_text' => trim(html_entity_decode(strip_tags($this->body))),
            'type' => $this->type,
            'is_read' => $this->is_read === 'Y',
            'read_at' => $this->read_at,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
