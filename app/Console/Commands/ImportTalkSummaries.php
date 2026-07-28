<?php

namespace App\Console\Commands;

use App\Models\Talk;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportTalkSummaries extends Command
{
    protected $signature = 'talks:import-summaries
        {--limit=0 : Only process this many talks (0 = all)}
        {--force : Re-fetch talks that already have a summary}
        {--delay=300 : Milliseconds to wait between requests}';

    protected $description = 'Fill in talk summaries by fetching each talk page and extracting its kicker (or meta description)';

    public function handle(): int
    {
        $query = Talk::whereNotNull('url')
            ->when(! $this->option('force'), fn ($q) => $q->whereNull('summary'))
            ->orderBy('id');

        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $talks = $query->get(['id', 'title', 'url']);

        if ($talks->isEmpty()) {
            $this->info('No talks need summaries.');

            return Command::SUCCESS;
        }

        $this->info("Fetching summaries for {$talks->count()} talks…");

        $imported = 0;
        $missing = 0;
        $failed = 0;
        $urlsFixed = 0;
        $delay = max(0, (int) $this->option('delay')) * 1000;

        $bar = $this->output->createProgressBar($talks->count());

        foreach ($talks as $talk) {
            try {
                $response = $this->fetch($talk->url);

                if (! $response->successful()) {
                    $failed++;
                    $bar->advance();
                    usleep($delay);

                    continue;
                }

                $summary = $this->extractSummary($response->body());

                // Some talks store a slug the church site doesn't know, which
                // redirects to the conference index. That index lists every
                // talk, so recover the real URL by title and retry.
                if (! $summary && ($fixedUrl = $this->resolveUrlFromIndex($talk, $response->body()))) {
                    $talk->update(['url' => $fixedUrl]);
                    $urlsFixed++;

                    usleep($delay);
                    $retry = $this->fetch($fixedUrl);
                    $summary = $retry->successful() ? $this->extractSummary($retry->body()) : null;
                }

                if ($summary) {
                    $talk->update(['summary' => $summary]);
                    $imported++;
                } else {
                    $missing++;
                }
            } catch (\Throwable $e) {
                $failed++;
            }

            $bar->advance();
            usleep($delay);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done: {$imported} summaries imported, {$urlsFixed} broken URLs fixed, {$missing} pages had none, {$failed} requests failed.");

        return Command::SUCCESS;
    }

    protected function fetch(string $url)
    {
        return Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) ErhevoBot/1.0',
        ])->timeout(20)->get($url);
    }

    /**
     * Given the conference-index HTML a bad talk URL redirected to, find the
     * talk's real link by matching the link text (title + speaker) against
     * the talk title. Returns an absolute URL, or null if no link matches.
     */
    protected function resolveUrlFromIndex(Talk $talk, string $html): ?string
    {
        if (! preg_match('#/study/general-conference/(\d{4})/(\d{2})/#', $talk->url, $m)) {
            return null;
        }

        $prefix = "/study/general-conference/{$m[1]}/{$m[2]}/";
        $wanted = $this->normalizeTitle($talk->title);

        if ($wanted === '') {
            return null;
        }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        if (! $doc->loadHTML('<?xml encoding="UTF-8">' . $html)) {
            return null;
        }

        foreach ((new DOMXPath($doc))->query('//a[contains(@href, "' . $prefix . '")]') as $link) {
            $text = $this->normalizeTitle($link->textContent);

            if ($text !== '' && str_starts_with($text, $wanted)) {
                $href = strtok($link->getAttribute('href'), '?');

                return "https://www.churchofjesuschrist.org{$href}?lang=eng";
            }
        }

        return null;
    }

    /** Lowercase alphanumerics only, so typographic quotes/dashes don't block matches. */
    protected function normalizeTitle(string $title): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(
            transliterator_transliterate('Any-Latin; Latin-ASCII', $title) ?: $title
        ));
    }

    /**
     * The kicker is the talk's own highlighted excerpt line; the meta
     * description (a third-person editorial summary) is the fallback for
     * older talks whose pages don't have one.
     */
    protected function extractSummary(string $html): ?string
    {
        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        // Hint the encoding — the pages are UTF-8 but loadHTML defaults to Latin-1.
        if (! $doc->loadHTML('<?xml encoding="UTF-8">' . $html)) {
            return null;
        }

        $xpath = new DOMXPath($doc);

        $queries = [
            '//p[contains(concat(" ", normalize-space(@class), " "), " kicker ") or @id="kicker"]',
            '//meta[@name="description"]/@content',
            '//meta[@property="og:description"]/@content',
        ];

        foreach ($queries as $query) {
            foreach ($xpath->query($query) as $node) {
                $text = trim(preg_replace('/\s+/u', ' ', $node->textContent ?? ''));

                if ($text !== '' && ! $this->isBoilerplate($text)) {
                    return $text;
                }
            }
        }

        return null;
    }

    /** Generic page descriptions that aren't a real excerpt. */
    protected function isBoilerplate(string $text): bool
    {
        return (bool) preg_match('/^(april|october)\s+\d{4}\s+general\s+conference$/i', $text)
            || mb_strlen($text) < 20;
    }
}
