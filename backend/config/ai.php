<?php

return [
    'enabled' => (bool) env('AI_ENABLED', false),
    'provider' => env('AI_PROVIDER', 'fake'),
    'api_key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL'),
    'base_url' => env('AI_BASE_URL'),
    'timeout' => (int) env('AI_TIMEOUT_SECONDS', 30),
    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 900),
    'retry_attempts' => (int) env('AI_RETRY_ATTEMPTS', 2),
    'retry_delay_ms' => (int) env('AI_RETRY_DELAY_MS', 300),
    'trend_cache_minutes' => (int) env('AI_TREND_CACHE_MINUTES', 15),
    'store_responses' => (bool) env('AI_STORE_RESPONSES', false),
    'max_clarification_attempts' => (int) env('AI_MAX_CLARIFICATION_ATTEMPTS', 3),
];
