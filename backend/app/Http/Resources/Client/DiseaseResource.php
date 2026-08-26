<?php

namespace App\Http\Resources\Client;

use App\Support\NotificationContent;
use Illuminate\Http\Resources\Json\JsonResource;

class DiseaseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'disease_id' => $this->disease_id,
            'disease_name' => $this->disease_name,
            'disease_name_en' => $this->disease_name_en,
            'description' => NotificationContent::resolveImageUrls($this->description, $request),
            'cause' => NotificationContent::resolveImageUrls($this->cause, $request),
            'symptom_description' => NotificationContent::resolveImageUrls($this->symptom_description, $request),
            'complications' => NotificationContent::resolveImageUrls($this->complications, $request),
            'diagnosis' => NotificationContent::resolveImageUrls($this->diagnosis, $request),
            'medical_treatment' => NotificationContent::resolveImageUrls($this->medical_treatment, $request),
            'self_care' => NotificationContent::resolveImageUrls($this->self_care, $request),
            'when_to_see_doctor' => NotificationContent::resolveImageUrls($this->when_to_see_doctor, $request),
            'prevention' => NotificationContent::resolveImageUrls($this->prevention, $request),
            'recommendations' => NotificationContent::resolveImageUrls($this->recommendations, $request),
            'disease_image' => $this->publicImageUrl($this->disease_image),
            'reference' => $this->reference,
            'references' => $this->references ?? [],
            'published_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'view_count' => (int) ($this->view_count ?? 0),
            'disease_category_id' => $this->disease_category_id,
            'category' => new DiseaseCategoryResource($this->whenLoaded('category')),
            'treatment_orders' => TreatmentOrderResource::collection($this->whenLoaded('treatmentOrders')),
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

        return url('/api/media/'.ltrim($path, '/'));
    }
}
