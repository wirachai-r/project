<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class FirstAidCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'first_aid_category_id' => $this->first_aid_category_id,
            'category_name'         => $this->category_name,
            'category_name_en'      => $this->category_name_en,
            'description'           => $this->description,
            'status'                => $this->status,
            'first_aids'            => FirstAidResource::collection($this->whenLoaded('firstAids')),
            'created_by'            => $this->created_by,
            'updated_by'            => $this->updated_by,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
