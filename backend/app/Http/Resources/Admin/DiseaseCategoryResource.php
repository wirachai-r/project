<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiseaseCategoryResource extends JsonResource
{
    // app/Http/Resources/Admin/DiseaseCategoryResource.php
    public function toArray($request): array
    {
        return [
            'disease_category_id' => $this->disease_category_id,
            'category_name'       => $this->category_name,
            'category_name_en'    => $this->category_name_en,
            'description'         => $this->description,
            'status'              => $this->status,
            'diseases'            => DiseaseResource::collection($this->whenLoaded('diseases')),
            'created_by'          => $this->created_by,
            'updated_by'          => $this->updated_by,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
