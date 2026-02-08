<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CfmStudyYear extends Model
{
    protected $fillable = [
        'year',
        'title',
        'description',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public function volumes(): BelongsToMany
    {
        return $this->belongsToMany(
            ScriptureVolume::class,
            'cfm_study_year_volumes',
            'study_year_id',
            'volume_id'
        )->withTimestamps();
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(CfmWeek::class, 'study_year_id')->orderBy('week_number');
    }

    /**
     * Scope to get the current study year
     */
    public function scopeCurrent($query)
    {
        return $query->where('year', now()->year);
    }
}
