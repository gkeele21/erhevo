<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsFromJson;
use Illuminate\Database\Seeder;

/**
 * All church figures (current leadership, GA/Area Seventies, historical First
 * Presidency, and every General Conference speaker) from the
 * database/data/seed/authors.json snapshot.
 *
 * Supersedes ChurchLeadershipSeeder and the `authors:import` CSV commands — the
 * snapshot already includes everything they produced, plus the ~50 speaker
 * authors that were created ad-hoc. Requires church_callings (incl. the extra
 * ids 30-35) to be seeded first.
 */
class AuthorSeeder extends Seeder
{
    use SeedsFromJson;

    public function run(): void
    {
        $n = $this->seedFromJson('authors.json', 'authors');
        $this->command?->info("  Seeded {$n} authors.");
    }
}
