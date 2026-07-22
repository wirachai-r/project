<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

class DiseaseResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return [
            'disease_id'           => $this->disease_id,
            'disease_name'         => $this->disease_name,
            'disease_name_en'      => $this->disease_name_en,
            'description'          => $this->description,
            'cause'                => $this->cause,
            'symptom_description'  => $this->symptom_description,
            'prevention'           => $this->prevention,
            'disease_image'        => $this->disease_image
                ? $disk->url($this->disease_image)
                : null,
            'status'               => $this->status,
            'disease_category_id'  => $this->disease_category_id,
            'category'             => new DiseaseCategoryResource($this->whenLoaded('category')),
            'treatment_orders'     => TreatmentOrderResource::collection($this->whenLoaded('treatmentOrders')),
            'created_by'           => $this->created_by,
            'updated_by'           => $this->updated_by,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
