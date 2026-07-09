<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reference data — safe to seed everywhere, including production.
        // The church-figure / talk dataset is loaded deterministically from JSON
        // snapshots in database/data/seed/ (see docs/PRODUCTION_DATA_SEEDING.md);
        // AuthorSeeder/AuthorCallingSeeder/TalkSeeder supersede ChurchLeadershipSeeder,
        // the authors:import CSV commands, and GeneralConferenceTalkSeeder.
        $this->call([
            CategorySeeder::class,
            DefaultCategorySeeder::class,

            // Org structure + callings (ids 1-29), then the hand-added calling
            // types (ids 30-35) that authors/talks reference.
            ChurchOrganizationSeeder::class,
            ExtraChurchCallingSeeder::class,

            SourceSeeder::class,
            TalkTypeSeeder::class,

            // Conferences + sessions (talks reference session ids).
            GeneralConferenceSessionTypeSeeder::class,
            GeneralConferenceSeeder::class,

            // Church figures, their calling history, and GC talk metadata.
            AuthorSeeder::class,
            AuthorCallingSeeder::class,
            TalkSeeder::class,

            CfmScheduleSeeder::class,
            CfmSpecialTopicSeeder::class,
            ScriptureSeeder::class,
        ]);

        // Sample/demo data relies on model factories (faker), which is a
        // dev-only dependency and absent in production (composer install
        // --no-dev). Also, we don't want a test user or fake posts in prod.
        if (! app()->isProduction()) {
            User::factory()->create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

            $this->call([
                PostSeeder::class,
            ]);
        }
    }
}
