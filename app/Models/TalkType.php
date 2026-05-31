<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TalkType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    public function talks(): HasMany
    {
        return $this->hasMany(Talk::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
