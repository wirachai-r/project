<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiResponsesClient implements AiClient
{
    public function generateStructured(string $instructions, array $input, array $schema): array
    {
        $response = Http::acceptJson()
            ->withToken((string) config('ai.api_key'))
            ->timeout(config('ai.timeout'))
            ->post(rtrim(config('ai.base_url') ?: 'https://api.openai.com/v1', '/').'/responses', [
                'model' => config('ai.model'),
                'instructions' => $instructions,
                'input' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'max_output_tokens' => config('ai.max_output_tokens'),
                'store' => config('ai.store_responses'),
                'text' => ['format' => [
                    'type' => 'json_schema',
                    'name' => $schema['name'],
                    'strict' => true,
                    'schema' => $schema['schema'],
                ]],
            ])->throw()->json();

        $text = collect($response['output'] ?? [])
            ->flatMap(fn ($item) => $item['content'] ?? [])
            ->first(fn ($content) => isset($content['text']))['text'] ?? null;
        $decoded = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($decoded)) {
            throw new RuntimeException('AI provider returned an invalid structured response.');
        }

        return $decoded;
    }
}
