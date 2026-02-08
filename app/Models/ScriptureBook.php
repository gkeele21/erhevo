<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScriptureBook extends Model
{
    protected $fillable = [
        'volume_id',
        'name',
        'slug',
        'abbreviation',
        'sort_order',
        'chapter_count',
    ];

    public function volume(): BelongsTo
    {
        return $this->belongsTo(ScriptureVolume::class, 'volume_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(ScriptureChapter::class, 'book_id')->orderBy('chapter_number');
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }
}
