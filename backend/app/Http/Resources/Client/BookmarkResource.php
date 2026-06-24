<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class BookmarkResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'bookmarkable_type' => $this->bookmarkable_type,
            'bookmarkable_id'   => $this->bookmarkable_id,
            'bookmarkable'      => $this->whenLoaded('bookmarkable'),
            'created_at'        => $this->created_at,
        ];
    }
}
