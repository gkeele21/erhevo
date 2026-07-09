<?php

namespace App\Console\Commands;

use App\Models\GeneralConference;
use App\Models\GeneralConferenceSession;
use App\Models\Talk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportPndrSessions extends Command
{
    protected $signature = 'talks:import-pndr-sessions
                            {conference : A conference slug like "april-2026", or "all"}
                            {--sleep=0.5 : Seconds to pause between requests}';

    protected $description = 'Set talks\' general_conference_session_id by parsing the session groupings on pndr.me conference pages.';

    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    private const MONTHS = [1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april', 5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august', 9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december'];

    public function handle(): int
    {
        $conferences = $this->argument('conference') === 'all'
            ? GeneralConference::orderBy('year')->orderBy('month')->get()
                ->map(fn ($c) => strtolower($c->month) . '-' . $c->year)->all()
            : [$this->argument('conference')];

        $usleep = (int) round(((float) $this->option('sleep')) * 1_000_000);
        $total = 0;
        $unmatched = [];

        foreach ($conferences as $i => $conf) {
            if ($i > 0 && $usleep > 0) {
                usleep($usleep);
            }
            $total += $this->importConference($conf, $unmatched);
        }

        $this->info("Done. Set session on {$total} talk(s).");
        if ($unmatched) {
            arsort($unmatched);
            $this->warn(count($unmatched) . ' unmatched session heading(s):');
            foreach (array_slice($unmatched, 0, 15, true) as $h => $n) {
                $this->line("  {$h} ({$n})");
            }
        }

        return self::SUCCESS;
    }

    private function importConference(string $conf, array &$unmatched): int
    {
        if (! preg_match('/^([a-z]+)-(\d{4})$/i', $conf, $mm)) {
            return 0;
        }
        $monthName = strtolower($mm[1]);
        $year = (int) $mm[2];
        $monthNum = array_search($monthName, self::MONTHS, true);
        if (! $monthNum) {
            return 0;
        }

        // Sessions for this conference, keyed by session-type name.
        $sessions = GeneralConferenceSession::with('sessionType')
            ->whereHas('conference', fn ($q) => $q->where('year', $year)->where('month', $monthName))
            ->get()
            ->keyBy(fn ($s) => $s->sessionType?->name);
        if ($sessions->isEmpty()) {
            return 0;
        }

        $response = Http::withHeaders(['User-Agent' => self::UA])->timeout(30)
            ->get("https://www.pndr.me/conferences/{$conf}?lang=en");
        if (! $response->successful()) {
            $this->warn("{$conf}: HTTP {$response->status()}");
            return 0;
        }

        // Walk the page in order: session headers set the current session; each
        // talk link beneath a header belongs to it.
        preg_match_all('#<h2[^>]*>([^<]*Session[^<]*)</h2>|/talk/([^?"]+)\?#', $response->body(), $tokens, PREG_SET_ORDER);

        $currentType = null;
        $seen = [];
        $count = 0;

        foreach ($tokens as $t) {
            if (! empty($t[1])) {
                $currentType = $this->matchType($t[1]);
                if ($currentType === null) {
                    $unmatched[trim(strip_tags($t[1]))] = ($unmatched[trim(strip_tags($t[1]))] ?? 0) + 1;
                }
                continue;
            }

            $slug = $t[2] ?? '';
            if ($slug === '' || $currentType === null || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;

            $session = $sessions->get($currentType);
            if (! $session) {
                continue;
            }

            $url = sprintf('https://www.churchofjesuschrist.org/study/general-conference/%d/%02d/%s?lang=eng', $year, $monthNum, $slug);
            $count += Talk::where('url', $url)->update(['general_conference_session_id' => $session->id]);
        }

        $this->line("  {$conf}: {$count} talk(s)");

        return $count;
    }

    /** Map a pndr session heading (e.g. "Saturday Morning Session") to our session-type name. */
    private function matchType(string $heading): ?string
    {
        $h = trim(preg_replace('/\s*session\s*$/i', '', trim(strip_tags($heading))));
        $lower = strtolower($h);

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
}
