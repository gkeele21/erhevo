<?php

namespace App\Models;

use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'description',
        'user_id',
        'cfm_week_id',
        'visibility',
        'published_at',
    ];

    protected $casts = [
        'visibility' => Visibility::class,
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Lesson $lesson) {
            if (empty($lesson->uuid)) {
                $lesson->uuid = (string) Str::uuid();
            }
            if (empty($lesson->slug)) {
                $lesson->slug = Str::slug($lesson->title) . '-' . Str::random(6);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cfmWeek(): BelongsTo
    {
        return $this->belongsTo(CfmWeek::class);
    }

    /**
     * Top-level blocks (loose items and groups) that make up this lesson,
     * in order. Group children are loaded via the item's children() relation.
     */
    public function items(): HasMany
    {
        return $this->hasMany(LessonItem::class)
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    /**
     * Every block in the lesson regardless of nesting level.
     */
    public function allItems(): HasMany
    {
        return $this->hasMany(LessonItem::class);
    }

    /**
     * Replace this lesson's blocks with the given ordered tree.
     *
     * Each node is a loose item or a group; a group node carries a
     * "children" array of items. The array order becomes the sort_order at
     * each level, so reordering on the client persists by simply re-saving.
     */
    public function syncItems(array $nodes): void
    {
        $this->allItems()->delete();

        foreach ($nodes as $index => $node) {
            $item = LessonItem::create([
                'lesson_id' => $this->id,
                'parent_id' => null,
                'post_id' => $node['post_id'] ?? null,
                'type' => $node['type'],
                'sort_order' => $index,
                'content' => $node['content'] ?? null,
                'config' => $node['config'] ?? null,
            ]);

            if (($node['type'] ?? null) === 'group') {
                foreach ($node['children'] ?? [] as $childIndex => $child) {
                    LessonItem::create([
                        'lesson_id' => $this->id,
                        'parent_id' => $item->id,
                        'post_id' => $child['post_id'] ?? null,
                        'type' => $child['type'],
                        'sort_order' => $childIndex,
                        'content' => $child['content'] ?? null,
                        'config' => $child['config'] ?? null,
                    ]);
                }
            }
        }
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', Visibility::Public);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user) {
            return $query->public();
        }

        return $query->where(function ($q) use ($user) {
            $q->where('visibility', Visibility::Public)
                ->orWhere('user_id', $user->id)
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('visibility', Visibility::Friends)
                        ->whereIn('user_id', $user->friendIds());
                });
        });
    }

    public function isVisibleTo(?User $user): bool
    {
        if ($this->visibility === Visibility::Public) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        if ($this->visibility === Visibility::Friends) {
            return $user->isFriendWith($this->user_id);
        }

        return false;
    }
}
