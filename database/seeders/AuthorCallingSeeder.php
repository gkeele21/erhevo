<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsFromJson;
use Illuminate\Database\Seeder;

/**
 * Author calling history + concurrency (author_callings) from the
 * database/data/seed/author_callings.json snapshot. Requires AuthorSeeder and
 * the church_callings to be seeded first.
 */
class AuthorCallingSeeder extends Seeder
{
    use SeedsFromJson;

    public function run(): void
    {
        $n = $this->seedFromJson('author_callings.json', 'author_callings');
        $this->command?->info("  Seeded {$n} author callings.");
    }
}
