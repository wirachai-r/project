<?php

namespace App\Http\Resources\Admin;

use App\Support\ImageStorage;
use App\Support\NotificationContent;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Resources\Json\JsonResource;

class FirstAidResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var FilesystemAdapter $disk */
        $disk = ImageStorage::disk();

        return [
            'first_aid_id' => $this->first_aid_id,
            'title' => $this->title,
            'title_en' => $this->title_en,
            'content' => NotificationContent::resolveImageUrls($this->content, $request),
            'content_en' => NotificationContent::resolveImageUrls($this->content_en, $request),
            'references' => $this->references ?? [],
            'thumbnail' => $this->thumbnail
                ? $disk->url($this->thumbnail)
                : null,
            'status' => $this->status,
            'first_aid_category_id' => $this->first_aid_category_id,
            'category' => new FirstAidCategoryResource($this->whenLoaded('category')),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
