<?php

namespace App\Console\Commands;

use App\Models\GeneralConferenceSession;
use App\Models\Talk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FixPndrTalkUrls extends Command
{
    protected $signature = 'talks:fix-pndr-urls
                            {--sleep=0.4 : Seconds between requests}
                            {--limit=0 : Only process N talks}';

    protected $description = 'Fix each talk\'s url to the canonical churchofjesuschrist.org URL (from its pndr page), and fill a missing session from the page byline.';

    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
    private const MONTHS = [1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april', 5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august', 9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december'];

    private array $sessionCache = [];

    public function handle(): int
    {
        $query = Talk::whereNotNull('url');
        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $usleep = (int) round(((float) $this->option('sleep')) * 1_000_000);
        $processed = 0;
        $urlsFixed = 0;
        $sessionsSet = 0;
        $skipped = 0;

        foreach ($query->cursor() as $talk) {
            $slug = basename(parse_url($talk->url, PHP_URL_PATH) ?: '');
            if ($slug === '') {
                continue;
            }
            $processed++;

            $response = Http::withHeaders(['User-Agent' => self::UA])->timeout(30)
                ->get("https://www.pndr.me/talk/{$slug}?lang=en");
            if (! $response->successful()) {
                $skipped++;
                if ($usleep > 0) {
                    usleep($usleep);
                }
                continue;
            }

            $body = $response->body();
            $changes = [];

            // Canonical church URL — accept only if the year matches this talk
            // (guards against grabbing a related talk's link).
            if (preg_match('#/study/general-conference/(\d{4})/(\d{2})/([a-z0-9-]+)#i', $body, $m)) {
                $talkYear = $talk->talk_date?->year;
                if (! $talkYear || (int) $m[1] === (int) $talkYear) {
                    $canonical = sprintf('https://www.churchofjesuschrist.org/study/general-conference/%s/%s/%s?lang=eng', $m[1], $m[2], $m[3]);
                    if ($talk->url !== $canonical) {
                        $changes['url'] = $canonical;
                        $urlsFixed++;
                    }
                }
            }

            // Fill a missing session from the byline ("… • Saturday Morning").
            if ($talk->general_conference_session_id === null && $talk->talk_date) {
                $sessionId = $this->resolveSession($body, $talk->talk_date->year, $talk->talk_date->month);
                if ($sessionId) {
                    $changes['general_conference_session_id'] = $sessionId;
                    $sessionsSet++;
                }
            }

            if ($changes) {
                $talk->update($changes);
            }
            if ($usleep > 0) {
                usleep($usleep);
            }
            if ($processed % 200 === 0) {
                $this->line("  …{$processed} processed, {$urlsFixed} urls fixed, {$sessionsSet} sessions set");
            }
        }

        $this->info("Processed {$processed}; urls fixed {$urlsFixed}; sessions set {$sessionsSet}; skipped {$skipped}.");

        return self::SUCCESS;
    }

    private function resolveSession(string $body, int $year, int $month): ?int
    {
        $name = $this->sessionFromByline($body);
        if (! $name) {
            return null;
        }
        $type = $this->matchType($name);
        if (! $type) {
            return null;
        }

        $key = "{$year}-{$month}-{$type}";
        if (! array_key_exists($key, $this->sessionCache)) {
            $monthName = self::MONTHS[$month] ?? null;
            $this->sessionCache[$key] = GeneralConferenceSession::whereHas('conference', fn ($q) => $q->where('year', $year)->where('month', $monthName))
                ->whereHas('sessionType', fn ($q) => $q->where('name', $type))
                ->value('id');
        }

        return $this->sessionCache[$key];
    }

    /** The talk-page byline "<office> • <Month Year> General Conference • <Session>" → the last segment. */
    private function sessionFromByline(string $html): ?string
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xp = new \DOMXPath($doc);

        foreach ($xp->query("//div[contains(@class,'text-text-muted')]") as $div) {
            $text = trim($div->textContent);
            if (str_contains($text, 'General Conference') && preg_match('/[•·]/u', $text)) {
                $parts = preg_split('/\s*[•·]\s*/u', $text);
                $last = trim(end($parts));
                if ($last !== '' && ! str_contains($last, 'General Conference')) {
                    return $last;
                }
            }
        }

        return null;
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
}
