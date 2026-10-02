<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyTree extends Model
{
    protected $fillable = [
        'user_id',
        'root_fs_id',
        'root_name',
        'source',
        'people_count',
        'ancestor_count',
        'generation_count',
        'imported_at',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
