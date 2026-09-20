<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\ScriptureBook;
use App\Services\ScriptureReferenceParser;
use Illuminate\Console\Command;

class BackfillPostScriptureReferences extends Command
{
    protected $signature = 'posts:backfill-scripture-references
                            {--dry-run : Show what would be linked without writing anything}
                            {--overwrite : Also reprocess posts that already have references}';

    protected $description = 'Scan post content for scripture references and link them via scripture_references. Additive — posts that already have references are skipped unless --overwrite.';

    /** Ordinal book prefixes the parser doesn't recognise on its own. */
    private const ORDINALS = [
        'First' => '1', '1st' => '1',
        'Second' => '2', '2nd' => '2',
        'Third' => '3', '3rd' => '3',
        'Fourth' => '4', '4th' => '4',
    ];

    /** Built once from the book list; see referencePattern(). */
    private ?string $pattern = null;

    public function handle(ScriptureReferenceParser $parser): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $overwrite = (bool) $this->option('overwrite');

        $posts = Post::query()
            ->when(! $overwrite, fn ($q) => $q->doesntHave('scriptureReferences'))
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No posts to process.');

            return self::SUCCESS;
        }

        $linked = 0;
        $postsTouched = 0;

        foreach ($posts as $post) {
            $refs = $this->extractReferences($post, $parser);

            if (empty($refs)) {
                continue;
            }

            $postsTouched++;
            $linked += count($refs);

            $this->line(sprintf(
                '%s #%d "%s" → %s',
                $dryRun ? '[dry-run]' : 'linked',
                $post->id,
                str($post->title)->limit(40),
                collect($refs)->pluck('label')->join(', ')
            ));

            if (! $dryRun) {
                $post->syncScriptureReferences($refs);
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d reference(s) across %d of %d post(s).',
            $dryRun ? 'Would link' : 'Linked',
            $linked,
            $postsTouched,
            $posts->count()
        ));

        if ($dryRun) {
            $this->comment('Nothing was written. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }

    /**
     * A reference matcher anchored to the actual book names and abbreviations.
     *
     * Guessing at book-shaped words is too loose: "See 1 Nephi 4" matches
     * "See" + chapter 1 and swallows the numeral that belongs to the book,
     * and prose like "Religious Educator 12" matches nothing real. Listing the
     * books removes both problems. Longest first so "1 Nephi" wins over
     * "Nephi", and so abbreviations don't truncate full names.
     */
    protected function referencePattern(): string
    {
        if ($this->pattern) {
            return $this->pattern;
        }

        $names = ScriptureBook::query()
            ->get(['name', 'abbreviation'])
            ->flatMap(fn ($book) => [$book->name, $book->abbreviation])
            ->filter()
            ->unique()
            ->sortByDesc(fn ($name) => strlen($name))
            ->map(fn ($name) => preg_quote($name, '/'))
            ->implode('|');

        return $this->pattern = '/\b(' . $names . ')\s+(\d+)(?::(\d+))?(?:-(\d+)(?::(\d+))?)?(?![\w:])/';
    }

    /**
     * Flatten post HTML into matchable prose.
     *
     * Tags become spaces rather than being dropped: stripping them outright
     * glues a reference to the next block ("Moses 4:4</p><p>Satan" ->
     * "Moses 4:4Satan"), which costs the verse number. Spelled-out ordinals
     * are folded to digits so "Second Nephi 28" resolves like "2 Nephi 28".
     */
    protected function normalize(string $html): string
    {
        $text = html_entity_decode(preg_replace('/<[^>]+>/', ' ', $html));

        foreach (self::ORDINALS as $word => $digit) {
            $text = preg_replace('/\b' . $word . '\s+(?=[A-Z])/', $digit . ' ', $text);
        }

        // "Alma Chapter 30" -> "Alma 30"
        $text = preg_replace('/\s+[Cc]hapters?\s+(?=\d)/', ' ', $text);

        return preg_replace('/\s+/', ' ', $text);
    }

    /**
     * Pull resolvable scripture references out of a post's title and body.
     *
     * @return array<int, array{start_chapter_id: int, start_verse: ?int, end_chapter_id: ?int, end_verse: ?int, label: string}>
     */
    protected function extractReferences(Post $post, ScriptureReferenceParser $parser): array
    {
        $text = $post->title . "\n" . $this->normalize($post->content ?? '');

        if (! preg_match_all($this->referencePattern(), $text, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $refs = [];
        $seen = [];

        foreach ($matches as $match) {
            $candidate = trim($match[0]);
            $parsed = $parser->parseToChapterIds($candidate);

            if (! $parsed) {
                continue;
            }

            // The same passage often appears several times in one post.
            $key = implode('-', [
                $parsed['start_chapter_id'],
                $parsed['start_verse'] ?? '',
                $parsed['end_chapter_id'] ?? '',
                $parsed['end_verse'] ?? '',
            ]);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $refs[] = $parsed + ['label' => $candidate];
        }

        return $this->dropRedundantWholeChapters($refs);
    }

    /**
     * Drop a bare "Moses 4" when the same post also names verses in Moses 4.
     *
     * Prose routinely does both ("Moses 4 teaches us... as it says in Moses
     * 4:1"), and keeping the chapter-wide row would mark every verse in the
     * chapter as discussed, burying the verses actually written about.
     * Only whole-chapter rows are affected; ranges are left alone.
     *
     * @param  array<int, array<string, mixed>>  $refs
     * @return array<int, array<string, mixed>>
     */
    protected function dropRedundantWholeChapters(array $refs): array
    {
        $chaptersWithVerses = [];
        foreach ($refs as $ref) {
            if ($ref['start_verse']) {
                $chaptersWithVerses[$ref['start_chapter_id']] = true;
            }
        }

        return array_values(array_filter($refs, fn ($ref) => $ref['start_verse']
            || $ref['end_chapter_id']
            || ! isset($chaptersWithVerses[$ref['start_chapter_id']])));
    }
}
