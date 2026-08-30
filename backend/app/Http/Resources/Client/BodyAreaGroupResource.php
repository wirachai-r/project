<?php

namespace App\Http\Resources\Client;

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
            'symptoms_count' => $this->whenCounted('symptoms'),
            'subgroups' => $this->whenLoaded('subgroups', fn () => $this->subgroups->map(fn ($subgroup) => [
                'id' => $subgroup->id,
                'name' => $subgroup->name,
                'name_en' => $subgroup->name_en,
                'description' => $subgroup->description,
                'image_url' => $subgroup->image_path
                    ? rtrim($request->getSchemeAndHttpHost(), '/').'/api/media/'.ltrim($subgroup->image_path, '/')
                    : null,
                'symptoms_count' => $subgroup->symptoms_count ?? 0,
            ])),
        ];
    }
}
