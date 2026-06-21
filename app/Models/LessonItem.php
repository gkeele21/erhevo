<?php

namespace App\Models;

use App\Enums\LessonItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonItem extends Model
{
    protected $fillable = [
        'lesson_id',
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
}
