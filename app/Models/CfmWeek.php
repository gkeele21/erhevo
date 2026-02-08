<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class CfmWeek extends Model
{
    protected $fillable = [
        'study_year_id',
        'week_number',
        'start_date',
        'end_date',
        'title',
        'slug',
        'is_special_topic',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_special_topic' => 'boolean',
    ];

    public function studyYear(): BelongsTo
    {
        return $this->belongsTo(CfmStudyYear::class, 'study_year_id');
    }

    public function chapters(): BelongsToMany
    {
        return $this->belongsToMany(
            ScriptureChapter::class,
            'cfm_week_chapters',
            'cfm_week_id',
            'chapter_id'
        )->withTimestamps();
    }

    public function specialTopics(): BelongsToMany
    {
        return $this->belongsToMany(
            CfmSpecialTopic::class,
            'cfm_week_topics',
            'cfm_week_id',
            'special_topic_id'
        )->withTimestamps();
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(
            Post::class,
            'post_cfm_weeks',
            'cfm_week_id',
            'post_id'
        )->withTimestamps();
    }

    public function publisherContent(): HasMany
    {
        return $this->hasMany(CfmPublisherContent::class, 'cfm_week_id');
    }

    /**
     * Scope to get the current week based on today's date
     */
    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Get equivalent weeks from other years that cover the same chapters
     */
    public function getEquivalentWeeks(): Collection
    {
        if ($this->is_special_topic) {
            $topicIds = $this->specialTopics()->pluck('cfm_special_topics.id');

            return CfmWeek::whereHas('specialTopics', function ($q) use ($topicIds) {
                $q->whereIn('cfm_special_topics.id', $topicIds);
            })->where('id', '!=', $this->id)->get();
        }

        $chapterIds = $this->chapters()->pluck('scripture_chapters.id');

        return CfmWeek::whereHas('chapters', function ($q) use ($chapterIds) {
            $q->whereIn('scripture_chapters.id', $chapterIds);
        })->where('id', '!=', $this->id)->get();
    }

    /**
     * Check if this week is currently active
     */
    public function isActive(): bool
    {
        return now()->between($this->start_date, $this->end_date);
    }
}
