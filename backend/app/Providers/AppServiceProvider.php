<?php

namespace App\Providers;

use App\Contracts\AiClient;
use App\Services\Ai\FakeAiClient;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\OpenAiResponsesClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiClient::class, function () {
            if (! config('ai.enabled')) {
                return new FakeAiClient;
            }

            return match (config('ai.provider')) {
                'openai' => new OpenAiResponsesClient,
                'gemini' => new GeminiClient,
                'fake' => new FakeAiClient,
                default => throw new \InvalidArgumentException('Unsupported AI_PROVIDER.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
