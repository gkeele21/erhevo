<?php

namespace App\Services\FamilyHistory;

/**
 * Just enough GEDCOM date handling for sorting and comparing: "31 MAR 1974",
 * "MAR 1974", "ABT 1850", "BET 1850 AND 1855", "1850/51" and friends. The
 * first date in the phrase wins; qualifiers are ignored.
 */
class GedcomDate
{
    private const MONTHS = [
        'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6,
        'JUL' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12,
    ];

    public static function year(?string $date): ?int
    {
        return self::parts($date)[0] ?? null;
    }

    /** "YYYY-MM-DD" with unknown month/day as 00, or null. */
    public static function sortKey(?string $date): ?string
    {
        $parts = self::parts($date);
        if (! $parts) {
            return null;
        }
        [$year, $month, $day] = $parts;

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /** @return array{0:int,1:int,2:int}|null year, month (0 if unknown), day (0 if unknown) */
    private static function parts(?string $date): ?array
    {
        if ($date === null || $date === '') {
            return null;
        }
        $upper = strtoupper($date);
        $months = implode('|', array_keys(self::MONTHS));

        if (preg_match("/\\b(?:(\\d{1,2})\\s+)?(?:($months)[A-Z]*\\.?\\s+)?(\\d{3,4})\\b/", $upper, $m)) {
            $year = (int) $m[3];
            $month = ! empty($m[2]) ? self::MONTHS[$m[2]] : 0;
            $day = $month && ! empty($m[1]) ? (int) $m[1] : 0;

            return [$year, $month, $day];
        }

        return null;
    }
}
