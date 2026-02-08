<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScriptureVerse extends Model
{
    protected $fillable = [
        'chapter_id',
        'verse_number',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(ScriptureChapter::class, 'chapter_id');
    }

    /**
     * Get the full reference string for this verse (e.g., "1 Nephi 3:7")
     */
    public function getFullReferenceAttribute(): string
    {
        return "{$this->chapter->book->name} {$this->chapter->chapter_number}:{$this->verse_number}";
    }

    /**
     * Get the abbreviated reference string (e.g., "1 Ne. 3:7")
     */
    public function getShortReferenceAttribute(): string
    {
        return "{$this->chapter->book->abbreviation} {$this->chapter->chapter_number}:{$this->verse_number}";
    }
}
