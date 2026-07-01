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
        $this->call([
            CategorySeeder::class,
            DefaultCategorySeeder::class,
            ChurchOrganizationSeeder::class,
            SourceSeeder::class,
            TalkTypeSeeder::class,
            GeneralConferenceSessionTypeSeeder::class,
            GeneralConferenceSeeder::class,
            GeneralConferenceTalkSeeder::class,
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
