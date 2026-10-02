<?php

namespace App\Services\FamilyHistory;

use App\Models\FamilyTree;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Turns a GEDCOM export into the user's Family History: the root person and
 * their direct-line ancestors, with LDS baptism, gathering-place and pioneer
 * clues worked out up front so lists can filter on them. Replaces any earlier
 * import; research notes are keyed by FamilySearch ID and carry over.
 */
class FamilyTreeImporter
{
    /** GEDCOM tag → timeline label. EVEN uses its TYPE instead. */
    private const LABELS = [
        'BIRT' => 'Birth', 'CHR' => 'Christening', 'CHRA' => 'Adult christening',
        'BAPM' => 'Baptism', 'DEAT' => 'Death', 'BURI' => 'Burial', 'CREM' => 'Cremation',
        'RESI' => 'Residence', 'CENS' => 'Census', 'IMMI' => 'Immigration',
        'EMIG' => 'Emigration', 'NATU' => 'Naturalization', 'OCCU' => 'Occupation',
        'RELI' => 'Religion', 'BAPL' => 'LDS baptism', 'CONL' => 'LDS confirmation',
        'ENDL' => 'Endowment', 'SLGC' => 'Sealed to parents', 'PROB' => 'Probate',
        'WILL' => 'Will', 'MARR' => 'Marriage',
    ];

    private const CHUNK = 500;

    public function __construct(
        private GedcomParser $parser,
        private ChurchHistory $churchHistory,
    ) {}

    public function import(User $user, string $path, ?string $rootFsId = null): FamilyTree
    {
        $data = $this->parser->parse($path);
        $people = $data['individuals'];
        $families = $data['families'];

        $rootXref = $this->findRoot($people, $rootFsId);
        $lineage = $this->ancestry($rootXref, $people, $families);

        $now = now();
        $rows = [];
        foreach ($lineage as $xref => [$generation, $ahnentafel]) {
            $person = $people[$xref];
            if (! $person['fs_id']) {
                continue;
            }
            $rows[] = $this->row($user, $xref, $person, $generation, $ahnentafel, $people, $families) + [
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return DB::transaction(function () use ($user, $rows, $people, $rootXref, $data, $now) {
            $user->ancestors()->delete();
            foreach (array_chunk($rows, self::CHUNK) as $chunk) {
                DB::table('ancestors')->insert($chunk);
            }

            return FamilyTree::updateOrCreate(['user_id' => $user->id], [
                'root_fs_id' => $people[$rootXref]['fs_id'],
                'root_name' => $people[$rootXref]['name'] ?? 'Unknown',
                'source' => $data['source'],
                'people_count' => count($people),
                'ancestor_count' => max(count($rows) - 1, 0),
                'generation_count' => $rows ? max(array_column($rows, 'generation')) : 0,
                'imported_at' => $now,
            ]);
        });
    }

    private function findRoot(array $people, ?string $rootFsId): string
    {
        if ($rootFsId) {
            foreach ($people as $xref => $person) {
                if (strcasecmp((string) $person['fs_id'], $rootFsId) === 0) {
                    return $xref;
                }
            }
            throw new GedcomException("Nobody in this file has the FamilySearch ID {$rootFsId}.");
        }

        // Ancestral Quest and RootsMagic write the starting person first.
        foreach ($people as $xref => $person) {
            if ($person['fs_id']) {
                return $xref;
            }
        }
        throw new GedcomException('This file has no FamilySearch IDs. Export it from Ancestral Quest or RootsMagic after downloading from FamilySearch.');
    }

    /**
     * Breadth-first walk up the tree, so each ancestor keeps the shortest
     * path (lowest generation and Ahnentafel number) when pedigree collapse
     * reaches them twice.
     *
     * @return array<string, array{0: int, 1: int}> xref → [generation, ahnentafel]
     */
    private function ancestry(string $rootXref, array $people, array $families): array
    {
        $lineage = [$rootXref => [0, 1]];
        $queue = [$rootXref];
        while ($queue) {
            $xref = array_shift($queue);
            [$generation, $number] = $lineage[$xref];
            [$father, $mother] = $this->parents($people[$xref], $families);
            foreach ([[$father, 2 * $number], [$mother, 2 * $number + 1]] as [$parent, $parentNumber]) {
                if ($parent && isset($people[$parent]) && ! isset($lineage[$parent])) {
                    $lineage[$parent] = [$generation + 1, $parentNumber];
                    $queue[] = $parent;
                }
            }
        }

        return $lineage;
    }

    /**
     * For someone linked to several parent families: FamilySearch's preferred
     * parents (`_PRIMARY`), then a birth family, then anything not adoptive
     * or foster, then whatever came first.
     */
    private function parents(array $person, array $families): array
    {
        $links = collect($person['famc']);
        $link = $links->firstWhere('primary', true)
            ?? $links->firstWhere('pedi', 'birth')
            ?? $links->first(fn ($l) => ! in_array($l['pedi'], ['adopted', 'foster'], true))
            ?? $links->first();
        $family = $link ? $families[$link['xref']] ?? null : null;

        return [$family['husb'] ?? null, $family['wife'] ?? null];
    }

    private function row(User $user, string $xref, array $person, int $generation, int $ahnentafel, array $people, array $families): array
    {
        $events = $this->timeline($xref, $person, $people, $families);
        $first = fn (string $tag) => collect($person['events'])->firstWhere('tag', $tag);

        $birth = $first('BIRT') ?? $first('CHR');
        $death = $first('DEAT');
        $burial = $first('BURI') ?? $first('CREM');
        // Ordinance record first; some trees only have a hand-entered "LdsBaptism" fact.
        $baptism = collect($person['events'])->first(fn ($e) => $e['tag'] === 'BAPL' && $e['date'])
            ?? collect($person['events'])->first(fn ($e) => $e['tag'] === 'EVEN' && strcasecmp((string) $e['type'], 'LdsBaptism') === 0 && $e['date']);

        $birthYear = GedcomDate::year($birth['date'] ?? null);
        $baptismSort = GedcomDate::sortKey($baptism['date'] ?? null);
        $deathSort = GedcomDate::sortKey($death['date'] ?? null);
        $churchPlaces = $this->churchHistory->places($events);
        $pioneerSignals = $this->churchHistory->pioneerSignals($events, $birthYear);

        [$father, $mother] = $this->parents($person, $families);

        return [
            'user_id' => $user->id,
            'fs_id' => $person['fs_id'],
            'name' => mb_substr($person['name'] ?? 'Unknown', 0, 255),
            'given_name' => $this->clip($person['given']),
            'surname' => $this->clip($person['surname']),
            'sex' => in_array($person['sex'], ['M', 'F'], true) ? $person['sex'] : null,
            'generation' => $generation,
            'ahnentafel' => $ahnentafel,
            'father_fs_id' => $father ? $people[$father]['fs_id'] ?? null : null,
            'mother_fs_id' => $mother ? $people[$mother]['fs_id'] ?? null : null,
            'birth_date' => $this->clip($birth['date'] ?? null),
            'birth_year' => $birthYear,
            'birth_place' => $this->clip($birth['place'] ?? null),
            'death_date' => $this->clip($death['date'] ?? null),
            'death_year' => GedcomDate::year($death['date'] ?? null),
            'death_place' => $this->clip($death['place'] ?? null),
            // A bare "1 DEAT Y" still means deceased.
            'deceased' => $death !== null,
            'burial_date' => $this->clip($burial['date'] ?? null),
            'burial_place' => $this->clip($burial['place'] ?? null),
            'lds_baptism_date' => $this->clip($baptism['date'] ?? null),
            'lds_baptism_sort' => $baptismSort,
            'baptized_while_living' => self::wasLivingAt($baptismSort, $deathSort, $death !== null),
            'lds_affiliation' => collect($person['events'])->contains(fn ($e) => in_array($e['tag'], ['RELI', 'EVEN'], true)
                && preg_match('/latter[- ]day saints|\blds\b|mormon/i', (string) $e['value'])),
            'likely_pioneer' => (bool) $pioneerSignals,
            'has_church_places' => (bool) $churchPlaces,
            'pioneer_signals' => json_encode($pioneerSignals),
            'church_places' => json_encode($churchPlaces),
            'events' => json_encode($events),
        ];
    }

    /**
     * True when a baptism happened on or before death. Partial dates compare
     * on what's known; a same-year baptism with an unknown month counts.
     */
    public static function wasLivingAt(?string $baptismSort, ?string $deathSort, bool $deceased): bool
    {
        if (! $baptismSort) {
            return false;
        }
        if (! $deathSort) {
            // Baptized, and no death recorded — still living, or death unknown.
            return ! $deceased;
        }
        [$by, $bm, $bd] = array_map('intval', explode('-', $baptismSort));
        [$dy, $dm, $dd] = array_map('intval', explode('-', $deathSort));
        if ($by !== $dy) {
            return $by < $dy;
        }
        if (! $bm || ! $dm || $bm !== $dm) {
            return ! $bm || ! $dm || $bm < $dm;
        }

        return ! $bd || ! $dd || $bd <= $dd;
    }

    /**
     * Dated or placed events for the person's page, in date order: their own
     * events, their marriages, and their children's births — often the only
     * record of where a family was living.
     */
    private function timeline(string $xref, array $person, array $people, array $families): array
    {
        $events = [];
        foreach ($person['events'] as $event) {
            if (! $event['date'] && ! $event['place']) {
                continue;
            }
            $label = $event['tag'] === 'EVEN' ? ($event['type'] ?? 'Event') : self::LABELS[$event['tag']];
            $events[] = $this->event($label, $event, $event['value']);
        }

        foreach ($person['fams'] as $famXref) {
            $family = $families[$famXref] ?? null;
            if (! $family) {
                continue;
            }
            $spouseXref = $family['husb'] === $xref ? $family['wife'] : $family['husb'];
            $spouse = $people[$spouseXref]['name'] ?? null;
            foreach ($family['events'] as $event) {
                if ($event['date'] || $event['place']) {
                    $events[] = $this->event('Marriage', $event, $spouse ? "to {$spouse}" : null);
                }
            }
            foreach ($family['chil'] as $childXref) {
                $child = $people[$childXref] ?? null;
                $birth = $child ? collect($child['events'])->firstWhere('tag', 'BIRT') : null;
                if ($birth && ($birth['date'] || $birth['place'])) {
                    $events[] = $this->event('Child born', $birth, $child['name']);
                }
            }
        }

        usort($events, fn ($a, $b) => [$a['sort'] ?? '9999', $a['label']] <=> [$b['sort'] ?? '9999', $b['label']]);

        return $events;
    }

    private function event(string $label, array $event, ?string $detail): array
    {
        return [
            'label' => $label,
            'date' => $event['date'],
            'year' => GedcomDate::year($event['date']),
            'sort' => GedcomDate::sortKey($event['date']),
            'place' => $event['place'],
            // Some trees repeat the event type as its value ("Obituary" / "Obituary").
            'detail' => $detail !== null && $detail !== '' && strcasecmp(trim($detail), $label) !== 0 ? mb_substr($detail, 0, 300) : null,
        ];
    }

    private function clip(?string $value): ?string
    {
        return $value === null || $value === '' ? null : mb_substr($value, 0, 255);
    }
}
