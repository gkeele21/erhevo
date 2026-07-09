<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Regenerates the JSON snapshots that AuthorSeeder / AuthorCallingSeeder /
 * TalkSeeder / ExtraChurchCallingSeeder load. Run this after changing the
 * church-figure or talk data locally, then commit the updated files so a fresh
 * `php artisan migrate --seed` reproduces the current state everywhere.
 *
 * See docs/PRODUCTION_DATA_SEEDING.md.
 */
class ExportSeedSnapshots extends Command
{
    protected $signature = 'db:snapshot-seed-data';

    protected $description = 'Export authors, author_callings, talks, and extra church callings to database/data/seed/*.json for deterministic seeding.';

    public function handle(): int
    {
        $dir = database_path('data/seed');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $targets = [
            'church_callings_extra' => DB::table('church_callings')->where('id', '>', 29),
            'authors' => DB::table('authors'),
            'author_callings' => DB::table('author_callings'),
            'talks' => DB::table('talks'),
        ];

        foreach ($targets as $name => $query) {
            $rows = $query->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
            $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            file_put_contents("{$dir}/{$name}.json", $json . "\n");
            $this->line(sprintf('  %-24s %d rows  %.1f KB', $name, count($rows), strlen($json) / 1024));
        }

        $this->info("Wrote snapshots to {$dir}");

        return self::SUCCESS;
    }
}
