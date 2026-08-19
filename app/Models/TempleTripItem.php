<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TempleTripItem extends Model
{
    protected $fillable = [
        'temple_trip_id',
        'temple_id',
        'sort_order',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(TempleTrip::class, 'temple_trip_id');
    }

    public function temple(): BelongsTo
    {
        return $this->belongsTo(Temple::class);
    }
}
