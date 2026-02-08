<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScriptureVolume extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'abbreviation',
        'sort_order',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(ScriptureBook::class, 'volume_id')->orderBy('sort_order');
    }

    public function studyYears(): BelongsToMany
    {
        return $this->belongsToMany(
            CfmStudyYear::class,
            'cfm_study_year_volumes',
            'volume_id',
            'study_year_id'
        )->withTimestamps();
    }
}
