<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StudyPlan extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'config',
        'start_date',
        'end_date',
        'frequency',
    ];

    protected $casts = [
        'config' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StudyPlanItem::class)->orderBy('sort_order');
    }

    /**
     * Human-readable description of what the plan covers, resolved from the
     * ids in config (e.g. "Talks by Russell M. Nelson — President
     * (2018–2025)"). Not appended by default; controllers append it where
     * the UI shows it.
     */
    public function getCriteriaSummaryAttribute(): string
    {
        $config = $this->config ?? [];

        if ($this->type === 'scripture') {
            $volume = ScriptureVolume::find($config['volume_id'] ?? null)?->name ?? 'Scriptures';

            if (! empty($config['book_ids'])) {
                $books = ScriptureBook::whereIn('id', $config['book_ids'])
                    ->orderBy('sort_order')
                    ->pluck('name');
                $list = $books->take(3)->implode(', ')
                    . ($books->count() > 3 ? ' + ' . ($books->count() - 3) . ' more' : '');

                return "{$volume}: {$list}";
            }

            return "{$volume} (entire volume)";
        }

        if (($config['mode'] ?? null) === 'conference') {
            return GeneralConference::find($config['general_conference_id'] ?? null)?->name
                ?? 'General Conference';
        }

        if (($config['mode'] ?? null) === 'author') {
            $author = Author::find($config['author_id'] ?? null)?->full_name ?? 'Unknown author';
            $period = ! empty($config['author_calling_id'])
                ? AuthorCalling::with('calling.organization')->find($config['author_calling_id'])
                : null;

            if ($period) {
                $label = $period->calling?->display_label ?? 'Calling';
                $start = $period->start_date?->format('Y') ?? '?';
                $end = $period->end_date?->format('Y') ?? 'present';

                return "Talks by {$author} — {$label} ({$start}–{$end})";
            }

            return "All talks by {$author}";
        }

        $calling = ChurchCalling::with('organization')->find($config['church_calling_id'] ?? null);
        $label = $calling?->display_label ?? 'Calling';
        $years = $config['years_back'] ?? null;

        return $years
            ? "Talks given as {$label} — last " . $years . ' ' . Str::plural('year', $years)
            : "Talks given as {$label}";
    }
}
