<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class FirstAidResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'first_aid_id'          => $this->first_aid_id,
            'title'                 => $this->title,
            'title_en'              => $this->title_en,
            'content'               => $this->content,
            'content_en'            => $this->content_en,
            'thumbnail'             => $this->thumbnail,
            'published_at'          => $this->published_at,
            'first_aid_category_id' => $this->first_aid_category_id,
            'category'              => new FirstAidCategoryResource($this->whenLoaded('category')),
        ];
    }
}
