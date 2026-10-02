<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AncestorResearch extends Model
{
    protected $table = 'ancestor_research';

    protected $fillable = [
        'user_id',
        'fs_id',
        'researched_at',
        'notes',
        'lds_baptism_on',
        'baptized_while_living',
        'pioneer',
    ];

    protected $casts = [
        'researched_at' => 'datetime',
        'lds_baptism_on' => 'date',
        'baptized_while_living' => 'boolean',
        'pioneer' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
