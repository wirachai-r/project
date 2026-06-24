<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'article_id'          => $this->article_id,
            'title'               => $this->title,
            'title_en'            => $this->title_en,
            'content'             => $this->content,
            'content_en'          => $this->content_en,
            'thumbnail'           => $this->thumbnail,
            'status'              => $this->status,
            'published_at'        => $this->published_at,
            'article_category_id' => $this->article_category_id,
            'category'            => new ArticleCategoryResource($this->whenLoaded('category')),
            'created_by'          => $this->created_by,
            'updated_by'          => $this->updated_by,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
