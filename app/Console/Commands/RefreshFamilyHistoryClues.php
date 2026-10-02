<?php

namespace App\Console\Commands;

use App\Models\Ancestor;
use App\Services\FamilyHistory\ChurchHistory;
use Illuminate\Console\Command;

/**
 * Recomputes Church history clues (sites lived in or near, pioneer signals)
 * from the events already stored on each ancestor. Run it after changing the
 * rules in ChurchHistory, so users don't have to re-import their GEDCOM — the
 * file itself is never kept.
 */
class RefreshFamilyHistoryClues extends Command
{
    protected $signature = 'family-history:refresh-clues
        {--user= : Only this user id}';

    protected $description = 'Recompute Family History church-site and pioneer clues from stored ancestor events';

    public function handle(ChurchHistory $churchHistory): int
    {
        $query = Ancestor::query()->when($this->option('user'), fn ($q, $id) => $q->where('user_id', $id));
        $changed = 0;

        $query->chunkById(500, function ($ancestors) use ($churchHistory, &$changed) {
            foreach ($ancestors as $ancestor) {
                $ancestor->fill($churchHistory->clues($ancestor->events ?? [], $ancestor->birth_year));
                if ($ancestor->isDirty()) {
                    $ancestor->save();
                    $changed++;
                }
            }
        });

        $this->info("Updated {$changed} ancestors.");

        return self::SUCCESS;
    }
}
