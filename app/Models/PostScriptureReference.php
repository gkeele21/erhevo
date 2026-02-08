<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostScriptureReference extends Model
{
    protected $fillable = [
        'post_id',
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

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
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
