<?php

namespace App\Models;

use App\Enums\CfmContentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmPublisherContent extends Model
{
    protected $table = 'cfm_publisher_content';

    protected $fillable = [
        'publisher_id',
        'cfm_week_id',
        'title',
        'content_type',
        'external_url',
        'description',
        'thumbnail_url',
        'duration_seconds',
        'is_featured',
    ];

    protected $casts = [
        'content_type' => CfmContentType::class,
        'duration_seconds' => 'integer',
        'is_featured' => 'boolean',
    ];

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(CfmPublisher::class, 'publisher_id');
    }

    public function cfmWeek(): BelongsTo
    {
        return $this->belongsTo(CfmWeek::class, 'cfm_week_id');
    }

    /**
     * Get formatted duration (e.g., "1:23:45" or "23:45")
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration_seconds) {
            return null;
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Scope to only featured content
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
