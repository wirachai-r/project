<?php

namespace App\Http\Resources\Admin;

use App\Support\NotificationContent;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArticleResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return [
            'article_id' => $this->article_id,
            'title' => $this->title,
            'title_en' => $this->title_en,
            'content' => NotificationContent::resolveImageUrls($this->content, $request),
            'content_en' => NotificationContent::resolveImageUrls($this->content_en, $request),
            'references' => $this->references ?? [],
            'thumbnail' => $this->thumbnail
                ? $disk->url($this->thumbnail)
                : null,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'article_category_id' => $this->article_category_id,
            'category' => new ArticleCategoryResource($this->whenLoaded('category')),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
