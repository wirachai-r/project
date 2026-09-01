<?php

return [
    'enabled' => (bool) env('AI_ENABLED', false),
    'provider' => env('AI_PROVIDER', 'fake'),
    'api_key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL'),
    'base_url' => env('AI_BASE_URL'),
    'timeout' => (int) env('AI_TIMEOUT_SECONDS', 15),
    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 500),
    'store_responses' => (bool) env('AI_STORE_RESPONSES', false),
    'max_clarification_attempts' => (int) env('AI_MAX_CLARIFICATION_ATTEMPTS', 3),
];
