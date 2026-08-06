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
            'disease_image'       => $this->publicImageUrl($this->disease_image),
            'reference'           => $this->reference,
            'published_at'        => $this->created_at,
            'updated_at'          => $this->updated_at,
            'view_count'          => (int) ($this->view_count ?? 0),
            'disease_category_id' => $this->disease_category_id,
            'category'            => new DiseaseCategoryResource($this->whenLoaded('category')),
            'treatment_orders'    => TreatmentOrderResource::collection($this->whenLoaded('treatmentOrders')),
        ];
    }

    private function publicImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return url('/api/media/' . ltrim($path, '/'));
    }
}
