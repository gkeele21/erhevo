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
            'audio_model' => env('AI_OPENAI_AUDIO_MODEL', 'gpt-4o-mini-transcribe'),
            'supports_vision' => true,
            'key_hint' => 'Starts with "sk-".',
            // Shown on the profile connection form to guide key creation.
            'console_url' => 'https://platform.openai.com/api-keys',
            'console_host' => 'platform.openai.com',
            'console_path' => 'API keys → Create new secret key',
            'note' => 'Billed by usage — separate from a ChatGPT subscription.',
        ],

        'anthropic' => [
            'label' => 'Anthropic (Claude)',
            'text_model' => env('AI_ANTHROPIC_TEXT_MODEL', 'claude-haiku-4-5'),
            'vision_model' => env('AI_ANTHROPIC_VISION_MODEL', 'claude-sonnet-4-6'),
            'supports_vision' => true,
            'base_uri' => env('AI_ANTHROPIC_BASE_URI', 'https://api.anthropic.com/v1'),
            'version' => env('AI_ANTHROPIC_VERSION', '2023-06-01'),
            'key_hint' => 'Starts with "sk-ant-".',
            'console_url' => 'https://console.anthropic.com',
            'console_host' => 'console.anthropic.com',
            'console_path' => 'API Keys → Create Key',
            'note' => 'Requires API billing/credits — separate from a Claude.ai subscription.',
        ],

        'gemini' => [
            'label' => 'Google Gemini',
            'text_model' => env('AI_GEMINI_TEXT_MODEL', 'gemini-2.0-flash'),
            'vision_model' => env('AI_GEMINI_VISION_MODEL', 'gemini-2.0-flash'),
            'audio_model' => env('AI_GEMINI_AUDIO_MODEL', 'gemini-2.0-flash'),
            'supports_vision' => true,
            'base_uri' => env('AI_GEMINI_BASE_URI', 'https://generativelanguage.googleapis.com/v1beta'),
            'key_hint' => '',
            'console_url' => 'https://aistudio.google.com/apikey',
            'console_host' => 'aistudio.google.com',
            'console_path' => 'Create API key',
            'note' => 'Has a free tier — the easiest way to try AI features out.',
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
