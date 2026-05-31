<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChurchOrganization extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'gender',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'gender' => Gender::class,
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChurchOrganization::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChurchOrganization::class, 'parent_id');
    }

    public function callings(): HasMany
    {
        return $this->hasMany(ChurchCalling::class);
    }

    public function talks(): HasMany
    {
        return $this->hasMany(GeneralConferenceTalk::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
