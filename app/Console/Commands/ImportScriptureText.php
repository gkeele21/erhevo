<?php

namespace App\Console\Commands;

use App\Models\ScriptureBook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportScriptureText extends Command
{
    protected $signature = 'scriptures:import-text {--path= : Directory of bcbooks flat JSON files}';

    protected $description = 'Backfill verse text on scripture_verses from public-domain bcbooks flat JSON files';

    /**
     * The flat JSON files to import (public domain, from bcbooks/scriptures-json).
     */
    protected array $files = [
        'book-of-mormon-flat.json',
        'doctrine-and-covenants-flat.json',
        'pearl-of-great-price-flat.json',
        'old-testament-flat.json',
        'new-testament-flat.json',
    ];

    public function handle(): int
    {
        $dir = $this->option('path') ?: database_path('data/scripture-text');

        // Build in-memory lookup maps so we never hit the DB per verse.
        $bookByKey = $this->buildBookMap();
        $chapterId = $this->buildChapterMap();   // "book_id:chapter" => chapter_id
        $verseId = $this->buildVerseMap();       // "chapter_id:verse"  => verse_id

        $updates = [];   // existing verse rows -> set text
        $inserts = [];   // chapter known but verse row missing -> create it
        $unmatched = []; // book/chapter could not be resolved at all
        $total = 0;
        $now = now();

        foreach ($this->files as $file) {
            $full = $dir . '/' . $file;
            if (! is_file($full)) {
                $this->warn("Skipping missing file: {$file}");
                continue;
            }

            $data = json_decode(file_get_contents($full), true);
            foreach ($data['verses'] ?? [] as $verse) {
                $total++;
                $parsed = $this->parseReference($verse['reference'] ?? '');
                if (! $parsed) {
                    $unmatched[] = $verse['reference'] ?? '(blank)';
                    continue;
                }

                [$bookToken, $chapter, $verseNum] = $parsed;
                $bookId = $bookByKey[$bookToken] ?? null;
                $cid = $bookId ? ($chapterId["{$bookId}:{$chapter}"] ?? null) : null;

                if (! $cid) {
                    $unmatched[] = $verse['reference'];
                    continue;
                }

                $vid = $verseId["{$cid}:{$verseNum}"] ?? null;

                if ($vid) {
                    $updates[] = [
                        'id' => $vid,
                        'chapter_id' => $cid,
                        'verse_number' => $verseNum,
                        'text' => $verse['text'] ?? null,
                    ];
                } else {
                    $inserts[] = [
                        'chapter_id' => $cid,
                        'verse_number' => $verseNum,
                        'text' => $verse['text'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        $this->info('Resolved ' . (count($updates) + count($inserts)) . ' of ' . $total .
            ' verses (' . count($inserts) . ' new); writing...');

        foreach (array_chunk($updates, 1000) as $chunk) {
            DB::table('scripture_verses')->upsert($chunk, ['id'], ['text']);
        }

        foreach (array_chunk($inserts, 1000) as $chunk) {
            DB::table('scripture_verses')->insert($chunk);
        }

        // Keep each chapter's verse_count in sync with the verses it actually has.
        if ($inserts) {
            $affectedChapters = array_unique(array_column($inserts, 'chapter_id'));
            foreach ($affectedChapters as $cid) {
                $max = DB::table('scripture_verses')->where('chapter_id', $cid)->max('verse_number');
                DB::table('scripture_chapters')->where('id', $cid)->update(['verse_count' => $max]);
            }
        }

        $withText = DB::table('scripture_verses')->whereNotNull('text')->count();
        $this->info("Done. {$withText} verses now have text.");

        if ($unmatched) {
            $this->warn(count($unmatched) . ' references could not be resolved to a book/chapter (e.g. ' .
                implode(', ', array_slice($unmatched, 0, 5)) . ').');
        }

        return self::SUCCESS;
    }

    /**
     * Parse a single-verse reference like "1 Nephi 3:7" or "D&C 1:1".
     *
     * @return array{0: string, 1: int, 2: int}|null  [bookKey, chapter, verse]
     */
    protected function parseReference(string $reference): ?array
    {
        if (! preg_match('/^(.+?)\s+(\d+):(\d+)$/', trim($reference), $m)) {
            return null;
        }

        return [$this->bookKey($m[1]), (int) $m[2], (int) $m[3]];
    }

    /**
     * Normalize a book name/abbreviation to a lookup key.
     */
    protected function bookKey(string $name): string
    {
        return strtolower(trim(rtrim(trim($name), '.')));
    }

    /**
     * Book names the bcbooks data uses that differ from our DB names.
     * Keyed by bcbooks name => our DB book name.
     */
    protected array $bookAliases = [
        "Solomon's Song" => 'Song of Solomon',
    ];

    protected function buildBookMap(): array
    {
        $map = [];
        $byName = [];
        foreach (ScriptureBook::all() as $book) {
            foreach ([$book->name, $book->abbreviation, Str::slug($book->name)] as $candidate) {
                if ($candidate) {
                    $map[$this->bookKey($candidate)] = $book->id;
                }
            }
            // Slug form keyed directly too (e.g. "1-nephi").
            $map[strtolower(Str::slug($book->name))] = $book->id;
            $byName[$book->name] = $book->id;
        }

        // Register aliases for naming differences between bcbooks and our DB.
        foreach ($this->bookAliases as $bcName => $dbName) {
            if (isset($byName[$dbName])) {
                $map[$this->bookKey($bcName)] = $byName[$dbName];
            }
        }

        return $map;
    }

    protected function buildChapterMap(): array
    {
        $map = [];
        foreach (DB::table('scripture_chapters')->select('id', 'book_id', 'chapter_number')->get() as $c) {
            $map["{$c->book_id}:{$c->chapter_number}"] = $c->id;
        }

        return $map;
    }

    protected function buildVerseMap(): array
    {
        $map = [];
        foreach (DB::table('scripture_verses')->select('id', 'chapter_id', 'verse_number')->get() as $v) {
            $map["{$v->chapter_id}:{$v->verse_number}"] = $v->id;
        }

        return $map;
    }
}
