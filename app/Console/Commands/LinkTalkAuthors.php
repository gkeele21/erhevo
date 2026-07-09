<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Talk;
use Illuminate\Console\Command;

class LinkTalkAuthors extends Command
{
    protected $signature = 'talks:link-authors {--relink : Re-evaluate talks that already have an author_id}';

    protected $description = 'Link talks to Author records by exact (normalized) speaker name. Ambiguous or unknown names are left unlinked.';

    public function handle(): int
    {
        // Map normalized full name => [author ids]. Only unique names are usable.
        $byName = [];
        Author::all()->each(function (Author $a) use (&$byName) {
            $key = $this->normalize($a->full_name);
            if ($key !== '') {
                $byName[$key][] = $a->id;
            }
        });

        $linked = 0;
        $ambiguous = 0;
        $unmatched = [];

        Talk::whereNotNull('speaker_name')
            ->when(! $this->option('relink'), fn ($q) => $q->whereNull('author_id'))
            ->chunkById(300, function ($talks) use ($byName, &$linked, &$ambiguous, &$unmatched) {
                foreach ($talks as $talk) {
                    $ids = $byName[$this->normalize($talk->speaker_name)] ?? [];

                    if (count($ids) === 1) {
                        $talk->update(['author_id' => $ids[0]]);
                        $linked++;
                    } elseif (count($ids) > 1) {
                        $ambiguous++;
                    } else {
                        $unmatched[$talk->speaker_name] = ($unmatched[$talk->speaker_name] ?? 0) + 1;
                    }
                }
            });

        $this->info("Linked {$linked} talk(s) to authors.");
        if ($ambiguous) {
            $this->warn("{$ambiguous} talk(s) had an ambiguous name (multiple authors) — left unlinked.");
        }
        if ($unmatched) {
            arsort($unmatched);
            $this->warn(count($unmatched) . ' distinct speaker name(s) had no author match. Top unmatched:');
            foreach (array_slice($unmatched, 0, 15, true) as $name => $n) {
                $this->line("  {$name} ({$n})");
            }
        }

        return self::SUCCESS;
    }

    private function normalize(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', str_replace('.', '', mb_strtolower($name))));
    }
}
