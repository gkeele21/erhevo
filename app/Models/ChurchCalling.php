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
