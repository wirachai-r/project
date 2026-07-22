<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

class FirstAidResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return [
            'first_aid_id'          => $this->first_aid_id,
            'title'                 => $this->title,
            'title_en'              => $this->title_en,
            'content'               => $this->content,
            'content_en'            => $this->content_en,
            'thumbnail'             => $this->thumbnail
                ? $disk->url($this->thumbnail)
                : null,
            'status'                => $this->status,
            'first_aid_category_id' => $this->first_aid_category_id,
            'category'              => new FirstAidCategoryResource($this->whenLoaded('category')),
            'created_by'            => $this->created_by,
            'updated_by'            => $this->updated_by,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
