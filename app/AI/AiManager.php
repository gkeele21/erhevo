<?php

namespace App\AI;

use App\AI\Contracts\AiProvider;
use App\AI\Exceptions\AiNotConnectedException;
use App\AI\Providers\AnthropicProvider;
use App\AI\Providers\GeminiProvider;
use App\AI\Providers\OpenAiProvider;
use App\Models\User;
use App\Services\AiService;

class AiManager
{
    /**
     * Provider keys that users are allowed to connect.
     *
     * @return array<int, string>
     */
    public function availableProviders(): array
    {
        return array_keys(config('ai.providers', []));
    }

    /**
     * Provider options for display: ['key' => ..., 'label' => ..., 'key_hint' => ...].
     *
     * @return array<int, array<string, string>>
     */
    public function providerOptions(): array
    {
        return collect(config('ai.providers', []))
            ->map(fn (array $cfg, string $key) => [
                'key' => $key,
                'label' => $cfg['label'] ?? $key,
                'key_hint' => $cfg['key_hint'] ?? '',
            ])
            ->values()
            ->all();
    }

    public function isValidProvider(?string $provider): bool
    {
        return $provider !== null && in_array($provider, $this->availableProviders(), true);
    }

    public function isConnected(?User $user): bool
    {
        return $user !== null
            && $this->isValidProvider($user->ai_provider)
            && filled($user->ai_api_key);
    }

    /**
     * Build a provider instance from an explicit provider key + API key.
     */
    public function makeProvider(string $provider, string $apiKey): AiProvider
    {
        if (! $this->isValidProvider($provider)) {
            throw new \InvalidArgumentException("Unsupported AI provider: {$provider}");
        }

        $config = config("ai.providers.{$provider}", []);
        $timeout = (int) config('ai.request_timeout', 30);

        return match ($provider) {
            'openai' => new OpenAiProvider($apiKey, $config),
            'anthropic' => new AnthropicProvider($apiKey, $config, $timeout),
            'gemini' => new GeminiProvider($apiKey, $config, $timeout),
        };
    }

    /**
     * Resolve the provider configured for the given user.
     */
    public function providerFor(User $user): AiProvider
    {
        if (! $this->isConnected($user)) {
            throw new AiNotConnectedException();
        }

        return $this->makeProvider($user->ai_provider, $user->ai_api_key);
    }

    /**
     * Resolve an AiService bound to the user's connected provider.
     */
    public function serviceFor(User $user): AiService
    {
        return new AiService($this->providerFor($user));
    }

    /**
     * Verify that a provider/key pair works by issuing a tiny request.
     *
     * @return array{ok: bool, error?: string}
     */
    public function testConnection(string $provider, string $apiKey): array
    {
        try {
            $reply = $this->makeProvider($provider, $apiKey)->complete(
                'You are a connection test. Reply with the single word: ok.',
                [['type' => 'text', 'text' => 'ping']],
                ['max_tokens' => 5],
            );

            return ['ok' => $reply !== ''];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
