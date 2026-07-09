<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Talk extends Model
{
    protected $fillable = [
        'source_id',
        'talk_type_id',
        'general_conference_session_id',
        'speaker_name',
        'author_id',
        'speaker_title',
        'church_calling_id',
        'title',
        'slug',
        'summary',
        'talk_date',
        'url',
        'display_order',
    ];

    protected $casts = [
        'talk_date' => 'date',
        'display_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($talk) {
            if (empty($talk->slug)) {
                $talk->slug = static::generateUniqueSlug($talk->title, $talk->source_id);
            }
        });
    }

    protected static function generateUniqueSlug(string $title, int $sourceId): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->where('source_id', $sourceId)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /** The Author entity for this talk's speaker, when linked. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function talkType(): BelongsTo
    {
        return $this->belongsTo(TalkType::class);
    }

    public function conferenceSession(): BelongsTo
    {
        return $this->belongsTo(GeneralConferenceSession::class, 'general_conference_session_id');
    }

    public function calling(): BelongsTo
    {
        return $this->belongsTo(ChurchCalling::class, 'church_calling_id');
    }

    /**
     * Get the speaker's full display name with title
     */
    public function getSpeakerDisplayNameAttribute(): string
    {
        // If they have a church calling prefix, use that
        if ($this->calling && $this->calling->prefix) {
            return "{$this->calling->prefix} {$this->speaker_name}";
        }

        // Otherwise use their speaker title if available
        if ($this->speaker_title) {
            return "{$this->speaker_name}, {$this->speaker_title}";
        }

        return $this->speaker_name;
    }

    /**
     * Get the conference through the session (if this is a GC talk)
     */
    public function getConferenceAttribute(): ?GeneralConference
    {
        return $this->conferenceSession?->conference;
    }

    /**
     * Get the year of the talk
     */
    public function getYearAttribute(): ?int
    {
        return $this->talk_date?->year;
    }

    /**
     * Check if this is a General Conference talk
     */
    public function isGeneralConferenceTalk(): bool
    {
        return $this->general_conference_session_id !== null;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('talk_date', 'desc')->orderBy('display_order');
    }

    public function scopeBySpeaker($query, string $name)
    {
        return $query->where('speaker_name', 'like', "%{$name}%");
    }

    public function scopeByYear($query, int $year)
    {
        return $query->whereYear('talk_date', $year);
    }

    public function scopeBySource($query, string $sourceSlug)
    {
        return $query->whereHas('source', function ($q) use ($sourceSlug) {
            $q->where('slug', $sourceSlug);
        });
    }

    public function scopeGeneralConference($query)
    {
        return $query->whereNotNull('general_conference_session_id');
    }
}
