<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UserCategory extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'parent_id',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserCategory $category) {
            if (empty($category->slug)) {
                $baseSlug = Str::slug($category->name);
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('user_id', $category->user_id)
                    ->where('slug', $slug)
                    ->exists()
                ) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $category->slug = $slug;
            }
        });

        static::updating(function (UserCategory $category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $baseSlug = Str::slug($category->name);
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('user_id', $category->user_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $category->id)
                    ->exists()
                ) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $category->slug = $slug;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(UserCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(UserCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }
}
