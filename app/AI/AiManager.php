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
     * Provider options for display, including the "where do I get a key"
     * guidance shown on the profile connection form.
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
                'console_url' => $cfg['console_url'] ?? '',
                'console_host' => $cfg['console_host'] ?? '',
                'console_path' => $cfg['console_path'] ?? '',
                'note' => $cfg['note'] ?? '',
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
        return $user !== null && $user->hasAiConnection();
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
     * Resolve the user's default provider: the connection named by
     * `ai_provider`, or their only/first connection as a fallback.
     */
    public function providerFor(User $user): AiProvider
    {
        $connection = ($user->ai_provider ? $user->aiConnection($user->ai_provider) : null)
            ?? $user->aiConnections->first();

        if ($connection === null) {
            throw new AiNotConnectedException();
        }

        return $this->makeProvider($connection->provider, $connection->api_key);
    }

    /**
     * Resolve an AiService bound to the user's connected provider.
     */
    public function serviceFor(User $user): AiService
    {
        return new AiService($this->providerFor($user));
    }

    /**
     * Resolve a provider able to transcribe audio for the user: their
     * default provider when it supports audio, else any of their other
     * connections that does, else the app's server OpenAI key
     * (transcription is the one AI feature Anthropic can't do, so a
     * connected user shouldn't be locked out of it).
     *
     * Returns null when no transcription-capable provider is available.
     */
    public function transcriberFor(User $user): ?AiProvider
    {
        $default = $this->providerFor($user);

        if ($default->supportsTranscription()) {
            return $default;
        }

        foreach ($user->aiConnections as $connection) {
            $provider = $this->makeProvider($connection->provider, $connection->api_key);
            if ($provider->supportsTranscription()) {
                return $provider;
            }
        }

        $serverKey = config('openai.api_key');

        return filled($serverKey)
            ? new OpenAiProvider($serverKey, config('ai.providers.openai', []))
            : null;
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
