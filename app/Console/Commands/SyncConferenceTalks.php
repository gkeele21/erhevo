<?php

namespace App\Console\Commands;

use App\Models\AuthorCalling;
use App\Models\GeneralConference;
use App\Models\GeneralConferenceSession;
use App\Models\GeneralConferenceSessionType;
use App\Models\Source;
use App\Models\Talk;
use App\Models\TalkType;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncConferenceTalks extends Command
{
    protected $signature = 'talks:sync-conference
        {year : Conference year, e.g. 2026}
        {month : april or october}
        {--dry-run : Report what would change without writing}';

    protected $description = 'Sync a General Conference\'s talks from churchofjesuschrist.org — adds missing talks, fixes session assignment and speaking order';

    public function handle(): int
    {
        $year = (int) $this->argument('year');
        $month = strtolower($this->argument('month'));

        $conference = GeneralConference::where('year', $year)->where('month', $month)->first();
        $source = Source::where('slug', 'general-conference')->first();

        if (! $conference || ! $source) {
            $this->error($conference ? 'Source "general-conference" not found.' : "Conference not found: {$month} {$year} (seed it first).");

            return Command::FAILURE;
        }

        $monthNum = $month === 'april' ? '04' : '10';
        $url = "https://www.churchofjesuschrist.org/study/general-conference/{$year}/{$monthNum}?lang=eng";

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) ErhevoBot/1.0',
        ])->timeout(20)->get($url);

        if (! $response->successful()) {
            $this->error("Failed to fetch {$url} ({$response->status()}).");

            return Command::FAILURE;
        }

        $sessions = $this->parseIndex($response->body(), "/study/general-conference/{$year}/{$monthNum}/");

        if (empty($sessions)) {
            $this->error('No sessions found on the conference page — its markup may have changed.');

            return Command::FAILURE;
        }

        $talkType = TalkType::where('slug', 'conference-talk')->first();

        // Existing talks in this conference, matchable by slug or by title.
        $existing = Talk::whereHas('conferenceSession', fn ($q) => $q->where('general_conference_id', $conference->id))->get();
        $bySlug = $existing->keyBy('slug');
        $byTitle = $existing->keyBy(fn ($t) => $this->normalize($t->title));

        $created = 0;
        $updated = 0;
        $position = 0;

        foreach ($sessions as $order => $session) {
            $model = $this->resolveSession($conference, $session, $order + 1);

            if (! $model) {
                $this->warn("Skipping unknown session type: {$session['slug']} ({$session['name']})");

                continue;
            }

            foreach ($session['talks'] as $talkData) {
                $position++;

                $talk = $bySlug->get($talkData['slug']) ?? $byTitle->get($this->normalize($talkData['title']));

                $attributes = [
                    'general_conference_session_id' => $model->id,
                    'display_order' => $position,
                    'talk_date' => $model->session_date ?? $conference->start_date,
                    'url' => "https://www.churchofjesuschrist.org{$talkData['href']}?lang=eng",
                ];

                if ($talk) {
                    if (! $talk->summary && $talkData['summary']) {
                        $attributes['summary'] = $talkData['summary'];
                    }

                    if ($this->option('dry-run')) {
                        $this->line("  would update: {$talk->title}");
                    } else {
                        $talk->update($attributes);
                    }
                    $updated++;
                } else {
                    if ($this->option('dry-run')) {
                        $this->line("  would create: {$talkData['speaker']} — {$talkData['title']}");
                    } else {
                        // No slug: church slugs (e.g. "11eyring") repeat across
                        // conferences but talks.slug is unique per source, so
                        // let the model hook generate a unique title-based one.
                        Talk::create($attributes + [
                            'source_id' => $source->id,
                            'talk_type_id' => $talkType?->id,
                            'speaker_name' => $talkData['speaker'],
                            'title' => $talkData['title'],
                            'summary' => $talkData['summary'] ?: null,
                        ]);
                    }
                    $created++;
                }
            }
        }

        $this->info(($this->option('dry-run') ? '[dry run] ' : '') . "Sync complete: {$created} talks created, {$updated} updated across " . count($sessions) . ' sessions.');

        if (! $this->option('dry-run')) {
            $this->call('talks:link-authors');
            $this->fillCallings($conference);
        }

        return Command::SUCCESS;
    }

    /**
     * Parse the conference index into ordered sessions, each with its talks
     * in speaking order. The page's list tiles carry semantic classes:
     * p.primaryMeta (speaker), p.title, p.description (summary); session
     * tiles use an "-session" slug suffix.
     *
     * @return array<int, array{slug: string, name: string, talks: array}>
     */
    protected function parseIndex(string $html, string $pathPrefix): array
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        if (! $doc->loadHTML('<?xml encoding="UTF-8">' . $html)) {
            return [];
        }

        $xpath = new DOMXPath($doc);
        $sessions = [];
        $current = null;

        $tiles = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " list-tile ")]');

        foreach ($tiles as $tile) {
            $href = strtok($tile->getAttribute('href'), '?');

            if (! str_starts_with($href, $pathPrefix)) {
                continue;
            }

            $slug = basename($href);
            $field = fn (string $class) => trim(preg_replace(
                '/\s+/u',
                ' ',
                $xpath->query('.//p[@class="' . $class . '"]', $tile)->item(0)?->textContent ?? ''
            ));

            $title = $field('title');

            if (str_ends_with($slug, '-session')) {
                if ($title !== '' && ! isset($sessions[$slug])) {
                    $sessions[$slug] = ['slug' => $slug, 'name' => $title, 'talks' => []];
                }
                $current = $slug;

                continue;
            }

            if ($current === null || $title === '' || isset($sessions[$current]['seen'][$slug])) {
                continue;
            }

            $sessions[$current]['talks'][] = [
                'slug' => $slug,
                'href' => $href,
                'title' => $title,
                'speaker' => $field('primaryMeta'),
                'summary' => $field('description'),
            ];
            $sessions[$current]['seen'][$slug] = true;
        }

        return array_values(array_filter($sessions, fn ($s) => $s['talks'] !== []));
    }

    /** Page session slugs whose wording differs from our session type slugs. */
    private const SESSION_TYPE_ALIASES = [
        'general-womens' => 'womens',
        'general-women' => 'womens',
        'general-young-women' => 'womens',
    ];

    /** Find (or create) the conference's session matching a page session slug. */
    protected function resolveSession(GeneralConference $conference, array $session, int $order): ?GeneralConferenceSession
    {
        $typeSlug = preg_replace('/-session$/', '', $session['slug']);
        $typeSlug = self::SESSION_TYPE_ALIASES[$typeSlug] ?? $typeSlug;

        $type = GeneralConferenceSessionType::where('slug', $typeSlug)->first();

        if (! $type) {
            return null;
        }

        $existing = $conference->sessions()->where('session_type_id', $type->id)->first();

        if ($existing) {
            return $existing;
        }

        if ($this->option('dry-run')) {
            return new GeneralConferenceSession(['name' => $session['name']]);
        }

        return $conference->sessions()->create([
            'session_type_id' => $type->id,
            'name' => $session['name'],
            'session_date' => str_starts_with($typeSlug, 'sunday') ? $conference->end_date : $conference->start_date,
            'display_order' => $order,
        ]);
    }

    /**
     * Fill church_calling_id for this conference's talks from the linked
     * author's calling history at the talk date (only when unambiguous).
     */
    protected function fillCallings(GeneralConference $conference): void
    {
        $talks = Talk::whereHas('conferenceSession', fn ($q) => $q->where('general_conference_id', $conference->id))
            ->whereNull('church_calling_id')
            ->whereNotNull('author_id')
            ->whereNotNull('talk_date')
            ->get();

        $filled = 0;

        foreach ($talks as $talk) {
            $callings = AuthorCalling::where('author_id', $talk->author_id)
                ->where(fn ($q) => $q->whereNull('start_date')->orWhereDate('start_date', '<=', $talk->talk_date))
                ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $talk->talk_date))
                ->get();

            if ($callings->count() === 1) {
                $talk->update(['church_calling_id' => $callings->first()->church_calling_id]);
                $filled++;
            }
        }

        $this->info("Filled calling for {$filled} talk(s) from author calling history.");
    }

    protected function normalize(string $title): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(
            transliterator_transliterate('Any-Latin; Latin-ASCII', $title) ?: $title
        ));
    }
}
