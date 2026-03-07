<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class AiService
{
    /**
     * Extract text from an image using GPT-4o vision.
     *
     * @param string $imageData Base64 encoded image data or URL
     * @param bool $isUrl Whether the image data is a URL
     * @return array{text: string, confidence: string}
     */
    public function extractTextFromImage(string $imageData, bool $isUrl = false): array
    {
        $imageContent = $isUrl
            ? ['type' => 'image_url', 'image_url' => ['url' => $imageData]]
            : ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$imageData}"]];

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that extracts text from images. Transcribe all visible text exactly as written, preserving paragraph breaks. For handwritten text, do your best to interpret the writing accurately. If parts are unclear, make your best guess but note any uncertainty. Return only the transcribed text without any additional commentary.',
                ],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Please transcribe all the text visible in this image. Preserve the original formatting and paragraph structure as much as possible.'],
                        $imageContent,
                    ],
                ],
            ],
            'max_tokens' => 4096,
        ]);

        $text = $response->choices[0]->message->content ?? '';

        return [
            'text' => trim($text),
            'usage' => [
                'prompt_tokens' => $response->usage->promptTokens ?? 0,
                'completion_tokens' => $response->usage->completionTokens ?? 0,
                'total_tokens' => $response->usage->totalTokens ?? 0,
            ],
        ];
    }

    /**
     * Generate a summary/excerpt from content.
     *
     * @param string $content The content to summarize
     * @param int $maxLength Maximum length of the summary
     * @return string
     */
    public function generateExcerpt(string $content, int $maxLength = 200): string
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a helpful assistant that creates brief, engaging excerpts. Create a summary that captures the essence of the content in {$maxLength} characters or less. Do not use quotes around the excerpt.",
                ],
                [
                    'role' => 'user',
                    'content' => "Create a brief excerpt for this content:\n\n{$content}",
                ],
            ],
            'max_tokens' => 150,
        ]);

        return trim($response->choices[0]->message->content ?? '');
    }

    /**
     * Suggest tags for content.
     *
     * @param string $content The content to analyze
     * @param int $maxTags Maximum number of tags to suggest
     * @return array<string>
     */
    public function suggestTags(string $content, int $maxTags = 5): array
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a helpful assistant that suggests relevant tags for content. Return only a JSON array of {$maxTags} or fewer single-word or short-phrase tags. Tags should be lowercase. Example: [\"gratitude\", \"family\", \"personal growth\"]",
                ],
                [
                    'role' => 'user',
                    'content' => "Suggest tags for this content:\n\n{$content}",
                ],
            ],
            'max_tokens' => 100,
        ]);

        $result = $response->choices[0]->message->content ?? '[]';

        try {
            $tags = json_decode($result, true);
            return is_array($tags) ? array_slice($tags, 0, $maxTags) : [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
