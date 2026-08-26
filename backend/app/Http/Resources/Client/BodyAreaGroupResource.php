<?php

namespace App\Http\Resources\Client;

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
            'symptoms_count' => $this->whenCounted('symptoms'),
        ];
    }
}
