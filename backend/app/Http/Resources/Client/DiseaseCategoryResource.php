<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class DiseaseCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'disease_category_id' => $this->disease_category_id,
            'category_name'       => $this->category_name,
            'category_name_en'    => $this->category_name_en,
            'description'         => $this->description,
            'icon'                => $this->icon,
        ];
    }
}
