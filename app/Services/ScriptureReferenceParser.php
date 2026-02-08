<?php

namespace App\Services;

use App\Models\ScriptureBook;
use App\Models\ScriptureChapter;

class ScriptureReferenceParser
{
    /**
     * Parse a scripture reference string into structured data.
     *
     * Supported formats:
     * - "1 Nephi 3:7" → single verse
     * - "1 Nephi 3:7-12" → verse range in single chapter
     * - "1 Nephi 3" → whole chapter
     * - "1 Nephi 3-4" → chapter range
     * - "1 Nephi 3:25-4:5" → cross-chapter verse range
     *
     * @return array{book_slug: string, start_chapter: int, start_verse: ?int, end_chapter: ?int, end_verse: ?int}|null
     */
    public function parse(string $reference): ?array
    {
        $reference = trim($reference);

        // Pattern for various scripture reference formats
        // Book name can be like: "1 Nephi", "Alma", "D&C", "Doctrine and Covenants", etc.
        $pattern = '/^(.+?)\s+(\d+)(?::(\d+))?(?:-(\d+)(?::(\d+))?)?$/';

        if (!preg_match($pattern, $reference, $matches)) {
            return null;
        }

        $bookName = trim($matches[1]);
        $startChapter = (int) $matches[2];
        $startVerse = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : null;
        $endPart1 = isset($matches[4]) && $matches[4] !== '' ? (int) $matches[4] : null;
        $endPart2 = isset($matches[5]) && $matches[5] !== '' ? (int) $matches[5] : null;

        // Determine end chapter and verse based on what was provided
        $endChapter = null;
        $endVerse = null;

        if ($endPart1 !== null) {
            if ($endPart2 !== null) {
                // Format: "1 Nephi 3:25-4:5" → cross-chapter range
                $endChapter = $endPart1;
                $endVerse = $endPart2;
            } elseif ($startVerse !== null) {
                // Format: "1 Nephi 3:7-12" → verse range in same chapter
                $endVerse = $endPart1;
            } else {
                // Format: "1 Nephi 3-4" → chapter range
                $endChapter = $endPart1;
            }
        }

        // Find book by name (try exact match, then partial)
        $book = $this->findBook($bookName);

        return [
            'book_name' => $bookName,
            'book_slug' => $book?->slug,
            'book_id' => $book?->id,
            'start_chapter' => $startChapter,
            'start_verse' => $startVerse,
            'end_chapter' => $endChapter,
            'end_verse' => $endVerse,
        ];
    }

    /**
     * Parse a reference and resolve to chapter IDs.
     *
     * @return array{start_chapter_id: int, start_verse: ?int, end_chapter_id: ?int, end_verse: ?int}|null
     */
    public function parseToChapterIds(string $reference): ?array
    {
        $parsed = $this->parse($reference);

        if (!$parsed || !$parsed['book_id']) {
            return null;
        }

        $startChapter = ScriptureChapter::where('book_id', $parsed['book_id'])
            ->where('chapter_number', $parsed['start_chapter'])
            ->first();

        if (!$startChapter) {
            return null;
        }

        $endChapterId = null;
        if ($parsed['end_chapter']) {
            $endChapter = ScriptureChapter::where('book_id', $parsed['book_id'])
                ->where('chapter_number', $parsed['end_chapter'])
                ->first();
            $endChapterId = $endChapter?->id;
        }

        return [
            'start_chapter_id' => $startChapter->id,
            'start_verse' => $parsed['start_verse'],
            'end_chapter_id' => $endChapterId,
            'end_verse' => $parsed['end_verse'],
        ];
    }

    /**
     * Find a book by name, abbreviation, or slug.
     */
    protected function findBook(string $name): ?ScriptureBook
    {
        $name = trim($name);
        $slug = \Illuminate\Support\Str::slug($name);

        return ScriptureBook::where('slug', $slug)
            ->orWhere('name', $name)
            ->orWhere('abbreviation', $name)
            ->orWhere('abbreviation', $name . '.')
            ->first();
    }

    /**
     * Format a scripture reference for display.
     */
    public function format(
        ScriptureChapter $startChapter,
        ?int $startVerse,
        ?ScriptureChapter $endChapter,
        ?int $endVerse
    ): string {
        $book = $startChapter->book;
        $result = $book->name . ' ' . $startChapter->chapter_number;

        if ($startVerse) {
            $result .= ':' . $startVerse;
        }

        if ($endChapter && $endChapter->id !== $startChapter->id) {
            // Cross-chapter reference
            if ($endVerse) {
                $result .= '-' . $endChapter->chapter_number . ':' . $endVerse;
            } else {
                $result .= '-' . $endChapter->chapter_number;
            }
        } elseif ($endVerse && $endVerse !== $startVerse) {
            // Same chapter verse range
            $result .= '-' . $endVerse;
        }

        return $result;
    }
}
