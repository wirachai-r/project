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
            'recorded_at' => ($this->recorded_at ?? $this->created_at)?->toISOString(),
            'status' => $this->status,
            'note' => $this->note,
            'symptoms' => $this->whenLoaded('symptoms', fn () => $this->symptoms->map(fn ($symptom) => [
                'symptom_id' => $symptom->symptom_id,
                'symptom_name' => $symptom->symptom_name,
                'symptom_name_en' => $symptom->symptom_name_en,
                'description' => $symptom->description,
                'symptom_image' => $symptom->symptom_image,
                'status' => $symptom->status,
                'symptom_category_id' => $symptom->symptom_category_id,
            ])),
            'health_episodes' => $this->whenLoaded('healthEpisodes', fn () => $this->healthEpisodes->map(fn ($episode) => [
                'id' => $episode->id,
                'status' => $episode->status,
                'started_at' => $episode->started_at,
                'symptom_names' => $episode->symptoms->map(fn ($item) => $item->symptom?->symptom_name ?? $item->custom_symptom_text)->filter()->values(),
            ])),
        ];
    }
}
