<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\ChurchCalling;
use Illuminate\Database\Seeder;

class ChurchLeadershipSeeder extends Seeder
{
    /**
     * Seed current senior Church leadership as authors, each tagged with their
     * current calling (and a matching author_callings history row). Idempotent:
     * re-running dedupes authors by name and ongoing callings by author+calling.
     *
     * Note: members of the Presidency of the Seventy are also General Authority
     * Seventies (loaded via authors:import) — they end up with both callings
     * concurrently, which is expected.
     */
    public function run(): void
    {
        $byOrg = fn (string $org) => ChurchCalling::whereHas('organization', fn ($q) => $q->where('name', $org))
            ->get()->keyBy('name');

        $fp = $byOrg('The First Presidency');
        $pb = $byOrg('The Presiding Bishopric');
        $rs = $byOrg('Relief Society General Presidency');
        $ym = $byOrg('Young Men General Presidency');
        $yw = $byOrg('Young Women General Presidency');
        $pr = $byOrg('Primary General Presidency');
        $ss = $byOrg('Sunday School General Presidency');
        $apostle = ChurchCalling::where('name', 'Apostle')->first();
        $seventyPres = ChurchCalling::whereHas('organization', fn ($q) => $q->where('name', 'The Presidency of the Seventy'))->first();

        if ($fp->isEmpty() || ! $apostle) {
            $this->command?->error('Required church callings not found — seed callings first.');
            return;
        }

        // [calling record, start date (or null), [names…]]
        $roster = [
            // First Presidency
            [$fp->get('President'), null, ['Dallin H. Oaks']],
            [$fp->get('1st Counselor'), null, ['Henry B. Eyring']],
            [$fp->get('2nd Counselor'), null, ['D. Todd Christofferson']],

            // Quorum of the Twelve Apostles is loaded from
            // database/data/quorum-of-the-twelve.csv (per-person call dates).

            // Presidency of the Seventy (also GA Seventies → concurrent callings)
            [$seventyPres, null, [
                'Carl B. Cook', 'S. Mark Palmer', 'Marcus B. Nash', 'Michael T. Ringwood',
                'Arnulfo Valenzuela', 'Edward Dube', 'Kevin R. Duncan',
            ]],

            // Presiding Bishopric (since 14 Nov 2025)
            [$pb->get('Presiding Bishop'), '2025-11-14', ['W. Christopher Waddell']],
            [$pb->get('1st Counselor'), '2025-11-14', ['L. Todd Budge']],
            [$pb->get('2nd Counselor'), '2025-11-14', ['Sean Douglas']],

            // Relief Society General Presidency
            [$rs->get('President'), null, ['Camille N. Johnson']],
            [$rs->get('1st Counselor'), null, ['J. Anette Dennis']],
            [$rs->get('2nd Counselor'), null, ['Kristin M. Yee']],

            // Young Women General Presidency
            [$yw->get('President'), null, ['Emily Belle Freeman']],
            [$yw->get('1st Counselor'), null, ['Tamara W. Runia']],
            [$yw->get('2nd Counselor'), null, ['Andrea M. Spannaus']],

            // Young Men General Presidency
            [$ym->get('President'), null, ['Timothy L. Farnes']],
            [$ym->get('1st Counselor'), null, ['David J. Wunderli']],
            [$ym->get('2nd Counselor'), null, ['Sean R. Dixon']],

            // Primary General Presidency
            [$pr->get('President'), null, ['Susan H. Porter']],
            [$pr->get('1st Counselor'), null, ['Amy A. Wright']],
            [$pr->get('2nd Counselor'), null, ['Tracy Y. Browning']],

            // Sunday School General Presidency
            [$ss->get('President'), null, ['Paul V. Johnson']],
            [$ss->get('1st Counselor'), null, ['Chad H Webb']],
            [$ss->get('2nd Counselor'), null, ['Gabriel W. Reid']],
        ];

        $count = 0;
        foreach ($roster as [$calling, $start, $names]) {
            if (! $calling) {
                continue;
            }
            foreach ($names as $name) {
                Author::findOrCreateByName($name)->assignCalling($calling->id, $start);
                $count++;
            }
        }

        $this->command?->info("Seeded {$count} church leaders.");
    }
}
