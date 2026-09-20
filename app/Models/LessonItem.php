<?php

namespace App\Models;

use App\Enums\LessonItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonItem extends Model
{
    use Concerns\HasScriptureReferences;

    protected $fillable = [
        'lesson_id',
        'parent_id',
        'post_id',
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
     * The Post this block references, if any (e.g. a Quote block linked to a
     * Quote-type post). Null for blocks that carry their own inline content.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Turn a scripture block's picker config into reference rows, so the
     * passage can find the lesson the same way it finds a post. Blocks of any
     * other type simply have no references.
     */
    public function syncScriptureReferencesFromConfig(): void
    {
        if ($this->type !== LessonItemType::Scripture) {
            return;
        }

        $config = $this->config ?? [];

        if (empty($config['start_chapter_id'])) {
            $this->syncScriptureReferences([]);

            return;
        }

        // "Same chapter" comes through as no end chapter; an end verse on its
        // own means the range stays inside the start chapter.
        $endChapterId = $config['end_chapter_id'] ?? null;
        if ($endChapterId && (int) $endChapterId === (int) $config['start_chapter_id']) {
            $endChapterId = null;
        }

        $this->syncScriptureReferences([[
            'start_chapter_id' => (int) $config['start_chapter_id'],
            'start_verse' => $config['start_verse'] ?? null,
            'end_chapter_id' => $endChapterId ? (int) $endChapterId : null,
            'end_verse' => $config['end_verse'] ?? null,
        ]]);
    }

    /**
     * Items contained in this block (only groups have children).
     */
    public function children(): HasMany
    {
        return $this->hasMany(LessonItem::class, 'parent_id')->orderBy('sort_order');
    }
}
