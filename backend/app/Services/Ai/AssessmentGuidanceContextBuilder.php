<?php

namespace App\Services\Ai;

use App\Models\Assessment;
use Illuminate\Support\Str;

abstract class AssessmentGuidanceContextBuilder
{
    /** @return array{instructions: string, input: array<string, mixed>, actions: array<int, string>} */
    abstract public function build(Assessment $assessment): array;

    protected function symptom(Assessment $assessment): array
    {
        return [
            'name' => $assessment->symptom?->symptom_name,
            'description' => $this->plainText($assessment->symptom?->description),
        ];
    }

    protected function clarificationObservations(Assessment $assessment): array
    {
        return $assessment->clarificationSessions
            ->flatMap(fn ($session) => $session->questions->map(fn ($question) => [
                'question' => $question->question_text,
                'answer' => $question->answer?->choice?->choice_text,
            ]))
            ->filter(fn ($item) => $item['answer'] !== null)
            ->values()
            ->all();
    }

    protected function diseaseContext($diseases, bool $includeMatchCounts = false): array
    {
        return $diseases->map(function ($disease) use ($includeMatchCounts) {
            $context = [
                'name' => $disease->disease_name,
                'description' => $this->plainText($disease->description),
                'symptom_description' => $this->plainText($disease->symptom_description),
                'self_care' => $this->plainText($disease->self_care),
                'when_to_see_doctor' => $this->plainText($disease->when_to_see_doctor),
                'recommendations' => $this->plainText($disease->recommendations),
            ];

            if ($includeMatchCounts) {
                $context['supporting_symptom_count'] = $disease->pivot?->supporting_symptom_count;
                $context['evaluated_symptom_count'] = $disease->pivot?->evaluated_symptom_count;
            }

            return $context;
        })->values()->all();
    }

    protected function plainText(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $plain = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;

        return Str::limit(trim($plain), 2000, '…');
    }
}
