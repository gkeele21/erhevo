<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class GeneralConference extends Model
{
    protected $fillable = [
        'name',
        'year',
        'month',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(GeneralConferenceSession::class)->orderBy('display_order');
    }

    public function talks(): HasManyThrough
    {
        return $this->hasManyThrough(
            Talk::class,
            GeneralConferenceSession::class,
            'general_conference_id',
            'general_conference_session_id'
        );
    }

    /**
     * Get conferences for a specific year
     */
    public function scopeYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Get the April conference for a year
     */
    public function scopeApril($query)
    {
        return $query->where('month', 'april');
    }

    /**
     * Get the October conference for a year
     */
    public function scopeOctober($query)
    {
        return $query->where('month', 'october');
    }

    /**
     * Get conferences ordered by date descending
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('year', 'desc')->orderByRaw("FIELD(month, 'october', 'april')");
    }

    /**
     * Get the URL slug for this conference
     */
    public function getSlugAttribute(): string
    {
        return strtolower("{$this->year}/{$this->month}");
    }
}
