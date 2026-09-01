<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiClient implements AiClient
{
    public function generateStructured(string $instructions, array $input, array $schema): array
    {
        $baseUrl = rtrim(config('ai.base_url') ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $model = rawurlencode((string) config('ai.model'));
        $response = Http::acceptJson()->timeout(config('ai.timeout'))
            ->post("{$baseUrl}/models/{$model}:generateContent?key=".urlencode((string) config('ai.api_key')), [
                'systemInstruction' => ['parts' => [['text' => $instructions]]],
                'contents' => [['role' => 'user', 'parts' => [[
                    'text' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]]]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseJsonSchema' => $schema['schema'],
                    'maxOutputTokens' => config('ai.max_output_tokens'),
                ],
            ])->throw()->json();

        $text = data_get($response, 'candidates.0.content.parts.0.text');
        $decoded = is_string($text) ? json_decode($text, true) : null;
        if (! is_array($decoded)) {
            throw new RuntimeException('AI provider returned an invalid structured response.');
        }

        return $decoded;
    }
}
