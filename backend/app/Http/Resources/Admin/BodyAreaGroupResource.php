<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class BodyAreaGroupResource extends JsonResource
{
    public function toArray($request): array
    {
        $imageUrl = $this->image_path
            ? rtrim($request->getSchemeAndHttpHost(), '/').'/api/media/'.ltrim($this->image_path, '/')
            : null;

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
            'subgroups' => $this->whenLoaded('subgroups', fn () => $this->subgroups->map(fn ($subgroup) => [
                'id' => $subgroup->id,
                'name' => $subgroup->name,
                'name_en' => $subgroup->name_en,
                'description' => $subgroup->description,
                'image_url' => $subgroup->image_path
                    ? rtrim($request->getSchemeAndHttpHost(), '/').'/api/media/'.ltrim($subgroup->image_path, '/')
                    : null,
                'display_order' => $subgroup->display_order,
                'status' => $subgroup->status,
                'symptom_ids' => $subgroup->relationLoaded('symptoms')
                    ? $subgroup->symptoms->pluck('symptom_id')->values()
                    : [],
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
