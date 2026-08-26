<?php

namespace App\Http\Resources\Admin;

use App\Support\NotificationContent;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DiseaseResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

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
            'references' => $this->references ?? [],
            'disease_image' => $this->disease_image
                ? $disk->url($this->disease_image)
                : null,
            'status' => $this->status,
            'disease_category_id' => $this->disease_category_id,
            'category' => new DiseaseCategoryResource($this->whenLoaded('category')),
            'treatment_orders' => TreatmentOrderResource::collection($this->whenLoaded('treatmentOrders')),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
