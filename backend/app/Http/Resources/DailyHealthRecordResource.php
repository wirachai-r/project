<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyHealthRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recorded_on' => $this->recorded_on->format('Y-m-d'),
            'status' => $this->status,
            'note' => $this->note,
        ];
    }
}
