<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'platform',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function talks(): HasMany
    {
        return $this->hasMany(Talk::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Generate a URL for a talk based on this source's base URL
     */
    public function generateTalkUrl(string $slug, array $params = []): ?string
    {
        if (!$this->base_url) {
            return null;
        }

        // Handle different source URL patterns
        return match ($this->slug) {
            'general-conference' => $this->generateGcUrl($slug, $params),
            'byu-speeches' => "{$this->base_url}/talks/{$slug}/",
            'ensign', 'liahona' => $this->generateMagazineUrl($slug, $params),
            default => "{$this->base_url}/{$slug}",
        };
    }

    private function generateGcUrl(string $slug, array $params): string
    {
        $year = $params['year'] ?? date('Y');
        $month = $params['month'] ?? 'april';
        $monthNum = $month === 'april' ? '04' : '10';

        return "{$this->base_url}/{$year}/{$monthNum}/{$slug}?lang=eng";
    }

    private function generateMagazineUrl(string $slug, array $params): string
    {
        $year = $params['year'] ?? date('Y');
        $month = $params['month'] ?? '01';

        return "{$this->base_url}/{$year}/{$month}/{$slug}?lang=eng";
    }
}
