<?php

namespace App\Console\Commands;

use App\Models\GeneralConferenceSession;
use App\Models\Talk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RekeyPndrTalks extends Command
{
    protected $signature = 'talks:rekey-pndr
                            {conference : A conference slug like "april-2026", or "all"}
                            {--sleep=0.3 : Seconds between talk-page requests}';

    protected $description = 'Re-key talks against pndr conference pages (match by year/month/title), set session from the page grouping, and fix each url to the canonical churchofjesuschrist.org link.';

    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
    private const MONTHS = [1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april', 5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august', 9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december'];

    public function handle(): int
    {
        $conferences = $this->argument('conference') === 'all'
            ? \App\Models\GeneralConference::orderBy('year')->orderBy('month')->get()
                ->map(fn ($c) => strtolower($c->month) . '-' . $c->year)->all()
            : [$this->argument('conference')];

        $urls = 0;
        $sessions = 0;
        $unmatched = 0;

        foreach ($conferences as $conf) {
            $this->importConference($conf, $urls, $sessions, $unmatched);
        }

        $this->info("Done. Urls fixed: {$urls}; sessions set: {$sessions}; unmatched cards: {$unmatched}.");

        return self::SUCCESS;
    }

    private function importConference(string $conf, int &$urls, int &$sessions, int &$unmatched): void
    {
        if (! preg_match('/^([a-z]+)-(\d{4})$/i', $conf, $mm)) {
            return;
        }
        $monthName = strtolower($mm[1]);
        $year = (int) $mm[2];
        $monthNum = array_search($monthName, self::MONTHS, true);
        if (! $monthNum) {
            return;
        }

        $ours = Talk::whereYear('talk_date', $year)->whereMonth('talk_date', $monthNum)->get()
            ->keyBy(fn (Talk $t) => $this->norm($t->title));
        if ($ours->isEmpty()) {
            return;
        }

        $sessionIds = GeneralConferenceSession::with('sessionType')
            ->whereHas('conference', fn ($q) => $q->where('year', $year)->where('month', $monthName))
            ->get()->keyBy(fn ($s) => $s->sessionType?->name);

        $resp = Http::withHeaders(['User-Agent' => self::UA])->timeout(30)->get("https://www.pndr.me/conferences/{$conf}?lang=en");
        if (! $resp->successful()) {
            $this->warn("{$conf}: HTTP {$resp->status()}");
            return;
        }

        $usleep = (int) round(((float) $this->option('sleep')) * 1_000_000);
        $count = 0;

        foreach ($this->parseCards($resp->body()) as $card) {
            $talk = $ours->get($this->norm($card['title']));
            if (! $talk) {
                $unmatched++;
                continue;
            }

            $changes = [];
            if ($card['sessionType'] && ($sid = $sessionIds->get($card['sessionType'])?->id)) {
                if ($talk->general_conference_session_id !== $sid) {
                    $changes['general_conference_session_id'] = $sid;
                    $sessions++;
                }
            }

            // Correct slug from the conference page → fetch the talk page → canonical url.
            $talkResp = Http::withHeaders(['User-Agent' => self::UA])->timeout(30)->get("https://www.pndr.me/talk/{$card['slug']}?lang=en");
            if ($talkResp->successful() && preg_match('#/study/general-conference/(\d{4})/(\d{2})/([a-z0-9-]+)#i', $talkResp->body(), $u) && (int) $u[1] === $year) {
                $canonical = sprintf('https://www.churchofjesuschrist.org/study/general-conference/%s/%s/%s?lang=eng', $u[1], $u[2], $u[3]);
                if ($talk->url !== $canonical) {
                    $changes['url'] = $canonical;
                    $urls++;
                }
            }

            if ($changes) {
                $talk->update($changes);
            }
            $count++;
            if ($usleep > 0) {
                usleep($usleep);
            }
        }

        $this->line("  {$conf}: {$count} talk(s) processed");
    }

    /** Parse each talk card: slug + title + the session heading it sits under. */
    private function parseCards(string $html): array
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xp = new \DOMXPath($doc);

        $cards = [];
        $seen = [];
        foreach ($xp->query("//a[starts-with(@href, '/talk/')]") as $a) {
            if (! preg_match('#/talk/([^?"]+)#', $a->getAttribute('href'), $m)) {
                continue;
            }
            $slug = $m[1];
            $h3 = $xp->query('.//h3', $a)->item(0);
            if (! $h3 || isset($seen[$slug])) {
                continue;
            }
            $title = trim($h3->textContent);
            if ($title === '') {
                continue;
            }
            $seen[$slug] = true;

            $h2 = $xp->query('preceding::h2[contains(., "Session")][1]', $a)->item(0);
            $cards[] = [
                'slug' => $slug,
                'title' => $title,
                'sessionType' => $h2 ? $this->matchType(trim($h2->textContent)) : null,
            ];
        }

        return $cards;
    }

    private function matchType(string $heading): ?string
    {
        $lower = strtolower(trim(preg_replace('/\s*session\s*$/i', '', $heading)));

        return match (true) {
            str_contains($lower, 'priesthood') => 'General Priesthood',
            str_contains($lower, 'women') => "General Women's",
            $lower === 'saturday morning' => 'Saturday Morning',
            $lower === 'saturday afternoon' => 'Saturday Afternoon',
            $lower === 'saturday evening' => 'Saturday Evening',
            $lower === 'sunday morning' => 'Sunday Morning',
            $lower === 'sunday afternoon' => 'Sunday Afternoon',
            default => null,
        };
    }

    private function norm(string $title): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $title)));
    }
}
