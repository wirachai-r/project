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
            'complications'       => $this->complications,
            'diagnosis'           => $this->diagnosis,
            'medical_treatment'   => $this->medical_treatment,
            'self_care'           => $this->self_care,
            'when_to_see_doctor'  => $this->when_to_see_doctor,
            'prevention'          => $this->prevention,
            'recommendations'     => $this->recommendations,
            'disease_image'       => $this->disease_image,
            'disease_category_id' => $this->disease_category_id,
            'category'            => new DiseaseCategoryResource($this->whenLoaded('category')),
            'treatment_orders'    => TreatmentOrderResource::collection($this->whenLoaded('treatmentOrders')),
        ];
    }
}
