<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'order_id'       => $this->order_id,
            'order_name'     => $this->order_name,
            'order_name_en'  => $this->order_name_en,
            'description'    => $this->description,
            'urgency_type'   => $this->urgency_type,
            'order_sequence' => $this->order_sequence,
            'status'         => $this->status,
            'disease_id'     => $this->disease_id,
            'disease'        => new DiseaseResource($this->whenLoaded('disease')),
            'created_by'     => $this->created_by,
            'updated_by'     => $this->updated_by,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
