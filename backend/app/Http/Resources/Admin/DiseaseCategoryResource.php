<?php

namespace App\Http\Resources\Admin;

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
            'status'              => $this->status,
            'diseases_count'      => $this->whenCounted('diseases'),
            'diseases'            => DiseaseResource::collection($this->whenLoaded('diseases')),
            'created_by'          => $this->created_by,
            'updated_by'          => $this->updated_by,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
