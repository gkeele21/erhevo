<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TempleVisit extends Model
{
    protected $fillable = [
        'user_id',
        'temple_id',
        'visited_on',
        'ordinances',
        'notes',
    ];

    protected $casts = [
        'visited_on' => 'date',
        'ordinances' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function temple(): BelongsTo
    {
        return $this->belongsTo(Temple::class);
    }
}
