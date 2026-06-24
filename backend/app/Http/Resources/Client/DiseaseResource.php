<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class DiseaseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'disease_id'          => $this->disease_id,
            'disease_name'        => $this->disease_name,
            'disease_name_en'     => $this->disease_name_en,
            'description'         => $this->description,
            'cause'               => $this->cause,
            'symptom_description' => $this->symptom_description,
            'prevention'          => $this->prevention,
            'disease_image'       => $this->disease_image,
            'disease_category_id' => $this->disease_category_id,
            'category'            => new DiseaseCategoryResource($this->whenLoaded('category')),
            'treatment_orders'    => TreatmentOrderResource::collection($this->whenLoaded('treatmentOrders')),
        ];
    }
}
