<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\AuthorCalling;
use App\Models\Source;
use App\Models\Tag;
use App\Models\Talk;
use App\Models\TalkType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportByuSpeeches extends Command
{
    protected $signature = 'talks:import-byu-speeches
        {--pages=0 : Number of API pages to fetch (0 = all)}
        {--delay=500 : Milliseconds between requests}
        {--no-authors : Skip creating Author records for unknown speakers}';

    protected $description = 'Import all BYU Speeches from speeches.byu.edu (metadata + excerpt only), link or create authors, and tag by topic';

    private const API = 'https://speeches.byu.edu/wp-json/wp/v2/speech';

    /** event_type class => talk_types slug. Anything else falls to "other". */
    private const TYPE_MAP = [
        'devotional' => 'devotional',
        'forum' => 'forum',
        'commencement' => 'commencement',
        'fireside' => 'fireside',
        'womens-conference' => 'womens-conference',
        'education-week' => 'education-week',
    ];

    public function handle(): int
    {
        $source = Source::where('slug', 'byu-speeches')->first();

        if (! $source) {
            $this->error('Source "byu-speeches" not found. Run SourceSeeder first.');

            return Command::FAILURE;
        }

        $types = TalkType::pluck('id', 'slug');
        $delay = max(0, (int) $this->option('delay')) * 1000;
        $maxPages = (int) $this->option('pages');

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $page = 1;

        do {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) ErhevoBot/1.0',
            ])->timeout(30)->get(self::API, ['per_page' => 100, 'page' => $page]);

            if (! $response->successful()) {
                $this->error("Page {$page} failed ({$response->status()}) — stopping. Re-run to resume; the import is idempotent.");
                break;
            }

            $totalPages = (int) $response->header('X-WP-TotalPages');

            foreach ($response->json() as $speech) {
                $parsed = $this->parseSpeech($speech);

                if (! $parsed) {
                    $skipped++;

                    continue;
                }

                $talk = Talk::firstOrNew([
                    'source_id' => $source->id,
                    'slug' => $parsed['slug'],
                ]);

                $isNew = ! $talk->exists;

                $talk->fill([
                    'talk_type_id' => $types[$parsed['type']] ?? $types['other'] ?? null,
                    'speaker_name' => $parsed['speaker'],
                    'title' => $parsed['title'],
                    'talk_date' => $parsed['date'],
                    'url' => $parsed['url'],
                ]);

                // Never clobber a curated summary with a scraped one.
                if (! $talk->summary && $parsed['summary']) {
                    $talk->summary = $parsed['summary'];
                }

                $talk->save();
                $isNew ? $created++ : $updated++;

                if (! empty($parsed['topics'])) {
                    $tagIds = collect($parsed['topics'])
                        ->map(fn ($name) => Tag::findOrCreateByName($name)->id)
                        ->unique()
                        ->all();
                    $talk->tags()->syncWithoutDetaching($tagIds);
                }
            }

            $this->info("Page {$page}/{$totalPages}: {$created} created, {$updated} updated so far.");
            $page++;
            usleep($delay);
        } while ($page <= $totalPages && ($maxPages === 0 || $page <= $maxPages));

        $this->info("Import complete: {$created} created, {$updated} updated, {$skipped} skipped.");

        $this->call('talks:link-authors');

        if (! $this->option('no-authors')) {
            $this->createMissingAuthors($source);
        }

        $this->fillCallings($source);

        return Command::SUCCESS;
    }

    /**
     * Pull the fields we keep out of one API record. Speaker comes from the
     * Yoast breadcrumb (Home > Speeches > Speaker > Title); event type and
     * topic tags ride in class_list. Body text is deliberately NOT stored.
     *
     * @return ?array{slug: string, title: string, speaker: string, date: ?string, url: string, summary: ?string, type: string, topics: array}
     */
    protected function parseSpeech(array $speech): ?array
    {
        $title = html_entity_decode($speech['title']['rendered'] ?? '', ENT_QUOTES | ENT_HTML5);
        $slug = $speech['slug'] ?? null;

        if (! $slug || $title === '') {
            return null;
        }

        $speaker = null;
        foreach (($speech['yoast_head_json']['schema']['@graph'] ?? []) as $node) {
            if (($node['@type'] ?? null) === 'BreadcrumbList') {
                $items = $node['itemListElement'] ?? [];
                $speaker = $items[2]['name'] ?? null;
            }
        }

        if (! $speaker) {
            return null;
        }

        $type = 'other';
        $topics = [];
        foreach (($speech['class_list'] ?? []) as $class) {
            if (str_starts_with($class, 'event_type-')) {
                $eventType = substr($class, strlen('event_type-'));
                $type = self::TYPE_MAP[$eventType] ?? 'other';
            } elseif (str_starts_with($class, 'topic-')) {
                $topics[] = str_replace('-', ' ', substr($class, strlen('topic-')));
            }
        }

        return [
            'slug' => $slug,
            'title' => $title,
            'speaker' => html_entity_decode($speaker, ENT_QUOTES | ENT_HTML5),
            'date' => isset($speech['date']) ? substr($speech['date'], 0, 10) : null,
            'url' => $speech['link'] ?? "https://speeches.byu.edu/talks/{$slug}/",
            'summary' => $speech['yoast_head_json']['description'] ?? null,
            'type' => $type,
            'topics' => $topics,
        ];
    }

    /**
     * Create Author records for BYU speakers we don't know yet, so
     * author-based study plans work for them too, then link their talks.
     */
    protected function createMissingAuthors(Source $source): void
    {
        $unlinked = Talk::where('source_id', $source->id)
            ->whereNull('author_id')
            ->whereNotNull('speaker_name')
            ->get()
            ->groupBy('speaker_name');

        $createdAuthors = 0;

        foreach ($unlinked as $name => $talks) {
            $author = Author::findOrCreateByName($name);

            if ($author->wasRecentlyCreated) {
                $createdAuthors++;
            }

            Talk::whereIn('id', $talks->pluck('id'))->update(['author_id' => $author->id]);
        }

        $this->info("Created {$createdAuthors} new author(s) and linked their talks.");
    }

    /**
     * Fill church_calling_id from the author's calling history at the talk
     * date (only when exactly one calling covers it) — so an apostle's BYU
     * devotional shows the calling they held when they gave it.
     */
    protected function fillCallings(Source $source): void
    {
        $talks = Talk::where('source_id', $source->id)
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

        $this->info("Filled calling for {$filled} talk(s).");
    }
}
