<?php

namespace App\Models;

use App\Enums\LessonItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonItem extends Model
{
    protected $fillable = [
        'lesson_id',
        'parent_id',
        'type',
        'sort_order',
        'content',
        'config',
    ];

    protected $casts = [
        'type' => LessonItemType::class,
        'sort_order' => 'integer',
        'config' => 'array',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Items contained in this block (only groups have children).
     */
    public function children(): HasMany
    {
        return $this->hasMany(LessonItem::class, 'parent_id')->orderBy('sort_order');
    }
}
