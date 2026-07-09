<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChurchCalling extends Model
{
    protected $fillable = [
        'church_organization_id',
        'name',
        'prefix',
        'gender',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'gender' => Gender::class,
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(ChurchOrganization::class, 'church_organization_id');
    }

    public function talks(): HasMany
    {
        return $this->hasMany(GeneralConferenceTalk::class);
    }

    /** Authors whose current (primary) calling this is. */
    public function authors(): HasMany
    {
        return $this->hasMany(Author::class);
    }

    /**
     * Label for pickers: "Organization — Name", without the prefix
     * (e.g. "The Quorum of the Twelve Apostles — Apostle"). Falls back to the
     * organization or prefix when a name isn't set.
     */
    public function getDisplayLabelAttribute(): string
    {
        $parts = array_filter([$this->organization?->name, $this->name ?: null]);

        return $parts ? implode(' — ', $parts) : ($this->prefix ?: 'Calling');
    }

    /**
     * Get the full title (prefix + name)
     */
    public function getFullTitleAttribute(): string
    {
        if ($this->prefix && $this->name) {
            return "{$this->prefix} ({$this->name})";
        }

        return $this->prefix ?: $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
