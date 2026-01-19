<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StoryImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_id',
        'user_id',
        'path',
        'filename',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function deleteFile(): bool
    {
        return Storage::disk('public')->delete($this->path);
    }

    protected static function booted(): void
    {
        static::deleting(function (StoryImage $image) {
            $image->deleteFile();
        });
    }
}
