<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\ChurchCalling;
use Illuminate\Console\Command;

class ImportAuthors extends Command
{
    protected $signature = 'authors:import {file : Path to a CSV file}';

    protected $description = 'Import authors + their callings from a CSV. Idempotent (dedupes authors by name, ongoing callings by author+calling).';

    /**
     * CSV headers (order-independent, case-insensitive):
     *   name          (required) — e.g. "Dieter F. Uchtdorf"
     *   calling_id    — a church_callings id (unambiguous), OR
     *   organization + calling — matched by org name + calling name
     *   start_date    (optional) — YYYY-MM-DD
     *   end_date      (optional) — YYYY-MM-DD; blank = currently held
     *   primary       (optional) — 1/0 or true/false; default 1 for ongoing callings
     */
    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        if (! $header) {
            $this->error('CSV appears to be empty.');
            return self::FAILURE;
        }
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $imported = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // skip blank lines
            }

            $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));
            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                $errors[] = "Line {$line}: missing name.";
                continue;
            }

            $calling = $this->resolveCalling($data);
            if (! $calling) {
                $errors[] = "Line {$line}: could not resolve calling for “{$name}”.";
                continue;
            }

            $start = $this->cleanDate($data['start_date'] ?? null);
            $end = $this->cleanDate($data['end_date'] ?? null);

            $author = Author::findOrCreateByName($name);

            if ($end) {
                // A past calling — recorded as history, never primary.
                $author->callings()->firstOrCreate([
                    'church_calling_id' => $calling->id,
                    'start_date' => $start,
                    'end_date' => $end,
                ]);
            } else {
                $primary = $this->toBool($data['primary'] ?? true);
                $author->assignCalling($calling->id, $start, $primary);
            }

            $imported++;
        }

        fclose($handle);

        foreach ($errors as $e) {
            $this->warn($e);
        }
        $this->info("Imported {$imported} calling assignment(s)." . ($errors ? ' ' . count($errors) . ' row(s) skipped.' : ''));

        return self::SUCCESS;
    }

    private function resolveCalling(array $data): ?ChurchCalling
    {
        if (! empty($data['calling_id'])) {
            return ChurchCalling::find((int) $data['calling_id']);
        }

        $calling = trim((string) ($data['calling'] ?? ''));
        $org = trim((string) ($data['organization'] ?? ''));
        if ($calling === '') {
            return null;
        }

        return ChurchCalling::where('name', $calling)
            ->when($org !== '', fn ($q) => $q->whereHas('organization', fn ($q2) => $q2->where('name', $org)))
            ->first();
    }

    private function cleanDate(?string $value): ?string
    {
        $value = trim((string) $value);
        // Treat "ongoing" markers as no end date (currently held).
        if ($value === '' || in_array(strtolower($value), ['present', 'current', 'ongoing', '-', '—', 'n/a'], true)) {
            return null;
        }

        // Accept flexible formats ("5 Apr 2008", "2008-04-05", ...).
        $ts = strtotime($value);

        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function toBool($value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y'], true) || $value === true;
    }
}
