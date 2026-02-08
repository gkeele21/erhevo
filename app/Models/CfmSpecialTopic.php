<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class CfmSpecialTopic extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (CfmSpecialTopic $topic) {
            if (empty($topic->slug)) {
                $topic->slug = Str::slug($topic->name);
            }
        });
    }

    public function weeks(): BelongsToMany
    {
        return $this->belongsToMany(
            CfmWeek::class,
            'cfm_week_topics',
            'special_topic_id',
            'cfm_week_id'
        )->withTimestamps();
    }
}
