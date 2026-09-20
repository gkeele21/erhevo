<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScriptureReference extends Model
{
    protected $fillable = [
        'referenceable_type',
        'referenceable_id',
        'start_chapter_id',
        'start_verse',
        'end_chapter_id',
        'end_verse',
        'sort_order',
    ];

    protected $casts = [
        'start_verse' => 'integer',
        'end_verse' => 'integer',
        'sort_order' => 'integer',
    ];

    /** The Post or LessonItem this reference hangs off. */
    public function referenceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function startChapter(): BelongsTo
    {
        return $this->belongsTo(ScriptureChapter::class, 'start_chapter_id');
    }

    public function endChapter(): BelongsTo
    {
        return $this->belongsTo(ScriptureChapter::class, 'end_chapter_id');
    }

    /**
     * Get the display reference string (e.g., "1 Nephi 3:7-12" or "1 Nephi 3:25-4:5")
     */
    public function getDisplayReferenceAttribute(): string
    {
        $startBook = $this->startChapter->book->name;
        $startChapterNum = $this->startChapter->chapter_number;

        // Single verse: "1 Nephi 3:7"
        if ($this->start_verse && !$this->end_chapter_id && !$this->end_verse) {
            return "{$startBook} {$startChapterNum}:{$this->start_verse}";
        }

        // Verse range in same chapter: "1 Nephi 3:7-12"
        if ($this->start_verse && !$this->end_chapter_id && $this->end_verse) {
            return "{$startBook} {$startChapterNum}:{$this->start_verse}-{$this->end_verse}";
        }

        // Whole chapter: "1 Nephi 3"
        if (!$this->start_verse && !$this->end_chapter_id) {
            return "{$startBook} {$startChapterNum}";
        }

        // Multiple whole chapters: "1 Nephi 3-4"
        if (!$this->start_verse && $this->end_chapter_id && !$this->end_verse) {
            $endChapterNum = $this->endChapter->chapter_number;
            return "{$startBook} {$startChapterNum}-{$endChapterNum}";
        }

        // Cross-chapter verse range: "1 Nephi 3:25-4:5"
        if ($this->start_verse && $this->end_chapter_id && $this->end_verse) {
            $endChapterNum = $this->endChapter->chapter_number;
            return "{$startBook} {$startChapterNum}:{$this->start_verse}-{$endChapterNum}:{$this->end_verse}";
        }

        // Fallback
        return "{$startBook} {$startChapterNum}";
    }

    /**
     * References that touch a chapter — including ranges that merely pass
     * through it, e.g. "1 Nephi 3:25-5:2" covers chapter 4 without naming it.
     */
    public function scopeCoveringChapter($query, ScriptureChapter $chapter)
    {
        return $query->where(function ($q) use ($chapter) {
            $q->where('start_chapter_id', $chapter->id)
                ->orWhere('end_chapter_id', $chapter->id)
                ->orWhere(function ($spanning) use ($chapter) {
                    $spanning
                        ->whereHas('startChapter', fn ($c) => $c
                            ->where('book_id', $chapter->book_id)
                            ->where('chapter_number', '<', $chapter->chapter_number))
                        ->whereHas('endChapter', fn ($c) => $c
                            ->where('book_id', $chapter->book_id)
                            ->where('chapter_number', '>', $chapter->chapter_number));
                });
        });
    }

    /**
     * Whether this reference includes a given verse of a given chapter.
     *
     * Assumes the reference already covers the chapter (see coveringChapter);
     * this narrows it to the verses actually named.
     */
    public function coversVerse(ScriptureChapter $chapter, int $verseNumber): bool
    {
        $isStart = $this->start_chapter_id === $chapter->id;
        $isEnd = $this->end_chapter_id === $chapter->id;

        // A range passing straight through covers the chapter end to end.
        if (! $isStart && ! $isEnd) {
            return true;
        }

        // Single-chapter reference.
        if (! $this->end_chapter_id) {
            if (! $this->start_verse) {
                return true;
            }

            return $verseNumber >= $this->start_verse
                && $verseNumber <= ($this->end_verse ?: $this->start_verse);
        }

        if ($isStart) {
            return ! $this->start_verse || $verseNumber >= $this->start_verse;
        }

        return ! $this->end_verse || $verseNumber <= $this->end_verse;
    }

    /**
     * Get all chapter IDs covered by this reference (for resurfacing queries)
     */
    public function getCoveredChapterIds(): array
    {
        if (!$this->end_chapter_id) {
            return [$this->start_chapter_id];
        }

        // Get all chapters between start and end (inclusive)
        $startChapter = $this->startChapter;
        $endChapter = $this->endChapter;

        // They must be in the same book for a valid range
        if ($startChapter->book_id !== $endChapter->book_id) {
            return [$this->start_chapter_id];
        }

        return ScriptureChapter::where('book_id', $startChapter->book_id)
            ->whereBetween('chapter_number', [
                $startChapter->chapter_number,
                $endChapter->chapter_number,
            ])
            ->pluck('id')
            ->toArray();
    }
}
