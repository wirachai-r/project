<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class SymptomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'symptom_id'          => $this->symptom_id,
            'symptom_name'        => $this->symptom_name,
            'symptom_name_en'     => $this->symptom_name_en,
            'description'         => $this->description,
            'symptom_image'       => $this->symptom_image,
            'status'              => $this->status,
            'symptom_category_id' => $this->symptom_category_id,
            'category'            => new SymptomCategoryResource($this->whenLoaded('category')),
            'created_by'          => $this->created_by,
            'updated_by'          => $this->updated_by,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
