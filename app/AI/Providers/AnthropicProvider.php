<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;

class AnthropicProvider implements AiProvider
{
    public function __construct(
        protected string $apiKey,
        protected array $config = [],
        protected int $timeout = 30,
    ) {}

    public function key(): string
    {
        return 'anthropic';
    }

    public function supportsVision(): bool
    {
        return (bool) ($this->config['supports_vision'] ?? true);
    }

    public function complete(string $system, array $userParts, array $opts = []): string
    {
        $useVision = (bool) ($opts['vision'] ?? false);
        $model = $useVision
            ? ($this->config['vision_model'] ?? 'claude-sonnet-4-6')
            : ($this->config['text_model'] ?? 'claude-haiku-4-5');

        $payload = [
            'model' => $model,
            'max_tokens' => $opts['max_tokens'] ?? 1024,
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $this->mapParts($userParts)],
            ],
        ];

        if (isset($opts['temperature'])) {
            $payload['temperature'] = $opts['temperature'];
        }

        $base = rtrim($this->config['base_uri'] ?? 'https://api.anthropic.com/v1', '/');

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->config['version'] ?? '2023-06-01',
            ])
            ->acceptJson()
            ->post("{$base}/messages", $payload);

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('Anthropic request failed: ' . $message);
        }

        // Concatenate all text blocks in the response.
        $text = collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        return trim($text);
    }

    /**
     * Translate normalized parts into Anthropic's content-block format.
     */
    protected function mapParts(array $parts): array
    {
        return array_map(function (array $part) {
            if (($part['type'] ?? null) === 'image') {
                return [
                    'type' => 'image',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => $part['mime'],
                        'data' => $part['data'],
                    ],
                ];
            }

            return ['type' => 'text', 'text' => (string) ($part['text'] ?? '')];
        }, $parts);
    }
}
