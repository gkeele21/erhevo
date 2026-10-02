<?php

namespace App\Services\FamilyHistory;

/**
 * Early Church history clues read from an ancestor's dated events: key Church
 * sites they lived in or near during the years the Church was there, and
 * whether they look like a pioneer who crossed the plains before the railroad
 * (1847–1869). These are hints for research, not conclusions — the user can
 * confirm or overrule them.
 */
class ChurchHistory
{
    public const PIONEER_FROM = 1847;
    public const PIONEER_TO = 1869;

    /**
     * Key Church sites, in order. An event counts when its years overlap the
     * site's and its place matches `town` ("in") or `near` (the surrounding
     * counties). Patterns pin the county and state, because trees are full
     * of other Harmonys, Independences and Kirtlands.
     */
    public const PLACES = [
        'palmyra' => [
            'label' => 'Palmyra, New York',
            'from' => 1816, 'to' => 1831,
            'about' => 'The Smith family farm: the First Vision, the gold plates, and the first printing of the Book of Mormon.',
            'near_label' => 'Wayne & Ontario counties, New York',
            'town' => '/\b(Palmyra|Manchester)\b.*\b(Wayne|Ontario)\b.*\b(New York|NY)\b/i',
            'near' => '/\b(Wayne|Ontario)(?: County)?,\s*(New York|NY)\b/i',
        ],
        'harmony' => [
            'label' => 'Harmony, Pennsylvania',
            'from' => 1825, 'to' => 1831,
            'about' => 'Joseph and Emma\'s home while most of the Book of Mormon was translated, and where the priesthood was restored.',
            'near_label' => 'Susquehanna County, Pennsylvania, and Broome County (Colesville), New York',
            'town' => '/\b(Harmony|Oakland)\b.*\bSusquehanna\b.*\b(Pennsylvania|PA)\b/i',
            'near' => '/\bSusquehanna(?: County)?,\s*(Pennsylvania|PA)\b|\bBroome(?: County)?,\s*(New York|NY)\b/i',
        ],
        'kirtland' => [
            'label' => 'Kirtland, Ohio',
            'from' => 1831, 'to' => 1838,
            'about' => 'Church headquarters and site of the first temple.',
            'near_label' => 'Lake & Geauga counties, Ohio',
            'town' => '/\bKirtland\b.*\b(Ohio|OH)\b/i',
            'near' => '/\b(Lake|Geauga)(?: County)?,\s*(Ohio|OH)\b/i',
        ],
        'independence' => [
            'label' => 'Independence, Missouri',
            'from' => 1831, 'to' => 1836,
            'about' => 'The gathering place in Jackson County until the Saints were driven out in 1833. Many then settled across the river in Clay County.',
            'near_label' => 'Jackson & Clay counties, Missouri',
            'town' => '/\bIndependence\b(?:,\s*Jackson(?: County)?)?,\s*(Missouri|MO)\b/i',
            'near' => '/\b(Jackson|Clay)(?: County)?,\s*(Missouri|MO)\b/i',
        ],
        'far_west' => [
            'label' => 'Far West, Missouri',
            'from' => 1836, 'to' => 1839,
            'about' => 'Church headquarters in Caldwell County, with Adam-ondi-Ahman nearby.',
            'near_label' => 'Caldwell & Daviess counties, Missouri',
            'town' => '/\bFar West\b.*\b(Missouri|MO)\b/i',
            'near' => '/\b(Caldwell|Daviess)(?: County)?,\s*(Missouri|MO)\b|\bAdam-ondi-Ahman\b/i',
        ],
        'nauvoo' => [
            'label' => 'Nauvoo, Illinois',
            'from' => 1839, 'to' => 1846,
            'about' => 'Church headquarters until the exodus west. Joseph Smith was killed at nearby Carthage in 1844.',
            'near_label' => 'Hancock County, Illinois, and Lee County (Montrose), Iowa',
            'town' => '/\bNauvoo\b.*\b(Illinois|IL)\b|\bCommerce, Hancock\b/i',
            'near' => '/\bHancock(?: County)?,\s*(Illinois|IL)\b|\bLee(?: County)?,\s*(Iowa|IA)\b/i',
        ],
        'winter_quarters' => [
            'label' => 'Winter Quarters & Council Bluffs',
            'from' => 1846, 'to' => 1852,
            'about' => 'The staging ground for the trek west.',
            'near_label' => 'Pottawattamie County, Iowa, and Douglas County, Nebraska',
            'town' => '/\b(Winter Quarters|Florence, Douglas, Nebraska|Kanesville|Council Bluffs)\b/i',
            'near' => '/\bPottawattamie(?: County)?,\s*(Iowa|IA)\b|\bDouglas(?: County)?,\s*(Nebraska|NE)\b/i',
        ],
    ];

    private const UTAH = '/\bUtah\b/i';

    /**
     * Everything derived for one ancestor, as stored on the ancestors table.
     * Shared by the importer and `family-history:refresh-clues`.
     */
    public function clues(array $events, ?int $birthYear): array
    {
        $places = $this->places($events);
        $signals = $this->pioneerSignals($events, $birthYear);

        return [
            'church_places' => $places,
            'has_church_places' => (bool) $places,
            'pioneer_signals' => $signals,
            'likely_pioneer' => (bool) $signals,
        ];
    }

    /**
     * @param  array<int, array{year: ?int, date?: ?string, place: ?string}>  $events
     * @return array<int, array{key: string, label: string, from: int, to: int, proximity: string, where: string}>
     *         proximity is "in" (the town) or "near" (the surrounding counties); where is the matching place
     */
    public function places(array $events): array
    {
        $found = [];
        foreach ($events as $event) {
            if (! $event['year'] || ! $event['place']) {
                continue;
            }
            // "FROM 1839 TO 1846" covers the whole span, not just 1839.
            preg_match_all('/\b\d{4}\b/', (string) ($event['date'] ?? ''), $m);
            $years = array_map('intval', $m[0]) ?: [$event['year']];
            $start = min($years);
            $end = max($years);
            foreach (self::PLACES as $key => $site) {
                if ($end < $site['from'] || $start > $site['to']) {
                    continue;
                }
                $proximity = match (true) {
                    (bool) preg_match($site['town'], $event['place']) => 'in',
                    (bool) preg_match($site['near'], $event['place']) => 'near',
                    default => null,
                };
                if (! $proximity) {
                    continue;
                }
                $from = max($start, $site['from']);
                $to = min($end, $site['to']);
                $previous = $found[$key] ?? null;
                // "In" beats "near"; the place shown is the first match at the closer level.
                $closer = ! $previous || ($proximity === 'in' && $previous['proximity'] === 'near');
                $found[$key] = [
                    'key' => $key,
                    'label' => $site['label'],
                    'from' => min($previous['from'] ?? $from, $from),
                    'to' => max($previous['to'] ?? $to, $to),
                    'proximity' => $closer ? $proximity : $previous['proximity'],
                    'where' => $closer ? $event['place'] : $previous['where'],
                ];
            }
        }

        // Keep the sites' chronological order rather than the events'.
        return array_values(array_filter(array_map(fn ($key) => $found[$key] ?? null, array_keys(self::PLACES))));
    }

    /**
     * Reasons to think this person crossed the plains, most convincing first.
     *
     * @param  array<int, array{label: string, year: ?int, place: ?string, detail: ?string}>  $events
     * @return array<int, string>
     */
    public function pioneerSignals(array $events, ?int $birthYear): array
    {
        $signals = [];

        foreach ($events as $event) {
            $text = trim(($event['label'] ?? '').' '.($event['detail'] ?? ''));
            if ($event['year'] >= self::PIONEER_FROM && $event['year'] <= self::PIONEER_TO
                && preg_match('/\bCompany\b/i', $text)) {
                $signals[] = $this->arrivalPhrase(trim($event['detail'] ?? $text)).' ('.$event['year'].')';
            }
        }

        foreach ($this->places($events) as $place) {
            if ($place['key'] === 'winter_quarters') {
                $signals[] = 'At '.$place['label'].' ('.$this->span($place).') on the way west';
            }
        }

        // Somewhere else first, then Utah, with the move before the railroad.
        $dated = array_values(array_filter($events, fn ($e) => $e['year'] && $e['place']));
        usort($dated, fn ($a, $b) => $a['year'] <=> $b['year']);
        $lastElsewhere = null;
        $firstUtah = null;
        foreach ($dated as $event) {
            if (preg_match(self::UTAH, $event['place'])) {
                if ($lastElsewhere && $event['year'] >= self::PIONEER_FROM) {
                    $firstUtah = $event;
                    break;
                }
            } else {
                $lastElsewhere = $event;
            }
        }
        if ($firstUtah && $lastElsewhere['year'] <= self::PIONEER_TO
            && (! $birthYear || $birthYear <= self::PIONEER_TO)) {
            $signals[] = sprintf(
                'In %s (%d), then Utah (%d)',
                $this->shortPlace($lastElsewhere['place']),
                $lastElsewhere['year'],
                $firstUtah['year'],
            );
        }

        return array_values(array_unique($signals));
    }

    /**
     * When they reached Utah, and with which company if the tree says: an
     * arrival naming a company within the pioneer years, else the first
     * Utah event from 1847 on.
     *
     * @param  array<int, array{label: string, year: ?int, place: ?string, detail: ?string}>  $events
     * @return array{year: int, company: ?string}|null
     */
    public function arrival(array $events): ?array
    {
        foreach ($events as $event) {
            $text = trim(($event['label'] ?? '').' '.($event['detail'] ?? ''));
            if ($event['year'] >= self::PIONEER_FROM && $event['year'] <= self::PIONEER_TO
                && preg_match('/\bCompany\b/i', $text)) {
                return ['year' => $event['year'], 'company' => trim($event['detail'] ?? $text)];
            }
        }

        $utah = array_filter($events, fn ($e) => $e['year'] >= self::PIONEER_FROM && $e['place'] && preg_match(self::UTAH, $e['place']));
        $years = array_column($utah, 'year');

        return $years ? ['year' => min($years), 'company' => null] : null;
    }

    /**
     * Trees phrase arrivals freely: "Heber C. Kimball Company" or "Traveled
     * to Utah with the … Company." Only the bare company name gets a lead-in.
     */
    private function arrivalPhrase(string $detail): string
    {
        $detail = rtrim($detail, ". ");

        return preg_match('/^(travel|arriv|came|cross|emigrat|sail|walk|journey|left|went|immigrat)/i', $detail)
            ? ucfirst($detail)
            : 'Arrived with '.$detail;
    }

    private function span(array $place): string
    {
        return $place['from'] === $place['to'] ? (string) $place['from'] : $place['from'].'–'.$place['to'];
    }

    /** "Nauvoo, Hancock, Illinois, United States" → "Nauvoo, Illinois". */
    private function shortPlace(string $place): string
    {
        $parts = array_map('trim', explode(',', $place));
        $parts = array_values(array_filter($parts, fn ($p) => $p !== '' && ! in_array($p, ['United States', 'USA', 'United States of America'], true)));

        return count($parts) > 2 ? $parts[0].', '.end($parts) : implode(', ', $parts);
    }
}
