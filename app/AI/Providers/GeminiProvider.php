<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProvider
{
    public function __construct(
        protected string $apiKey,
        protected array $config = [],
        protected int $timeout = 30,
    ) {}

    public function key(): string
    {
        return 'gemini';
    }

    public function supportsVision(): bool
    {
        return (bool) ($this->config['supports_vision'] ?? true);
    }

    public function supportsTranscription(): bool
    {
        return true;
    }

    public function transcribeAudio(string $path, string $mime = 'audio/mpeg'): string
    {
        $model = $this->config['audio_model'] ?? 'gemini-2.0-flash';

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => 'Transcribe the speech in this audio verbatim. Return only the spoken words with sensible punctuation — no commentary, no timestamps, no speaker labels.'],
                        ['inlineData' => ['mimeType' => $mime, 'data' => base64_encode(file_get_contents($path))]],
                    ],
                ],
            ],
        ];

        $base = rtrim($this->config['base_uri'] ?? 'https://generativelanguage.googleapis.com/v1beta', '/');

        // Audio uploads are slow to process; give this more room than text calls.
        $response = Http::timeout(max($this->timeout, 120))
            ->acceptJson()
            ->withHeaders(['x-goog-api-key' => $this->apiKey])
            ->post("{$base}/models/{$model}:generateContent", $payload);

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('Gemini transcription failed: ' . $message);
        }

        $text = collect($response->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->filter()
            ->implode('');

        return trim($text);
    }

    public function complete(string $system, array $userParts, array $opts = []): string
    {
        $useVision = (bool) ($opts['vision'] ?? false);
        $model = $useVision
            ? ($this->config['vision_model'] ?? 'gemini-2.0-flash')
            : ($this->config['text_model'] ?? 'gemini-2.0-flash');

        $generationConfig = [
            'maxOutputTokens' => $opts['max_tokens'] ?? 1024,
        ];

        if (isset($opts['temperature'])) {
            $generationConfig['temperature'] = $opts['temperature'];
        }

        if (! empty($opts['json'])) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => [
                ['role' => 'user', 'parts' => $this->mapParts($userParts)],
            ],
            'generationConfig' => $generationConfig,
        ];

        $base = rtrim($this->config['base_uri'] ?? 'https://generativelanguage.googleapis.com/v1beta', '/');

        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(['x-goog-api-key' => $this->apiKey])
            ->post("{$base}/models/{$model}:generateContent", $payload);

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('Gemini request failed: ' . $message);
        }

        $text = collect($response->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->filter()
            ->implode('');

        return trim($text);
    }

    /**
     * Translate normalized parts into Gemini's part format.
     */
    protected function mapParts(array $parts): array
    {
        return array_map(function (array $part) {
            if (($part['type'] ?? null) === 'image') {
                return [
                    'inlineData' => [
                        'mimeType' => $part['mime'],
                        'data' => $part['data'],
                    ],
                ];
            }

            return ['text' => (string) ($part['text'] ?? '')];
        }, $parts);
    }
}
