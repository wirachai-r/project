<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BodyAreaGroupResource extends JsonResource
{
    public function toArray($request): array
    {
        $imageUrl = $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
        if ($imageUrl && ! str_starts_with($imageUrl, 'http')) {
            $imageUrl = rtrim($request->getSchemeAndHttpHost(), '/').'/'.ltrim($imageUrl, '/');
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'description' => $this->description,
            'image_url' => $imageUrl,
            'display_order' => $this->display_order,
            'status' => $this->status,
            'symptoms_count' => $this->whenCounted('symptoms'),
            'symptom_ids' => $this->whenLoaded('symptoms', fn () => $this->symptoms->pluck('symptom_id')->values()),
            'symptoms' => $this->whenLoaded('symptoms', fn () => $this->symptoms->map(fn ($symptom) => [
                'symptom_id' => $symptom->symptom_id,
                'symptom_name' => $symptom->symptom_name,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
