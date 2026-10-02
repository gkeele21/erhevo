<?php

namespace App\Services\FamilyHistory;

/**
 * Early Church history clues read from an ancestor's dated events: gathering
 * places they lived in during the years the Saints were there, and whether
 * they look like a pioneer who crossed the plains before the railroad
 * (1847–1869). These are hints for research, not conclusions — the user can
 * confirm or overrule them.
 */
class ChurchHistory
{
    public const PIONEER_FROM = 1847;
    public const PIONEER_TO = 1869;

    /** Gathering places, matched against the place string within the given years. */
    public const PLACES = [
        'kirtland' => [
            'label' => 'Kirtland, Ohio',
            'from' => 1831, 'to' => 1838,
            'pattern' => '/\bKirtland\b/i',
        ],
        'missouri' => [
            'label' => 'Missouri (Jackson, Clay & Caldwell counties)',
            'from' => 1831, 'to' => 1839,
            'pattern' => '/\b(Far West|Adam-ondi-Ahman|Independence|Liberty|Jackson|Clay|Caldwell|Daviess)\b.*\bMissouri\b/i',
        ],
        'nauvoo' => [
            'label' => 'Nauvoo, Illinois',
            'from' => 1839, 'to' => 1846,
            'pattern' => '/\b(Nauvoo|Commerce, Hancock)\b/i',
        ],
        'winter_quarters' => [
            'label' => 'Winter Quarters & Council Bluffs',
            'from' => 1846, 'to' => 1852,
            'pattern' => '/\b(Winter Quarters|Florence, Douglas, Nebraska|Kanesville|Council Bluffs|Pottawattamie, Iowa)\b/i',
        ],
    ];

    private const UTAH = '/\bUtah\b/i';

    /**
     * @param  array<int, array{year: ?int, date?: ?string, place: ?string}>  $events
     * @return array<int, array{key: string, label: string, from: int, to: int}>
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
            foreach (self::PLACES as $key => $place) {
                if ($end < $place['from'] || $start > $place['to']) {
                    continue;
                }
                if (! preg_match($place['pattern'], $event['place'])) {
                    continue;
                }
                $from = max($start, $place['from']);
                $to = min($end, $place['to']);
                $found[$key] = [
                    'key' => $key,
                    'label' => $place['label'],
                    'from' => min($found[$key]['from'] ?? $from, $from),
                    'to' => max($found[$key]['to'] ?? $to, $to),
                ];
            }
        }

        return array_values($found);
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
                $signals[] = 'Arrived with '.trim($event['detail'] ?? $text).' ('.$event['year'].')';
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
