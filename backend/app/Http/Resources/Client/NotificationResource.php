<?php

namespace App\Http\Resources\Client;

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
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'target_date' => $this->target_date?->toDateString(),
            'target_url' => $this->campaign?->target_url,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at,
            'dismissed_at' => $this->dismissed_at,
            'created_at' => $this->created_at,
        ];
    }
}
