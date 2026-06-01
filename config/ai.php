<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Erhevo does not ship with a shared, application-wide AI key. Instead each
    | user connects their own account by pasting an API key for one of the
    | providers below. These entries describe how to talk to each provider and
    | which models to use. Model names are configurable here so they can be
    | tuned without touching the provider adapters.
    |
    */

    'providers' => [

        'openai' => [
            'label' => 'OpenAI (ChatGPT)',
            'text_model' => env('AI_OPENAI_TEXT_MODEL', 'gpt-4o-mini'),
            'vision_model' => env('AI_OPENAI_VISION_MODEL', 'gpt-4o'),
            'supports_vision' => true,
            'key_hint' => 'Starts with "sk-". Create one at platform.openai.com/api-keys.',
        ],

        'anthropic' => [
            'label' => 'Anthropic (Claude)',
            'text_model' => env('AI_ANTHROPIC_TEXT_MODEL', 'claude-haiku-4-5'),
            'vision_model' => env('AI_ANTHROPIC_VISION_MODEL', 'claude-sonnet-4-6'),
            'supports_vision' => true,
            'base_uri' => env('AI_ANTHROPIC_BASE_URI', 'https://api.anthropic.com/v1'),
            'version' => env('AI_ANTHROPIC_VERSION', '2023-06-01'),
            'key_hint' => 'Starts with "sk-ant-". Create one at console.anthropic.com.',
        ],

        'gemini' => [
            'label' => 'Google Gemini',
            'text_model' => env('AI_GEMINI_TEXT_MODEL', 'gemini-2.0-flash'),
            'vision_model' => env('AI_GEMINI_VISION_MODEL', 'gemini-2.0-flash'),
            'supports_vision' => true,
            'base_uri' => env('AI_GEMINI_BASE_URI', 'https://generativelanguage.googleapis.com/v1beta'),
            'key_hint' => 'Create one at aistudio.google.com/app/apikey.',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for a provider response.
    |
    */

    'request_timeout' => (int) env('AI_REQUEST_TIMEOUT', 30),

];
