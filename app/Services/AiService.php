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
     * @param string $mimeType The MIME type of the image (e.g., 'image/jpeg', 'image/png')
     * @return array{text: string, confidence: string}
     */
    public function extractTextFromImage(string $imageData, bool $isUrl = false, string $mimeType = 'image/jpeg'): array
    {
        $imageContent = $isUrl
            ? ['type' => 'image_url', 'image_url' => ['url' => $imageData]]
            : ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$imageData}"]];

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an OCR assistant for a personal journaling application. Your task is to transcribe text from photos of journal entries, letters, notes, and documents. Transcribe all visible text exactly as written, preserving paragraph breaks and formatting. For handwritten text, interpret the writing as accurately as possible. Return only the transcribed text without commentary.',
                ],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Please transcribe the text from this journal entry or document image. Preserve the original formatting and paragraph structure.'],
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

    /**
     * Suggest relevant scripture references for content.
     *
     * @param string $content The content to analyze
     * @param int $maxSuggestions Maximum number of suggestions
     * @param bool $includeLdsScriptures Whether to include LDS scriptures (Book of Mormon, D&C, Pearl of Great Price)
     * @return array<array{reference: string, reason: string}>
     */
    public function suggestScriptures(string $content, int $maxSuggestions = 5, bool $includeLdsScriptures = true): array
    {
        $scriptureSource = $includeLdsScriptures
            ? "the Standard Works (Bible, Book of Mormon, Doctrine and Covenants, Pearl of Great Price)"
            : "the Bible (Old Testament and New Testament only)";

        $formatExample = $includeLdsScriptures
            ? "'1 Nephi 3:7' or 'D&C 121:7-8' or 'John 3:16'"
            : "'John 3:16' or 'Psalm 23:1' or 'Romans 8:28'";

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a helpful assistant that suggests relevant scripture references from {$scriptureSource} for personal journal entries or faith-related content. Return a JSON array of up to {$maxSuggestions} objects, each with 'reference' (formatted like {$formatExample}) and 'reason' (brief explanation of relevance, 10-15 words). Only suggest scriptures that genuinely connect to the content's themes.",
                ],
                [
                    'role' => 'user',
                    'content' => "Suggest relevant scripture references for this content:\n\n{$content}",
                ],
            ],
            'max_tokens' => 500,
        ]);

        $result = $response->choices[0]->message->content ?? '[]';

        try {
            $suggestions = json_decode($result, true);
            return is_array($suggestions) ? array_slice($suggestions, 0, $maxSuggestions) : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate personalized writing prompts.
     *
     * @param array $recentPosts Recent post summaries for context
     * @param array $userTags Common tags used by the user
     * @param string|null $currentContext Optional current context or partial content
     * @return array<array{prompt: string, theme: string}>
     */
    public function generateWritingPrompts(array $recentPosts = [], array $userTags = [], ?string $currentContext = null): array
    {
        $context = '';
        if (!empty($recentPosts)) {
            $context .= "User's recent journal themes: " . implode(', ', array_slice($recentPosts, 0, 5)) . ".\n";
        }
        if (!empty($userTags)) {
            $context .= "User commonly writes about: " . implode(', ', array_slice($userTags, 0, 10)) . ".\n";
        }
        if ($currentContext) {
            $context .= "Current writing context: {$currentContext}\n";
        }

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a thoughtful writing prompt generator for a faith-focused journaling app. Generate personalized, reflective prompts that encourage spiritual growth and self-reflection. Return a JSON array of 5 objects, each with 'prompt' (the writing prompt, 15-25 words) and 'theme' (single word or short phrase like 'gratitude', 'faith', 'family').",
                ],
                [
                    'role' => 'user',
                    'content' => $context ?: "Generate 5 inspiring writing prompts for spiritual journaling.",
                ],
            ],
            'max_tokens' => 400,
        ]);

        $result = $response->choices[0]->message->content ?? '[]';

        try {
            $prompts = json_decode($result, true);
            return is_array($prompts) ? array_slice($prompts, 0, 5) : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Analyze content insights across multiple posts.
     *
     * @param array $posts Array of post content/summaries
     * @return array{themes: array, emotions: array, growth: string, recommendations: array}
     */
    public function analyzeContentInsights(array $posts): array
    {
        if (count($posts) < 3) {
            return [
                'themes' => [],
                'emotions' => [],
                'growth' => '',
                'recommendations' => [],
            ];
        }

        $postsText = implode("\n---\n", array_slice($posts, 0, 20));

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are an insightful journal analyst. Analyze these journal entries and return a JSON object with: 'themes' (array of 3-5 recurring themes), 'emotions' (array of 2-4 predominant emotional tones), 'growth' (one sentence observing spiritual or personal growth), 'recommendations' (array of 2-3 suggestions for future reflection topics).",
                ],
                [
                    'role' => 'user',
                    'content' => "Analyze these journal entries:\n\n{$postsText}",
                ],
            ],
            'max_tokens' => 400,
        ]);

        $result = $response->choices[0]->message->content ?? '{}';

        try {
            $insights = json_decode($result, true);
            return is_array($insights) ? $insights : [
                'themes' => [],
                'emotions' => [],
                'growth' => '',
                'recommendations' => [],
            ];
        } catch (\Exception $e) {
            return [
                'themes' => [],
                'emotions' => [],
                'growth' => '',
                'recommendations' => [],
            ];
        }
    }

    /**
     * Analyze content for privacy sensitivity.
     *
     * @param string $content The content to analyze
     * @return array{sensitivity: string, reasons: array, suggested_visibility: string, names_detected: array, recommendation: string}
     */
    public function analyzeContentSensitivity(string $content): array
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'temperature' => 0.3, // Lower temperature for consistency
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a privacy advisor for a journaling app. Analyze content for privacy sensitivity. Return a JSON object with: 'sensitivity' ('low', 'medium', or 'high'), 'reasons' (array of specific concerns found), 'suggested_visibility' ('public', 'friends', or 'private'), 'names_detected' (array of any personal names found), 'recommendation' (one sentence privacy advice).",
                ],
                [
                    'role' => 'user',
                    'content' => "Analyze this content for privacy concerns:\n\n{$content}",
                ],
            ],
            'max_tokens' => 300,
        ]);

        $result = $response->choices[0]->message->content ?? '{}';

        try {
            $analysis = json_decode($result, true);
            return is_array($analysis) ? $analysis : [
                'sensitivity' => 'low',
                'reasons' => [],
                'suggested_visibility' => 'public',
                'names_detected' => [],
                'recommendation' => '',
            ];
        } catch (\Exception $e) {
            return [
                'sensitivity' => 'low',
                'reasons' => [],
                'suggested_visibility' => 'public',
                'names_detected' => [],
                'recommendation' => '',
            ];
        }
    }

    /**
     * Suggest categories for content.
     *
     * @param string $content The content to analyze
     * @param array $existingCategories List of existing category names
     * @param string $type 'user' for personal categories, 'public' for public categories
     * @return array{name: string, reason: string, is_existing: bool}
     */
    public function suggestCategory(string $content, array $existingCategories = [], string $type = 'user'): array
    {
        $categoryContext = !empty($existingCategories)
            ? "Existing categories: " . implode(', ', $existingCategories) . "."
            : "No existing categories.";

        $typeContext = $type === 'user'
            ? "Suggest a personal organization category for this journal entry. Categories should help the user organize their personal writings (e.g., 'Family Stories', 'Spiritual Experiences', 'Travel Memories', 'Life Lessons')."
            : "Suggest a public category for this post that would help others discover it. Categories should be broad topics (e.g., 'Faith', 'Family', 'Gratitude', 'Service', 'Personal Growth').";

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a helpful assistant that suggests categories for journal entries. {$typeContext}\n\n{$categoryContext}\n\nReturn a JSON object with: 'name' (the suggested category name, prefer existing categories if they fit well), 'reason' (brief explanation of why this category fits, 10-15 words), 'is_existing' (boolean, true if suggesting an existing category).",
                ],
                [
                    'role' => 'user',
                    'content' => "Suggest a category for this content:\n\n{$content}",
                ],
            ],
            'max_tokens' => 150,
        ]);

        $result = $response->choices[0]->message->content ?? '{}';

        try {
            $suggestion = json_decode($result, true);
            return is_array($suggestion) ? $suggestion : [
                'name' => '',
                'reason' => '',
                'is_existing' => false,
            ];
        } catch (\Exception $e) {
            return [
                'name' => '',
                'reason' => '',
                'is_existing' => false,
            ];
        }
    }
}
