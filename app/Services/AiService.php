<?php

namespace App\Services;

use App\AI\Contracts\AiProvider;

class AiService
{
    public function __construct(
        protected AiProvider $provider,
    ) {}

    /**
     * Extract text from an image using the provider's vision model.
     *
     * @param string $imageData Base64 encoded image data
     * @param bool $isUrl Unused; retained for backwards compatibility (only base64 is supported)
     * @param string $mimeType The MIME type of the image (e.g., 'image/jpeg', 'image/png')
     * @return array{text: string, usage: array{prompt_tokens: int, completion_tokens: int, total_tokens: int}}
     */
    public function extractTextFromImage(string $imageData, bool $isUrl = false, string $mimeType = 'image/jpeg'): array
    {
        $text = $this->provider->complete(
            'You are an OCR assistant for a personal journaling application. Your task is to transcribe text from photos of journal entries, letters, notes, and documents. Transcribe all visible text exactly as written, preserving paragraph breaks and formatting. For handwritten text, interpret the writing as accurately as possible. Return only the transcribed text without commentary.',
            [
                ['type' => 'text', 'text' => 'Please transcribe the text from this journal entry or document image. Preserve the original formatting and paragraph structure.'],
                ['type' => 'image', 'mime' => $mimeType, 'data' => $imageData],
            ],
            ['max_tokens' => 4096, 'vision' => true],
        );

        return [
            'text' => trim($text),
            'usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
        ];
    }

    /**
     * Generate a summary/excerpt from content.
     */
    public function generateExcerpt(string $content, int $maxLength = 200): string
    {
        return trim($this->provider->complete(
            "You are a helpful assistant that creates brief, engaging excerpts. Create a summary that captures the essence of the content in {$maxLength} characters or less. Do not use quotes around the excerpt.",
            [['type' => 'text', 'text' => "Create a brief excerpt for this content:\n\n{$content}"]],
            ['max_tokens' => 150],
        ));
    }

    /**
     * Suggest tags for content.
     *
     * @return array<string>
     */
    public function suggestTags(string $content, int $maxTags = 5): array
    {
        $result = $this->provider->complete(
            "You are a helpful assistant that suggests relevant tags for content. Return only a JSON array of {$maxTags} or fewer single-word or short-phrase tags. Tags should be lowercase. Example: [\"gratitude\", \"family\", \"personal growth\"]",
            [['type' => 'text', 'text' => "Suggest tags for this content:\n\n{$content}"]],
            ['max_tokens' => 100, 'json' => true],
        );

        $tags = $this->decodeJson($result);

        return is_array($tags) ? array_slice($tags, 0, $maxTags) : [];
    }

    /**
     * Suggest relevant scripture references for content.
     *
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

        $result = $this->provider->complete(
            "You are a helpful assistant that suggests relevant scripture references from {$scriptureSource} for personal journal entries or faith-related content. Return a JSON array of up to {$maxSuggestions} objects, each with 'reference' (formatted like {$formatExample}) and 'reason' (brief explanation of relevance, 10-15 words). Only suggest scriptures that genuinely connect to the content's themes.",
            [['type' => 'text', 'text' => "Suggest relevant scripture references for this content:\n\n{$content}"]],
            ['max_tokens' => 500, 'json' => true],
        );

        $suggestions = $this->decodeJson($result);

        return is_array($suggestions) ? array_slice($suggestions, 0, $maxSuggestions) : [];
    }

    /**
     * Generate personalized writing prompts.
     *
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

        $result = $this->provider->complete(
            "You are a thoughtful writing prompt generator for a faith-focused journaling app. Generate personalized, reflective prompts that encourage spiritual growth and self-reflection. Return a JSON array of 5 objects, each with 'prompt' (the writing prompt, 15-25 words) and 'theme' (single word or short phrase like 'gratitude', 'faith', 'family').",
            [['type' => 'text', 'text' => $context ?: "Generate 5 inspiring writing prompts for spiritual journaling."]],
            ['max_tokens' => 400, 'json' => true],
        );

        $prompts = $this->decodeJson($result);

        return is_array($prompts) ? array_slice($prompts, 0, 5) : [];
    }

    /**
     * Analyze content insights across multiple posts.
     *
     * @return array{themes: array, emotions: array, growth: string, recommendations: array}
     */
    public function analyzeContentInsights(array $posts): array
    {
        $empty = [
            'themes' => [],
            'emotions' => [],
            'growth' => '',
            'recommendations' => [],
        ];

        if (count($posts) < 3) {
            return $empty;
        }

        $postsText = implode("\n---\n", array_slice($posts, 0, 20));

        $result = $this->provider->complete(
            "You are an insightful journal analyst. Analyze these journal entries and return a JSON object with: 'themes' (array of 3-5 recurring themes), 'emotions' (array of 2-4 predominant emotional tones), 'growth' (one sentence observing spiritual or personal growth), 'recommendations' (array of 2-3 suggestions for future reflection topics).",
            [['type' => 'text', 'text' => "Analyze these journal entries:\n\n{$postsText}"]],
            ['max_tokens' => 400, 'json' => true],
        );

        $insights = $this->decodeJson($result);

        return is_array($insights) ? $insights : $empty;
    }

    /**
     * Analyze content for privacy sensitivity.
     *
     * @return array{sensitivity: string, reasons: array, suggested_visibility: string, names_detected: array, recommendation: string}
     */
    public function analyzeContentSensitivity(string $content): array
    {
        $default = [
            'sensitivity' => 'low',
            'reasons' => [],
            'suggested_visibility' => 'public',
            'names_detected' => [],
            'recommendation' => '',
        ];

        $result = $this->provider->complete(
            "You are a privacy advisor for a journaling app. Analyze content for privacy sensitivity. Return a JSON object with: 'sensitivity' ('low', 'medium', or 'high'), 'reasons' (array of specific concerns found), 'suggested_visibility' ('public', 'friends', or 'private'), 'names_detected' (array of any personal names found), 'recommendation' (one sentence privacy advice).",
            [['type' => 'text', 'text' => "Analyze this content for privacy concerns:\n\n{$content}"]],
            ['max_tokens' => 300, 'temperature' => 0.3, 'json' => true],
        );

        $analysis = $this->decodeJson($result);

        return is_array($analysis) ? $analysis : $default;
    }

    /**
     * Suggest titles for content.
     *
     * @return array<string>
     */
    public function suggestTitles(string $content, int $maxSuggestions = 3): array
    {
        $result = $this->provider->complete(
            "You are a helpful assistant that suggests titles for journal entries and personal stories. Suggest {$maxSuggestions} distinct titles that capture the heart of the content. Titles should be short (3-8 words), warm, and specific to the content rather than generic. Return only a JSON array of strings. Example: [\"A Lesson at the Kitchen Table\", \"When Grandpa Taught Me Patience\"]",
            [['type' => 'text', 'text' => "Suggest titles for this content:\n\n{$content}"]],
            ['max_tokens' => 200, 'json' => true],
        );

        $titles = $this->decodeJson($result);

        if (! is_array($titles)) {
            return [];
        }

        return array_values(array_filter(
            array_slice($titles, 0, $maxSuggestions),
            fn ($title) => is_string($title) && trim($title) !== ''
        ));
    }

    /**
     * Suggest categories for content: the best-fitting existing category (if
     * any) plus a proposed new category, so the user can choose between them.
     *
     * @return array{existing: ?array{name: string, reason: string}, new: ?array{name: string, reason: string}, recommended: string}
     */
    public function suggestCategory(string $content, array $existingCategories = [], string $type = 'user'): array
    {
        $categoryContext = !empty($existingCategories)
            ? "Existing categories: " . implode(', ', $existingCategories) . "."
            : "No existing categories.";

        $typeContext = $type === 'user'
            ? "Suggest a personal organization category for this journal entry. Categories should help the user organize their personal writings (e.g., 'Family Stories', 'Spiritual Experiences', 'Travel Memories', 'Life Lessons')."
            : "Suggest a public category for this post that would help others discover it. Categories should be broad topics (e.g., 'Faith', 'Family', 'Gratitude', 'Service', 'Personal Growth').";

        $result = $this->provider->complete(
            "You are a helpful assistant that suggests categories for journal entries. {$typeContext}\n\n{$categoryContext}\n\n"
                . "Give two suggestions:\n"
                . "1. 'existing': the existing category that best fits the content, copied exactly from the list. Use null if there are no existing categories or none is a reasonable fit.\n"
                . "2. 'new': a new category that does NOT duplicate or closely overlap any existing category, and that would describe this content more precisely than the existing options. Use null only if an existing category is already a precise fit and a new one would add nothing.\n\n"
                . "Also set 'recommended' to 'existing' or 'new' for whichever you think is the better choice.\n\n"
                . "Return a JSON object: {\"existing\": {\"name\": string, \"reason\": string} | null, \"new\": {\"name\": string, \"reason\": string} | null, \"recommended\": \"existing\" | \"new\"}. Each reason is a brief explanation of why the category fits, 10-15 words.",
            [['type' => 'text', 'text' => "Suggest categories for this content:\n\n{$content}"]],
            ['max_tokens' => 300, 'json' => true],
        );

        return $this->normalizeCategorySuggestion($this->decodeJson($result), $existingCategories);
    }

    /**
     * Validate the model's category suggestions against the real category list:
     * an "existing" pick must actually exist, and a "new" pick that matches an
     * existing name is treated as that existing category instead.
     */
    protected function normalizeCategorySuggestion(mixed $raw, array $existingCategories): array
    {
        $byLowerName = [];
        foreach ($existingCategories as $name) {
            $byLowerName[mb_strtolower(trim($name))] = $name;
        }

        $clean = function ($item) {
            if (! is_array($item) || ! is_string($item['name'] ?? null) || trim($item['name']) === '') {
                return null;
            }

            return [
                'name' => trim($item['name']),
                'reason' => is_string($item['reason'] ?? null) ? trim($item['reason']) : '',
            ];
        };

        $existing = $clean(is_array($raw) ? ($raw['existing'] ?? null) : null);
        $new = $clean(is_array($raw) ? ($raw['new'] ?? null) : null);

        if ($existing) {
            $match = $byLowerName[mb_strtolower($existing['name'])] ?? null;
            $existing = $match ? ['name' => $match, 'reason' => $existing['reason']] : null;
        }

        if ($new && ($match = $byLowerName[mb_strtolower($new['name'])] ?? null)) {
            $existing ??= ['name' => $match, 'reason' => $new['reason']];
            $new = null;
        }

        $recommended = is_array($raw) && ($raw['recommended'] ?? null) === 'new' ? 'new' : 'existing';
        if (! $existing) {
            $recommended = 'new';
        } elseif (! $new) {
            $recommended = 'existing';
        }

        return [
            'existing' => $existing,
            'new' => $new,
            'recommended' => $recommended,
        ];
    }

    /**
     * Decode a JSON response, tolerating markdown code fences that some
     * providers wrap around JSON output.
     */
    protected function decodeJson(string $raw): mixed
    {
        $trimmed = trim($raw);

        // Strip ```json ... ``` or ``` ... ``` fences if present.
        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```[a-zA-Z]*\s*/', '', $trimmed);
            $trimmed = preg_replace('/\s*```$/', '', $trimmed);
        }

        return json_decode($trimmed, true);
    }
}
