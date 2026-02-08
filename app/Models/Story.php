<?php

namespace App\Models;

use App\Enums\AuthorType;
use App\Enums\PostType;
use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Story extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'post_type',
        'title',
        'slug',
        'content',
        'content_anonymized',
        'excerpt',
        'cover_image',
        'user_id',
        'author_type',
        'author_text',
        'author_user_id',
        'category_id',
        'visibility',
        'hide_creator',
        'hide_author',
        'anonymize_names',
        'name_mappings',
        'published_at',
    ];

    protected $casts = [
        'post_type' => PostType::class,
        'visibility' => Visibility::class,
        'author_type' => AuthorType::class,
        'hide_creator' => 'boolean',
        'hide_author' => 'boolean',
        'anonymize_names' => 'boolean',
        'name_mappings' => 'array',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'creator_name',
        'author_name',
        'display_content',
    ];

    protected static function booted(): void
    {
        static::creating(function (Story $story) {
            if (empty($story->uuid)) {
                $story->uuid = (string) Str::uuid();
            }
            if (empty($story->slug)) {
                $story->slug = Str::slug($story->title) . '-' . Str::random(6);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(StoryImage::class);
    }

    public function editTokens(): HasMany
    {
        return $this->hasMany(StoryEditToken::class);
    }

    public function getDisplayContentAttribute(): string
    {
        if ($this->anonymize_names && $this->content_anonymized) {
            return $this->content_anonymized;
        }

        return $this->content;
    }

    public function getAuthorNameAttribute(): ?string
    {
        if ($this->hide_author) {
            return null;
        }

        return match ($this->author_type) {
            AuthorType::Self => $this->user?->name,
            AuthorType::Text => $this->author_text,
            AuthorType::User => $this->authorUser?->name,
        };
    }

    public function getCreatorNameAttribute(): ?string
    {
        if ($this->hide_creator) {
            return null;
        }

        return $this->user?->name;
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

    public function syncTags(array $tagNames): void
    {
        $tagIds = collect($tagNames)->map(function ($name) {
            return Tag::findOrCreateByName(trim($name))->id;
        })->toArray();

        $this->tags()->sync($tagIds);
    }
}
