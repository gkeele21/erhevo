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
     * Ordered blocks that make up this lesson.
     */
    public function items(): HasMany
    {
        return $this->hasMany(LessonItem::class)->orderBy('sort_order');
    }

    /**
     * Replace this lesson's items with the given ordered array.
     * The array order becomes the sort_order, so reordering on the
     * client persists by simply re-saving.
     */
    public function syncItems(array $items): void
    {
        $this->items()->delete();

        foreach ($items as $index => $item) {
            $this->items()->create([
                'type' => $item['type'],
                'sort_order' => $index,
                'content' => $item['content'] ?? null,
                'config' => $item['config'] ?? null,
            ]);
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
