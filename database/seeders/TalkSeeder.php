<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsFromJson;
use Illuminate\Database\Seeder;

/**
 * General Conference talk metadata (2,580 talks) from the
 * database/data/seed/talks.json snapshot — title, speaker, date, session,
 * calling-at-time, author link, and canonical churchofjesuschrist.org URL.
 *
 * METADATA ONLY: no body text is stored (GC talks are © Intellectual Reserve).
 *
 * Supersedes GeneralConferenceTalkSeeder and the `talks:import-pndr*` scrape
 * commands. Requires authors, church_callings, general_conference_sessions,
 * sources, and talk_types to be seeded first.
 */
class TalkSeeder extends Seeder
{
    use SeedsFromJson;

    public function run(): void
    {
        $n = $this->seedFromJson('talks.json', 'talks');
        $this->command?->info("  Seeded {$n} talks.");
    }
}
