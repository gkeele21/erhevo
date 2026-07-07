<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorCalling extends Model
{
    protected $fillable = [
        'author_id',
        'church_calling_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function calling(): BelongsTo
    {
        return $this->belongsTo(ChurchCalling::class, 'church_calling_id');
    }

    /** Callings the author currently holds (no end date). */
    public function scopeCurrent($query)
    {
        return $query->whereNull('end_date');
    }
}
