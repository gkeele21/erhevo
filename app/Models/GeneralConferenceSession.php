<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneralConferenceSession extends Model
{
    protected $fillable = [
        'general_conference_id',
        'session_type_id',
        'name',
        'session_date',
        'display_order',
    ];

    protected $casts = [
        'session_date' => 'date',
        'display_order' => 'integer',
    ];

    public function conference(): BelongsTo
    {
        return $this->belongsTo(GeneralConference::class, 'general_conference_id');
    }

    public function sessionType(): BelongsTo
    {
        return $this->belongsTo(GeneralConferenceSessionType::class, 'session_type_id');
    }

    public function talks(): HasMany
    {
        return $this->hasMany(Talk::class, 'general_conference_session_id')->orderBy('display_order');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
