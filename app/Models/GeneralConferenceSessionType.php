<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneralConferenceSessionType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(GeneralConferenceSession::class, 'session_type_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
