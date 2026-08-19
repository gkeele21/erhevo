<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalkRating extends Model
{
    protected $fillable = [
        'talk_id',
        'user_id',
        'rating',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Keep the talk's cached average in step with its ratings. Doing it here
     * rather than in the controller means imports, tests and tinker sessions
     * all stay consistent too.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $rating) => static::refreshTalkAverage($rating->talk_id));
        static::deleted(fn (self $rating) => static::refreshTalkAverage($rating->talk_id));
    }

    public static function refreshTalkAverage(int $talkId): void
    {
        $stats = static::where('talk_id', $talkId)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        Talk::whereKey($talkId)->update([
            'ratings_count' => $stats->total,
            'average_rating' => $stats->total ? round((float) $stats->average, 2) : null,
        ]);
    }

    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
