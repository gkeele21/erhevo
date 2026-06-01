<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;
use OpenAI as OpenAIClient;

class OpenAiProvider implements AiProvider
{
    public function __construct(
        protected string $apiKey,
        protected array $config = [],
    ) {}

    public function key(): string
    {
        return 'openai';
    }

    public function supportsVision(): bool
    {
        return (bool) ($this->config['supports_vision'] ?? true);
    }

    public function complete(string $system, array $userParts, array $opts = []): string
    {
        $useVision = (bool) ($opts['vision'] ?? false);
        $model = $useVision
            ? ($this->config['vision_model'] ?? 'gpt-4o')
            : ($this->config['text_model'] ?? 'gpt-4o-mini');

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $this->mapParts($userParts)],
            ],
            'max_tokens' => $opts['max_tokens'] ?? 1024,
        ];

        if (isset($opts['temperature'])) {
            $payload['temperature'] = $opts['temperature'];
        }

        if (! empty($opts['json'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $client = OpenAIClient::client($this->apiKey);
            $response = $client->chat()->create($payload);
        } catch (\Throwable $e) {
            throw new \RuntimeException('OpenAI request failed: ' . $e->getMessage(), 0, $e);
        }

        return trim((string) ($response->choices[0]->message->content ?? ''));
    }

    /**
     * Translate normalized parts into OpenAI's chat content format.
     */
    protected function mapParts(array $parts): array
    {
        return array_map(function (array $part) {
            if (($part['type'] ?? null) === 'image') {
                return [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => "data:{$part['mime']};base64,{$part['data']}",
                    ],
                ];
            }

            return ['type' => 'text', 'text' => (string) ($part['text'] ?? '')];
        }, $parts);
    }
}
