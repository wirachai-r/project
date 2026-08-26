<?php

namespace App\Http\Resources\Client;

use App\Support\NotificationContent;
use Illuminate\Http\Resources\Json\JsonResource;

class FirstAidResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'first_aid_id' => $this->first_aid_id,
            'title' => $this->title,
            'title_en' => $this->title_en,
            'content' => NotificationContent::resolveImageUrls($this->content, $request),
            'content_en' => NotificationContent::resolveImageUrls($this->content_en, $request),
            'references' => $this->references ?? [],
            'thumbnail' => $this->publicImageUrl($this->thumbnail),
            'created_at' => $this->created_at,
            'published_at' => $this->published_at ?? $this->created_at,
            'updated_at' => $this->updated_at,
            'view_count' => (int) ($this->view_count ?? 0),
            'first_aid_category_id' => $this->first_aid_category_id,
            'category' => new FirstAidCategoryResource($this->whenLoaded('category')),
        ];
    }

    private function publicImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return url('/api/media/'.ltrim($path, '/'));
    }
}
