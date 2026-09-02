<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'assessment_status' => $this->assessment_status,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'is_saved' => (bool) $this->is_saved,
            'symptom_id' => $this->symptom_id,
            'diagram_id' => $this->diagram_id,
            'symptom' => new SymptomResource($this->whenLoaded('symptom')),
            'results' => AssessmentResultResource::collection($this->whenLoaded('results')),
            'created_at' => $this->created_at,
            'health_episode' => $this->whenLoaded('healthEpisode', function () {
                $episode = $this->healthEpisode->first();
                if (! $episode) {
                    return null;
                }

                return [
                    'id' => $episode->id,
                    'status' => $episode->status,
                    'started_at' => $episode->started_at,
                    'ended_at' => $episode->ended_at,
                    'end_reason' => $episode->end_reason,
                    'symptom_names' => $episode->symptoms->map(fn ($item) => $item->symptom?->symptom_name ?? $item->custom_symptom_text)->filter()->values(),
                ];
            }),
        ];
    }
}
