<?php

namespace App\Services\FamilyHistory;

/**
 * Streams a GEDCOM 5.5.x file into compact individual/family records.
 *
 * Only what Family History needs is kept: names, sex, FamilySearch IDs
 * (`_FSFTID`, as written by Ancestral Quest and RootsMagic), family links,
 * and dated/placed events. Nothing else survives the parse, which keeps a
 * 25k-person tree comfortably in memory.
 */
class GedcomParser
{
    /** Level-1 individual tags kept as events. */
    private const EVENT_TAGS = [
        'BIRT', 'CHR', 'CHRA', 'BAPM', 'DEAT', 'BURI', 'CREM', 'RESI', 'CENS',
        'IMMI', 'EMIG', 'NATU', 'OCCU', 'RELI', 'EVEN', 'BAPL', 'CONL', 'ENDL',
        'SLGC', 'PROB', 'WILL',
    ];

    /**
     * @return array{source: ?string, individuals: array<string, array>, families: array<string, array>}
     */
    public function parse(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new GedcomException('The file could not be read.');
        }

        $source = null;
        $individuals = [];
        $families = [];

        $record = null;      // current level-0 record type: HEAD, INDI, FAM, or null
        $xref = null;
        $event = null;       // index of the level-1 event being filled
        $level1 = null;      // current level-1 tag
        $sawHeader = false;

        try {
            while (($line = fgets($handle)) !== false) {
                $line = rtrim($line, "\r\n");
                if (! $sawHeader) {
                    $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
                }
                if (! preg_match('/^\s*(\d+)\s+(?:(@[^@]+@)\s+)?(\S+)(?: (.*))?$/', $line, $m)) {
                    continue;
                }
                $level = (int) $m[1];
                $tag = strtoupper($m[3]);
                $value = $m[4] ?? '';

                if ($level === 0) {
                    $sawHeader = $sawHeader || $tag === 'HEAD';
                    $record = $tag;
                    $xref = $m[2] ?: null;
                    $event = null;
                    $level1 = null;
                    if ($record === 'INDI' && $xref) {
                        $individuals[$xref] = [
                            'name' => null, 'given' => null, 'surname' => null, 'sex' => null,
                            'fs_id' => null, 'famc' => [], 'fams' => [], 'events' => [],
                        ];
                    } elseif ($record === 'FAM' && $xref) {
                        $families[$xref] = ['husb' => null, 'wife' => null, 'chil' => [], 'events' => []];
                    }

                    continue;
                }

                if (! $sawHeader) {
                    throw new GedcomException('This does not look like a GEDCOM file.');
                }

                if ($record === 'HEAD') {
                    if ($level === 1) {
                        $level1 = $tag;
                    }
                    if ($level === 1 && $tag === 'SOUR') {
                        $source = $value;
                    } elseif ($level === 2 && $tag === 'NAME' && $level1 === 'SOUR') {
                        $source = $value;
                    }

                    continue;
                }

                if ($record === 'INDI' && $xref) {
                    $person = &$individuals[$xref];
                    if ($level === 1) {
                        $level1 = $tag;
                        $event = null;
                        match (true) {
                            $tag === 'NAME' && $person['name'] === null => $this->setName($person, $value),
                            $tag === 'SEX' => $person['sex'] = strtoupper(substr($value, 0, 1)) ?: null,
                            $tag === '_FSFTID' => $person['fs_id'] = trim($value) ?: null,
                            $tag === 'FAMC' => $person['famc'][] = ['xref' => $value, 'primary' => false, 'pedi' => null],
                            $tag === 'FAMS' => $person['fams'][] = $value,
                            in_array($tag, self::EVENT_TAGS, true) => $event = array_push($person['events'], [
                                'tag' => $tag, 'type' => null, 'date' => null, 'place' => null, 'value' => $value !== '' ? $value : null,
                            ]) - 1,
                            default => null,
                        };
                    } elseif ($level === 2 && $event !== null) {
                        $this->fillEvent($person['events'][$event], $tag, $value);
                    } elseif ($level === 2 && $level1 === 'NAME' && $tag === 'GIVN' && $person['given'] === null) {
                        $person['given'] = $value;
                    } elseif ($level === 2 && $level1 === 'FAMC' && $person['famc']) {
                        // FamilySearch's preferred parents, and birth vs adopted/foster.
                        $link = &$person['famc'][array_key_last($person['famc'])];
                        match ($tag) {
                            '_PRIMARY' => $link['primary'] = strtoupper($value) === 'Y',
                            'PEDI' => $link['pedi'] = strtolower($value),
                            default => null,
                        };
                        unset($link);
                    }
                    unset($person);

                    continue;
                }

                if ($record === 'FAM' && $xref) {
                    $family = &$families[$xref];
                    if ($level === 1) {
                        $event = null;
                        match ($tag) {
                            'HUSB' => $family['husb'] = $value,
                            'WIFE' => $family['wife'] = $value,
                            'CHIL' => $family['chil'][] = $value,
                            'MARR' => $event = array_push($family['events'], [
                                'tag' => $tag, 'type' => null, 'date' => null, 'place' => null, 'value' => null,
                            ]) - 1,
                            default => null,
                        };
                    } elseif ($level === 2 && $event !== null) {
                        $this->fillEvent($family['events'][$event], $tag, $value);
                    }
                    unset($family);
                }
            }
        } finally {
            fclose($handle);
        }

        if (! $sawHeader) {
            throw new GedcomException('This does not look like a GEDCOM file.');
        }
        if (! $individuals) {
            throw new GedcomException('No people were found in this file.');
        }

        return ['source' => $source, 'individuals' => $individuals, 'families' => $families];
    }

    private function setName(array &$person, string $value): void
    {
        // "Grant Austin /Keele/ Jr." → surname between slashes.
        $surname = preg_match('#/([^/]*)/#', $value, $m) ? trim($m[1]) : null;
        $person['surname'] = $surname !== '' ? $surname : null;
        $person['name'] = trim(preg_replace('/\s+/', ' ', str_replace('/', '', $value))) ?: null;
        $person['given'] = trim(preg_replace('#/.*$#', '', $value)) ?: null;
    }

    private function fillEvent(array &$event, string $tag, string $value): void
    {
        match ($tag) {
            'DATE' => $event['date'] = $value !== '' ? $value : null,
            'PLAC' => $event['place'] = $value !== '' ? $value : null,
            'TYPE' => $event['type'] = trim($value) !== '' ? trim($value) : null,
            'TEMP' => $event['temple'] = $value,
            default => null,
        };
    }
}
