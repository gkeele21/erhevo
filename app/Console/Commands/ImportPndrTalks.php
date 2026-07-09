<?php

namespace App\Console\Commands;

use App\Models\Source;
use App\Models\Talk;
use App\Models\TalkType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportPndrTalks extends Command
{
    protected $signature = 'talks:import-pndr
                            {conference : A conference slug like "april-2026", or "all" for every seeded conference}
                            {--sleep=1 : Seconds to pause between requests when importing "all"}';

    protected $description = 'Import GC talk METADATA (title, speaker, date, canonical URL) by parsing pndr.me conference pages. No body text stored.';

    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    private const MONTHS = [1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april', 5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august', 9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december'];

    private ?int $sourceId = null;
    private ?int $typeId = null;

    public function handle(): int
    {
        $this->sourceId = Source::where('slug', 'general-conference')->value('id');
        $this->typeId = TalkType::where('slug', 'conference-talk')->value('id');

        $conferences = $this->argument('conference') === 'all'
            ? \App\Models\GeneralConference::orderBy('year')->orderBy('month')->get()
                ->map(fn ($c) => (self::MONTHS[(int) $c->month] ?? strtolower($c->month)) . '-' . $c->year)
                ->all()
            : [$this->argument('conference')];

        $grand = 0;
        foreach ($conferences as $i => $conf) {
            if ($i > 0 && (int) $this->option('sleep') > 0) {
                sleep((int) $this->option('sleep'));
            }
            $grand += $this->importConference($conf);
        }

        $this->info("Done. Imported/updated {$grand} talk(s) across " . count($conferences) . ' conference(s).');

        return self::SUCCESS;
    }

    private function importConference(string $conf): int
    {
        [$year, $monthNum] = $this->parseConference($conf);
        if (! $year) {
            $this->warn("Skipping '{$conf}': can't parse year/month.");
            return 0;
        }

        $response = Http::withHeaders(['User-Agent' => self::UA])
            ->timeout(30)
            ->get("https://www.pndr.me/conferences/{$conf}?lang=en");

        if (! $response->successful()) {
            $this->warn("{$conf}: HTTP {$response->status()} — skipped.");
            return 0;
        }

        $talks = $this->parseTalks($response->body());
        if (! $talks) {
            $this->warn("{$conf}: no talks parsed.");
            return 0;
        }

        $count = 0;
        foreach ($talks as $order => $t) {
            $url = sprintf('https://www.churchofjesuschrist.org/study/general-conference/%d/%02d/%s?lang=eng', $year, $monthNum, $t['slug']);

            Talk::updateOrCreate(
                ['url' => $url],
                array_filter([
                    'title' => $t['title'],
                    'speaker_name' => $t['speaker'],
                    'speaker_title' => $t['prefix'],
                    'talk_date' => $t['date'] ?: sprintf('%04d-%02d-01', $year, $monthNum),
                    'source_id' => $this->sourceId,
                    'talk_type_id' => $this->typeId,
                    'display_order' => $order + 1,
                ], fn ($v) => $v !== null && $v !== '')
            );
            $count++;
        }

        $this->line("  {$conf}: {$count} talk(s)");

        return $count;
    }

    /** "april-2026" => [2026, 4]. */
    private function parseConference(string $conf): array
    {
        if (! preg_match('/^([a-z]+)-(\d{4})$/i', trim($conf), $m)) {
            return [null, null];
        }
        $month = array_search(strtolower($m[1]), self::MONTHS, true);

        return $month ? [(int) $m[2], $month] : [null, null];
    }

    /**
     * Parse talk cards: each is <a href="/talk/{slug}"> with an <h3> title and a
     * muted div "Speaker · Date".
     */
    private function parseTalks(string $html): array
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xp = new \DOMXPath($doc);

        $talks = [];
        $seen = [];
        foreach ($xp->query("//a[starts-with(@href, '/talk/')]") as $a) {
            if (! preg_match('#/talk/([^?"]+)#', $a->getAttribute('href'), $m)) {
                continue;
            }
            $slug = $m[1];
            if (isset($seen[$slug])) {
                continue;
            }

            $h3 = $xp->query('.//h3', $a)->item(0);
            $muted = $xp->query(".//div[contains(@class,'text-text-muted')]", $a)->item(0);
            if (! $h3 || ! $muted) {
                continue;
            }

            $title = trim($h3->textContent);
            [$prefix, $speaker, $date] = $this->parseByline(trim($muted->textContent));
            if ($title === '') {
                continue;
            }

            $seen[$slug] = true;
            $talks[] = compact('slug', 'title', 'prefix', 'speaker', 'date');
        }

        return $talks;
    }

    /** "Elder Clark G. Gilbert · April 1, 2026" => ["Elder", "Clark G. Gilbert", "2026-04-01"]. */
    private function parseByline(string $text): array
    {
        $date = null;
        $who = $text;
        if (preg_match('/^(.*?)\s*·\s*(.+)$/u', $text, $m)) {
            $who = trim($m[1]);
            $ts = strtotime(trim($m[2]));
            $date = $ts ? date('Y-m-d', $ts) : null;
        }

        $prefix = null;
        if (preg_match('/^(President|Elder|Sister|Bishop|Brother)\s+(.+)$/u', $who, $m)) {
            $prefix = $m[1];
            $who = trim($m[2]);
        }

        return [$prefix, $who ?: null, $date];
    }
}
