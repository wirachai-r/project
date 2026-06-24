<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'article_category_id' => $this->article_category_id,
            'category_name'       => $this->category_name,
            'category_name_en'    => $this->category_name_en,
            'description'         => $this->description,
            'status'              => $this->status,
            'articles_count'      => $this->whenCounted('articles'),
            'articles'            => ArticleResource::collection($this->whenLoaded('articles')),
            'created_by'          => $this->created_by,
            'updated_by'          => $this->updated_by,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
