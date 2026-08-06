<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'assessment_status' => $this->assessment_status,
            'started_at'        => $this->started_at,
            'completed_at'      => $this->completed_at,
            'is_saved'          => (bool) $this->is_saved,
            'symptom_id'        => $this->symptom_id,
            'diagram_id'        => $this->diagram_id,
            'symptom'           => new SymptomResource($this->whenLoaded('symptom')),
            'results'           => AssessmentResultResource::collection($this->whenLoaded('results')),
            'created_at'        => $this->created_at,
        ];
    }
}
