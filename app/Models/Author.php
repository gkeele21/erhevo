<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Author extends Model
{
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'display_name',
        'slug',
        'church_calling_id',
        'calling_started_at',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'calling_started_at' => 'date',
    ];

    protected $appends = ['full_name'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Suffix tokens we recognize when parsing a free-text name. */
    protected static array $suffixMap = [
        'jr' => 'Jr.', 'sr' => 'Sr.', 'ii' => 'II', 'iii' => 'III',
        'iv' => 'IV', 'v' => 'V', 'phd' => 'PhD', 'md' => 'MD',
    ];

    protected static function booted(): void
    {
        static::creating(function (Author $author) {
            if (empty($author->slug)) {
                $author->slug = static::uniqueSlug($author->full_name);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The author's primary current calling (denormalized for display). */
    public function calling(): BelongsTo
    {
        return $this->belongsTo(ChurchCalling::class, 'church_calling_id');
    }

    /** Full calling history — current (no end date) first, then most recent. */
    public function callings(): HasMany
    {
        return $this->hasMany(AuthorCalling::class)
            ->orderByRaw('end_date IS NULL DESC')
            ->orderByDesc('start_date');
    }

    /** Callings the author holds right now (supports several concurrently). */
    public function currentCallings(): HasMany
    {
        return $this->hasMany(AuthorCalling::class)->whereNull('end_date');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Record a calling for this author. Defaults to an ongoing calling (no end
     * date) that also becomes the primary current calling on the author row.
     * Idempotent for a given ongoing (author, calling) pair.
     */
    public function assignCalling(int $callingId, ?string $startDate = null, bool $primary = true): AuthorCalling
    {
        $record = $this->callings()->firstOrNew(['church_calling_id' => $callingId, 'end_date' => null]);
        // Set the start date on first creation, or whenever a date is supplied
        // (so re-running with an authoritative date updates it).
        if ($startDate !== null || ! $record->exists) {
            $record->start_date = $startDate;
        }
        $record->save();

        if ($primary) {
            $this->forceFill([
                'church_calling_id' => $callingId,
                'calling_started_at' => $record->start_date,
            ])->save();
        }

        return $record;
    }

    /**
     * Assembled display name: the verbatim override if set, otherwise the
     * structured parts joined together.
     */
    public function getFullNameAttribute(): string
    {
        if (! empty($this->display_name)) {
            return $this->display_name;
        }

        return trim(implode(' ', array_filter([
            $this->first_name, $this->middle_name, $this->last_name, $this->suffix,
        ])));
    }

    public function scopeSearch($query, string $term)
    {
        $like = "%{$term}%";

        return $query->where(function ($q) use ($like) {
            $q->where('display_name', 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhereRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) like ?", [$like]);
        });
    }

    /**
     * Get-or-create the Author record representing an app user, keyed by user_id.
     */
    public static function forUser(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id],
            ['first_name' => $user->first_name, 'last_name' => $user->last_name],
        );
    }

    /**
     * Find an existing author by canonical name or create one, parsing the
     * free-text name into structured parts. Dedupes on slug.
     */
    public static function findOrCreateByName(string $name): self
    {
        $fields = static::parseName($name);
        $full = (new static($fields))->full_name;
        $slug = Str::slug($full) ?: Str::slug($name) ?: Str::slug(Str::random(8));

        return static::firstOrCreate(['slug' => $slug], $fields);
    }

    /**
     * Best-effort split of a free-text name into first/middle/last/suffix.
     * Single-token names (mononyms) are stored verbatim in display_name.
     */
    public static function parseName(string $name): array
    {
        $fields = [
            'first_name' => null, 'middle_name' => null,
            'last_name' => null, 'suffix' => null, 'display_name' => null,
        ];

        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            return $fields;
        }

        $tokens = explode(' ', $name);

        // Pull a trailing suffix off the end (Jr., III, PhD, ...).
        if (count($tokens) > 1) {
            $key = rtrim(strtolower(end($tokens)), '.');
            if (isset(static::$suffixMap[$key])) {
                $fields['suffix'] = static::$suffixMap[$key];
                array_pop($tokens);
            }
        }

        if (count($tokens) === 1) {
            // Mononym / irregular — keep it exactly as given.
            $fields['display_name'] = trim($tokens[0] . ' ' . ($fields['suffix'] ?? ''));
        } elseif (count($tokens) === 2) {
            $fields['first_name'] = $tokens[0];
            $fields['last_name'] = $tokens[1];
        } else {
            $fields['first_name'] = array_shift($tokens);
            $fields['last_name'] = array_pop($tokens);
            $fields['middle_name'] = implode(' ', $tokens);
        }

        return $fields;
    }

    protected static function uniqueSlug(string $fullName): string
    {
        $base = Str::slug($fullName) ?: Str::slug(Str::random(8));
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
