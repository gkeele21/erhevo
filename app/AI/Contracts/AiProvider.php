<?php

namespace App\AI\Contracts;

interface AiProvider
{
    /**
     * The provider key, e.g. "openai", "anthropic", "gemini".
     */
    public function key(): string;

    /**
     * Whether this provider can accept image input.
     */
    public function supportsVision(): bool;

    /**
     * Run a single-turn completion and return the response text.
     *
     * @param string $system System / instruction prompt.
     * @param array<int, array<string, mixed>> $userParts Ordered content parts. Each part is either
     *        ['type' => 'text', 'text' => string] or
     *        ['type' => 'image', 'mime' => string, 'data' => string /* base64 *\/].
     * @param array{max_tokens?: int, temperature?: float, json?: bool, vision?: bool} $opts
     * @return string The text content of the model's reply.
     *
     * @throws \RuntimeException When the provider rejects the request (e.g. invalid key).
     */
    public function complete(string $system, array $userParts, array $opts = []): string;
}
