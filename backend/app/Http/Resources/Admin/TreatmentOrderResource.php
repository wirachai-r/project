<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'treatment_id'      => $this->treatment_id,
            'treatment_text'    => $this->treatment_text,
            'treatment_text_en' => $this->treatment_text_en,
            'order'             => $this->order,
            'status'            => $this->status,
            'disease_id'        => $this->disease_id,
            'disease'           => new DiseaseResource($this->whenLoaded('disease')),
            'created_by'        => $this->created_by,
            'updated_by'        => $this->updated_by,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
