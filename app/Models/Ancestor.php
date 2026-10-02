<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A direct-line ancestor (or the root person, generation 0) from the user's
 * imported GEDCOM. Rows are rebuilt on every import, so anything the user
 * adds lives in AncestorResearch, joined in by withResearch().
 */
class Ancestor extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'deceased' => 'boolean',
        'baptized_while_living' => 'boolean',
        'lds_affiliation' => 'boolean',
        'likely_pioneer' => 'boolean',
        'has_church_places' => 'boolean',
        'pioneer_signals' => 'array',
        'church_places' => 'array',
        'events' => 'array',
    ];

    /** SQL for the user's answer where given, else what the GEDCOM says. */
    public const EFFECTIVE_BAPTIZED = 'COALESCE(ancestor_research.baptized_while_living, ancestors.baptized_while_living)';
    public const EFFECTIVE_PIONEER = 'COALESCE(ancestor_research.pioneer, ancestors.likely_pioneer)';
    public const EFFECTIVE_BAPTISM_SORT = 'COALESCE(ancestor_research.lds_baptism_on, ancestors.lds_baptism_sort)';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithResearch(Builder $query): Builder
    {
        return $query
            ->leftJoin('ancestor_research', function ($join) {
                $join->on('ancestor_research.user_id', '=', 'ancestors.user_id')
                    ->on('ancestor_research.fs_id', '=', 'ancestors.fs_id');
            })
            ->select('ancestors.*')
            ->addSelect([
                'ancestor_research.researched_at',
                'ancestor_research.notes as research_notes',
                'ancestor_research.lds_baptism_on as research_baptism_on',
                DB::raw(self::EFFECTIVE_BAPTIZED.' as effective_baptized'),
                DB::raw(self::EFFECTIVE_PIONEER.' as effective_pioneer'),
                'ancestor_research.baptized_while_living as research_baptized',
                'ancestor_research.pioneer as research_pioneer',
            ]);
    }

    public function scopeBaptizedWhileLiving(Builder $query): Builder
    {
        return $query->whereRaw(self::EFFECTIVE_BAPTIZED.' = 1');
    }

    public function scopePioneers(Builder $query): Builder
    {
        return $query->whereRaw(self::EFFECTIVE_PIONEER.' = 1');
    }

    /** "3rd great-grandmother", from generation and sex. */
    public function relationship(): string
    {
        if ($this->generation === 0) {
            return 'You';
        }
        $base = match ($this->sex) {
            'M' => ['father', 'grandfather'],
            'F' => ['mother', 'grandmother'],
            default => ['parent', 'grandparent'],
        };
        if ($this->generation === 1) {
            return ucfirst($base[0]);
        }
        if ($this->generation === 2) {
            return ucfirst($base[1]);
        }
        $greats = $this->generation - 2;

        return $greats === 1
            ? 'Great-'.$base[1]
            : self::ordinal($greats).' great-'.$base[1];
    }

    /**
     * Which parent's line this ancestor is on. Null for the root and for
     * parents themselves ("your father on your father's side").
     */
    public function side(): ?string
    {
        if ($this->generation < 2) {
            return null;
        }

        return (($this->ahnentafel >> ($this->generation - 1)) & 1) ? 'mother' : 'father';
    }

    /** The user's baptism date where they entered one, else the GEDCOM's (display form). */
    public function baptismDate(): ?string
    {
        if ($this->research_baptism_on) {
            return date('j M Y', strtotime($this->research_baptism_on));
        }

        return $this->lds_baptism_date;
    }

    public function familySearchUrl(string $tab = 'details'): string
    {
        return "https://www.familysearch.org/tree/person/{$tab}/{$this->fs_id}";
    }

    /** Compact shape for lists and the pedigree chart. */
    public function summary(): array
    {
        return [
            'fs_id' => $this->fs_id,
            'name' => $this->name,
            'sex' => $this->sex,
            'generation' => $this->generation,
            'relationship' => $this->relationship(),
            'side' => $this->side(),
            'birth_year' => $this->birth_year,
            'death_year' => $this->death_year,
            'birth_place' => $this->birth_place,
            'baptism_date' => $this->baptismDate(),
            'baptized_while_living' => (bool) $this->effective_baptized,
            'pioneer' => (bool) $this->effective_pioneer,
            'church_places' => collect($this->church_places ?? [])->pluck('label')->all(),
            'researched' => $this->researched_at !== null,
        ];
    }

    private static function ordinal(int $n): string
    {
        $suffix = in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');

        return $n.$suffix;
    }
}
