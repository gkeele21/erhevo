<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalkRead extends Model
{
    protected $fillable = [
        'talk_id',
        'user_id',
        'read_on',
    ];

    protected $casts = [
        'read_on' => 'date',
    ];

    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
