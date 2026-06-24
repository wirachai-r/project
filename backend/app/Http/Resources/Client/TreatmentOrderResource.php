<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'order_id'       => $this->order_id,
            'order_name'     => $this->order_name,
            'order_name_en'  => $this->order_name_en,
            'description'    => $this->description,
            'urgency_type'   => $this->urgency_type,
            'order_sequence' => $this->order_sequence,
        ];
    }
}
