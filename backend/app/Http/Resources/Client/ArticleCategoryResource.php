<?php

namespace App\Http\Resources\Client;

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
            'icon'                => $this->icon,
        ];
    }
}
