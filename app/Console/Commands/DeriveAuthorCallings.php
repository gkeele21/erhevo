<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\AuthorCalling;
use App\Models\Talk;
use Illuminate\Console\Command;

class DeriveAuthorCallings extends Command
{
    protected $signature = 'talks:derive-author-callings';

    protected $description = 'Derive author calling history from their talks: one stint per calling, dated by first/last talk. Additive — never overwrites callings an author already has.';

    public function handle(): int
    {
        // Author's current (primary) calling, so we can mark a matching derived
        // stint as ongoing rather than ending it at the last talk.
        $currentByAuthor = Author::pluck('church_calling_id', 'id');

        // Callings each author already has recorded — don't duplicate/overwrite.
        // (foreach, not an arrow fn, since arrow fns capture $existing by value.)
        $existing = [];
        foreach (AuthorCalling::select('author_id', 'church_calling_id')->get() as $c) {
            $existing[$c->author_id . '-' . $c->church_calling_id] = true;
        }

        // One row per (author, calling) with first/last talk dates.
        $groups = Talk::whereNotNull('author_id')
            ->whereNotNull('church_calling_id')
            ->whereNotNull('talk_date')
            ->selectRaw('author_id, church_calling_id, MIN(talk_date) as first_date, MAX(talk_date) as last_date')
            ->groupBy('author_id', 'church_calling_id')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($groups as $g) {
            if (isset($existing[$g->author_id . '-' . $g->church_calling_id])) {
                $skipped++;
                continue;
            }

            $isCurrent = (int) ($currentByAuthor[$g->author_id] ?? 0) === (int) $g->church_calling_id;

            AuthorCalling::create([
                'author_id' => $g->author_id,
                'church_calling_id' => $g->church_calling_id,
                'start_date' => $g->first_date,
                'end_date' => $isCurrent ? null : $g->last_date,
            ]);
            $created++;
        }

        $this->info("Created {$created} derived calling stint(s); skipped {$skipped} already recorded.");
        $this->line('Note: dates are bounded by first/last talk in each calling — approximate, not exact call/release dates.');

        return self::SUCCESS;
    }
}
