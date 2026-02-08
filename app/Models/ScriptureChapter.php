<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScriptureChapter extends Model
{
    protected $fillable = [
        'book_id',
        'chapter_number',
        'verse_count',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(ScriptureBook::class, 'book_id');
    }

    public function verses(): HasMany
    {
        return $this->hasMany(ScriptureVerse::class, 'chapter_id')->orderBy('verse_number');
    }

    public function cfmWeeks(): BelongsToMany
    {
        return $this->belongsToMany(
            CfmWeek::class,
            'cfm_week_chapters',
            'chapter_id',
            'cfm_week_id'
        )->withTimestamps();
    }

    /**
     * Get the full reference string for this chapter (e.g., "1 Nephi 3")
     */
    public function getFullReferenceAttribute(): string
    {
        return "{$this->book->name} {$this->chapter_number}";
    }

    /**
     * Get the abbreviated reference string (e.g., "1 Ne. 3")
     */
    public function getShortReferenceAttribute(): string
    {
        return "{$this->book->abbreviation} {$this->chapter_number}";
    }
}
