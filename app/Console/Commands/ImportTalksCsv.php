<?php

namespace App\Console\Commands;

use App\Models\ChurchCalling;
use App\Models\Source;
use App\Models\Talk;
use App\Models\TalkType;
use Illuminate\Console\Command;

class ImportTalksCsv extends Command
{
    protected $signature = 'talks:import-csv {file : Path to a CSV file}';

    protected $description = 'Import General Conference talk METADATA from a flat CSV (title, speaker, calling, year/month, url). Idempotent by URL. Stores no talk body text.';

    /**
     * CSV headers (order-independent, case-insensitive):
     *   title            (required)
     *   speaker          — speaker name (e.g. "Russell M. Nelson")
     *   speaker_title    — optional descriptive title
     *   calling_id       — a church_callings id, OR
     *   calling          — a church calling name to look up (e.g. "Apostle")
     *   organization     — church org name, to disambiguate the calling
     *   year, month      — GC session; builds talk_date (YYYY-MM-01) if `date` absent
     *   date             — explicit date (YYYY-MM-DD or "6 Oct 2024"); overrides year/month
     *   url              — canonical source link (idempotency key — include it!)
     *   summary          — OPTIONAL short blurb only; do NOT paste full talk text
     *   source           — source slug (default: general-conference)
     *   talk_type        — talk type slug (default: conference-talk)
     */
    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $defaultSourceId = Source::where('slug', 'general-conference')->value('id');
        $defaultTypeId = TalkType::where('slug', 'conference-talk')->value('id');

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
                continue;
            }

            $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));
            $title = trim((string) ($data['title'] ?? ''));
            if ($title === '') {
                $errors[] = "Line {$line}: missing title.";
                continue;
            }

            $url = $this->clean($data['url'] ?? null);

            $attributes = array_filter([
                'title' => $title,
                'speaker_name' => $this->clean($data['speaker'] ?? null),
                'speaker_title' => $this->clean($data['speaker_title'] ?? null),
                'church_calling_id' => $this->resolveCalling($data),
                'talk_date' => $this->resolveDate($data),
                'summary' => $this->clean($data['summary'] ?? null),
                'url' => $url,
                'source_id' => $this->resolveSource($data['source'] ?? null) ?? $defaultSourceId,
                'talk_type_id' => $this->resolveTalkType($data['talk_type'] ?? null) ?? $defaultTypeId,
            ], fn ($v) => $v !== null);

            $match = $url
                ? ['url' => $url]
                : ['title' => $title, 'talk_date' => $attributes['talk_date'] ?? null, 'speaker_name' => $attributes['speaker_name'] ?? null];

            Talk::updateOrCreate($match, $attributes);
            $imported++;
        }

        fclose($handle);

        foreach ($errors as $e) {
            $this->warn($e);
        }
        $this->info("Imported/updated {$imported} talk(s)." . ($errors ? ' ' . count($errors) . ' row(s) skipped.' : ''));

        return self::SUCCESS;
    }

    private function resolveCalling(array $data): ?int
    {
        if (! empty($data['calling_id'])) {
            return (int) $data['calling_id'];
        }

        $name = trim((string) ($data['calling'] ?? ''));
        if ($name === '') {
            return null;
        }

        $org = trim((string) ($data['organization'] ?? ''));

        return ChurchCalling::where('name', $name)
            ->when($org !== '', fn ($q) => $q->whereHas('organization', fn ($o) => $o->where('name', $org)))
            ->value('id');
    }

    private function resolveSource(?string $slug): ?int
    {
        $slug = trim((string) $slug);
        return $slug === '' ? null : Source::where('slug', $slug)->value('id');
    }

    private function resolveTalkType(?string $slug): ?int
    {
        $slug = trim((string) $slug);
        return $slug === '' ? null : TalkType::where('slug', $slug)->value('id');
    }

    /** Explicit date wins; otherwise build the session date from year + month. */
    private function resolveDate(array $data): ?string
    {
        $date = trim((string) ($data['date'] ?? ''));
        if ($date !== '') {
            $ts = strtotime($date);
            return $ts ? date('Y-m-d', $ts) : null;
        }

        $year = (int) ($data['year'] ?? 0);
        $month = (int) ($data['month'] ?? 0);

        return ($year && $month) ? sprintf('%04d-%02d-01', $year, $month) : null;
    }

    private function clean($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
