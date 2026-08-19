<?php

namespace App\Console\Commands;

use App\Models\Temple;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Scrapes churchofjesuschristtemples.org (a fan site — no official API
 * exists) for every dedicated temple's address, coordinates, photo, and
 * first dedication day. Idempotent: upserts by slug, so re-run it when new
 * temples are dedicated, then `php artisan db:snapshot-seed-data` and commit
 * the refreshed temples.json so seeding stays offline.
 */
class ImportTemples extends Command
{
    protected $signature = 'temples:import
        {--delay=500 : Milliseconds between requests}
        {--limit=0 : Max detail pages to fetch (0 = all)}
        {--slug= : Import a single temple by slug}
        {--dry-run : Parse and report without writing to the database}';

    protected $description = 'Import dedicated LDS temples from churchofjesuschristtemples.org';

    private const BASE = 'https://churchofjesuschristtemples.org';

    public function handle(): int
    {
        $delay = max(0, (int) $this->option('delay')) * 1000;
        $limit = (int) $this->option('limit');
        $only = $this->option('slug');

        $listed = $only
            ? [['slug' => $only, 'name' => null, 'undedicated' => false]]
            : $this->fetchTempleList();

        if ($listed === []) {
            $this->error('No temples found on the list page — site layout may have changed.');

            return Command::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $warnings = [];
        $rows = [];
        $fetched = 0;

        foreach ($listed as $entry) {
            // † on the list page marks announced / under-construction temples
            // (‡ = renovation, still dedicated). The detail-page dedication
            // check below stays authoritative; this just skips known
            // undedicated pages.
            if ($entry['undedicated']) {
                $skipped++;

                continue;
            }

            if ($limit > 0 && $fetched >= $limit) {
                break;
            }

            $fetched++;
            $response = $this->get(self::BASE."/{$entry['slug']}/");

            if (! $response || ! $response->successful()) {
                $warnings[] = "{$entry['slug']}: fetch failed".($response ? " ({$response->status()})" : '');

                continue;
            }

            $parsed = $this->parseDetailPage($response->body(), $entry['slug'], $warnings);

            if (! $parsed) {
                $skipped++;

                continue;
            }

            $parsed['name'] = $entry['name'] ?? $parsed['name'];
            $rows[] = $parsed;

            if (! $this->option('dry-run')) {
                $temple = Temple::updateOrCreate(['slug' => $parsed['slug']], $parsed);
                $temple->wasRecentlyCreated ? $created++ : $updated++;
            }

            usleep($delay);
        }

        $this->table(
            ['Slug', 'City', 'State', 'Country', 'Dedicated', 'Lat', 'Lng'],
            collect($rows)->map(fn ($r) => [
                $r['slug'], $r['city'], $r['state'], $r['country'],
                $r['dedicated_on'], $r['latitude'], $r['longitude'],
            ])->all()
        );

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        $this->info(
            $this->option('dry-run')
                ? sprintf('Dry run: %d parsed, %d skipped (undedicated/unparseable), %d warning(s).', count($rows), $skipped, count($warnings))
                : sprintf('Import complete: %d created, %d updated, %d skipped, %d warning(s).', $created, $updated, $skipped, count($warnings))
        );

        return Command::SUCCESS;
    }

    /** @return array<int, array{slug: string, name: string, undedicated: bool}> */
    protected function fetchTempleList(): array
    {
        $response = $this->get(self::BASE.'/temples/');

        if (! $response || ! $response->successful()) {
            return [];
        }

        preg_match_all(
            '#<a href="/([a-z0-9-]+-temple)/">([^<]+)</a>(?:<sup>([^<]*)</sup>)?#u',
            $response->body(),
            $matches,
            PREG_SET_ORDER
        );

        $temples = [];

        foreach ($matches as $m) {
            $temples[$m[1]] = [
                'slug' => $m[1],
                'name' => html_entity_decode(trim($m[2]), ENT_QUOTES | ENT_HTML5),
                'undedicated' => str_contains($m[3] ?? '', '†'),
            ];
        }

        return array_values($temples);
    }

    /**
     * Extract one temple's fields from its detail page. Returns null for
     * temples without an original dedication (announced / under
     * construction). Address, coordinates, and photo degrade to null with a
     * warning; the dedication date is the only hard requirement.
     */
    protected function parseDetailPage(string $html, string $slug, array &$warnings): ?array
    {
        // The dedication row links to the dedicatory prayer with the first
        // day of the dedication as an ISO date in the href — the most
        // reliable extraction. "Rededication:" rows don't match the label.
        $dedicated = null;
        if (preg_match('#<h4 class="label">Dedication:[^<]*</h4>\s*<a href="dedicatory-prayer/(\d{4}-\d{2}-\d{2})/"#u', $html, $m)) {
            $dedicated = $m[1];
        } elseif (preg_match('#<h4 class="label">Dedication:[^<]*</h4>\s*(?:<a[^>]*>)?([^<]+)#u', $html, $m)) {
            $dedicated = self::parseDedicationDate(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5));

            if (! $dedicated) {
                $warnings[] = "{$slug}: unparseable dedication date \"{$m[1]}\"";

                return null;
            }
        }

        if (! $dedicated) {
            return null; // announced or under construction
        }

        $photo = null;
        if (preg_match('#property="og:image" content="([^"]+)"#', $html, $m)) {
            $photo = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
        } else {
            $warnings[] = "{$slug}: no og:image photo";
        }

        $latitude = null;
        $longitude = null;
        if (preg_match('#google\.com/maps/[^"]*@(-?\d+\.\d+),(-?\d+\.\d+)#', $html, $m)) {
            $latitude = (float) $m[1];
            $longitude = (float) $m[2];
        } else {
            $warnings[] = "{$slug}: no coordinates found";
        }

        $address = $this->parseAddressBlock($html, $slug, $warnings);

        // Historic temples (Kirtland, original Nauvoo) are dedicated but not
        // operating — their pages have no address block. Only active temples
        // belong in the tracker.
        if ($address === null) {
            return null;
        }

        $name = self::parseName($html);

        if ($name === null) {
            $warnings[] = "{$slug}: no temple name in <title>, using the slug";
        }

        return [
            'slug' => $slug,
            'name' => $name ?? ucwords(str_replace('-', ' ', $slug)),
            'dedicated_on' => $dedicated,
            'photo_url' => $photo,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'source_url' => self::BASE."/{$slug}/",
            ...$address,
        ];
    }

    /**
     * The address block after <h4>Address</h4> is <br>-separated lines:
     * street line(s), then a city line, then a country line. The site links
     * geography to /statistics/locations/<country>(/<state>) — a
     * two-segment link marks the "City, State  ZIP" line (US/CA/MX style),
     * a one-segment link marks the country line. Without a state link, the
     * city is the last line before the country (international style).
     *
     * Returns null when the page has no address block at all (historic,
     * non-operating temples).
     *
     * @return ?array{address: ?string, city: ?string, state: ?string, country: string}
     */
    protected function parseAddressBlock(string $html, string $slug, array &$warnings): ?array
    {
        $empty = ['address' => null, 'city' => null, 'state' => null, 'country' => 'Unknown'];

        if (! preg_match('#>Address</h4>(.*?)(?:Telephone:|<div\b)#su', $html, $m)) {
            $warnings[] = "{$slug}: no address block — skipped as non-operating";

            return null;
        }

        $lines = [];
        foreach (preg_split('#<br\s*/?>#', $m[1]) as $raw) {
            $line = ['links' => [], 'text' => ''];

            if (preg_match('#/statistics/locations/([^/">]+?)(?:/([^/">]+?))?/?"[^>]*>([^<]+)<#u', $raw, $lm)) {
                $line['links'] = ['segments' => isset($lm[2]) && $lm[2] !== '' ? 2 : 1, 'label' => trim($lm[3])];
            }

            $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5)));

            if ($text !== '') {
                $line['text'] = $text;
                $lines[] = $line;
            }
        }

        if ($lines === []) {
            $warnings[] = "{$slug}: empty address block";

            return $empty;
        }

        $country = null;
        $state = null;
        $city = null;
        $countryIndex = null;
        $cityIndex = null;

        foreach ($lines as $i => $line) {
            if ($line['links'] === []) {
                continue;
            }

            if ($line['links']['segments'] === 1) {
                $country = $line['links']['label'];
                $countryIndex = $i;
            } else {
                $state = $line['links']['label'];
                $cityIndex = $i;
                // "Boise, Idaho 83709-1871" → text before the comma
                $city = str_contains($line['text'], ',') ? $this->cleanPlace(strtok($line['text'], ',')) : null;
            }
        }

        if ($country === null) {
            $warnings[] = "{$slug}: no country link in address";

            return [...$empty, 'address' => $lines[0]['text']];
        }

        // International style: city is the last line before the country.
        // "Tokyo 106-0047" → Tokyo; "Marden, South Australia" → city Marden,
        // state South Australia; "Muntinlupa City, 1781 Metro Manila" →
        // Muntinlupa City / Metro Manila (postal codes stripped).
        if ($cityIndex === null && $countryIndex > 0) {
            $cityIndex = $countryIndex - 1;
            $text = $lines[$cityIndex]['text'];

            if (str_contains($text, ',')) {
                [$cityPart, $rest] = explode(',', $text, 2);
                $city = $this->cleanPlace($cityPart);
                $state = $this->cleanPlace($rest);
            } else {
                $city = $this->cleanPlace($text);
            }
        }

        $street = collect($lines)
            ->take($cityIndex ?? $countryIndex)
            ->pluck('text')
            ->implode(', ');

        return [
            'address' => $street !== '' ? $street : null,
            'city' => $city !== '' ? $city : null,
            'state' => $state,
            'country' => $country,
        ];
    }

    /**
     * Strip postal codes from a city/state fragment: leading digit runs
     * ("86100 Villahermosa") and everything from the first digit-bearing
     * token on ("Manitoba R3Y 2C5" → "Manitoba"). Null when nothing is left.
     */
    protected function cleanPlace(string $text): ?string
    {
        // Leading postal tokens: "83709", "CH-3052", "B1778DUA", "70.830-550".
        $text = trim(preg_replace('/^(?:\S*\d\S*\s+)+/u', '', trim($text)));
        // Trailing digit-bearing tokens: "Manitoba R3Y 2C5" → "Manitoba".
        $text = trim(preg_replace('/\s*\S*\d.*$/u', '', $text), " \t,");
        // Region suffix after an en/em dash: "Brasilia–DF" → "Brasilia"
        // (real hyphenated city names use plain hyphens).
        $text = trim(preg_replace('/\s*[–—]\s*\S+$/u', '', $text));

        return $text !== '' ? $text : null;
    }

    /**
     * Normalize a human dedication string to the first day, ISO format.
     * Handles "25–30 May 1984 by Gordon B. Hinckley", "30 April–1 May 1993",
     * "14 February 1987", and "April 6, 1893". Returns null when unparseable.
     */
    public static function parseDedicationDate(string $text): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        $text = preg_replace('/\s+by\s+.*$/iu', '', $text);
        $text = preg_replace('/\(.*?\)/u', '', $text);

        // "25–30 May 1984" → "25 May 1984"
        $text = preg_replace('/^(\d{1,2})\s*[–—-]\s*\d{1,2}\s+/u', '$1 ', $text);
        // "30 April–1 May 1993" → "30 April 1993"
        $text = preg_replace('/^(\d{1,2}\s+[[:alpha:]]+)\s*[–—-]\s*\d{1,2}\s+[[:alpha:]]+\s+(\d{4})/u', '$1 $2', $text);

        try {
            return Carbon::parse(trim($text))->toDateString();
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Every detail page's <h1> is the site-wide banner ("Temples of The
     * Church of Jesus Christ of Latter-day Saints"), never the temple's own
     * name — the <title> carries that, ahead of the site suffix. Returns null
     * when only the banner is there, so callers can fall back to the slug.
     */
    public static function parseName(string $html): ?string
    {
        if (! preg_match('#<title>([^<]+)</title>#u', $html, $m)) {
            return null;
        }

        $name = trim(explode('|', html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5))[0]);

        if ($name === '' || str_starts_with($name, 'Temples of')) {
            return null;
        }

        return $name;
    }

    protected function get(string $url): ?Response
    {
        try {
            return Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) ErhevoBot/1.0',
            ])->timeout(30)->get($url);
        } catch (\Exception $e) {
            return null;
        }
    }
}
