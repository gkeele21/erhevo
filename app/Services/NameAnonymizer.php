<?php

namespace App\Services;

class NameAnonymizer
{
    protected array $genericNames = [
        'Alex', 'Jordan', 'Taylor', 'Morgan', 'Casey', 'Riley', 'Jamie', 'Quinn',
        'Avery', 'Parker', 'Drew', 'Sage', 'Blake', 'Reese', 'Charlie', 'Finley',
    ];

    protected array $usedReplacements = [];

    public function anonymize(string $content, ?array $existingMappings = null): array
    {
        $mappings = $existingMappings ?? [];
        $anonymizedContent = $content;

        // If we have existing mappings, apply them first
        if (! empty($mappings)) {
            foreach ($mappings as $original => $replacement) {
                $anonymizedContent = $this->replaceNameInContent($anonymizedContent, $original, $replacement);
                $this->usedReplacements[] = $replacement;
            }
        }

        // Detect and anonymize any new names found
        $detectedNames = $this->detectNames($content);

        foreach ($detectedNames as $name) {
            if (! isset($mappings[$name])) {
                $replacement = $this->generateReplacement($name);
                $mappings[$name] = $replacement;
                $anonymizedContent = $this->replaceNameInContent($anonymizedContent, $name, $replacement);
            }
        }

        return [
            'content' => $anonymizedContent,
            'mappings' => $mappings,
        ];
    }

    public function applyMappings(string $content, array $mappings): string
    {
        $anonymizedContent = $content;

        foreach ($mappings as $original => $replacement) {
            $anonymizedContent = $this->replaceNameInContent($anonymizedContent, $original, $replacement);
        }

        return $anonymizedContent;
    }

    protected function detectNames(string $content): array
    {
        $names = [];

        // Strip HTML tags for name detection
        $plainText = strip_tags($content);

        // Pattern to match capitalized words that could be names
        // This is a simple heuristic - the JS compromise library provides better NLP
        preg_match_all('/\b([A-Z][a-z]{2,})\b/', $plainText, $matches);

        if (! empty($matches[1])) {
            // Filter out common non-name words
            $excludeWords = $this->getExcludeWords();

            foreach ($matches[1] as $word) {
                if (! in_array(strtolower($word), $excludeWords)) {
                    $names[] = $word;
                }
            }
        }

        return array_unique($names);
    }

    protected function generateReplacement(string $name): string
    {
        // First, try to use a generic name that hasn't been used yet
        $availableNames = array_diff($this->genericNames, $this->usedReplacements);

        if (! empty($availableNames)) {
            $replacement = $availableNames[array_rand($availableNames)];
            $this->usedReplacements[] = $replacement;

            return $replacement;
        }

        // If all generic names are used, create initial-based replacement
        $initial = strtoupper(substr($name, 0, 1));

        return $initial.'.';
    }

    protected function replaceNameInContent(string $content, string $original, string $replacement): string
    {
        // Use word boundaries to avoid partial replacements
        return preg_replace('/\b'.preg_quote($original, '/').'\b/', $replacement, $content);
    }

    protected function getExcludeWords(): array
    {
        return [
            // Common words that start with capital letters but aren't names
            'the', 'this', 'that', 'these', 'those', 'there', 'their', 'they',
            'when', 'where', 'what', 'which', 'who', 'whom', 'whose', 'why', 'how',
            'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
            'january', 'february', 'march', 'april', 'may', 'june', 'july',
            'august', 'september', 'october', 'november', 'december',
            'after', 'before', 'during', 'since', 'until', 'while',
            'about', 'above', 'across', 'against', 'along', 'among', 'around',
            'because', 'been', 'being', 'between', 'both', 'but', 'can',
            'could', 'did', 'does', 'doing', 'done', 'down', 'each', 'even',
            'every', 'few', 'for', 'from', 'had', 'has', 'have', 'having',
            'here', 'hers', 'him', 'his', 'into', 'its', 'just', 'like',
            'made', 'make', 'many', 'more', 'most', 'much', 'must', 'nor',
            'not', 'now', 'off', 'once', 'one', 'only', 'other', 'our',
            'out', 'over', 'own', 'same', 'she', 'should', 'some', 'such',
            'than', 'then', 'through', 'too', 'under', 'very', 'was', 'way',
            'well', 'were', 'will', 'with', 'would', 'you', 'your',
            'also', 'always', 'another', 'any', 'back', 'first', 'get', 'got',
            'great', 'just', 'know', 'last', 'look', 'never', 'new', 'next',
            'old', 'still', 'take', 'think', 'time', 'want', 'work', 'year',
            // Common sentence starters
            'however', 'therefore', 'although', 'meanwhile', 'furthermore',
            'nevertheless', 'moreover', 'consequently', 'otherwise', 'finally',
            // Story-related words
            'chapter', 'story', 'book', 'page', 'part', 'section',
        ];
    }

    public function mergeNewMappings(array $existingMappings, array $newNames): array
    {
        $this->usedReplacements = array_values($existingMappings);

        $mergedMappings = $existingMappings;

        foreach ($newNames as $name) {
            if (! isset($mergedMappings[$name])) {
                $mergedMappings[$name] = $this->generateReplacement($name);
            }
        }

        return $mergedMappings;
    }
}
