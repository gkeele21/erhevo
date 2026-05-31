<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            [
                'name' => 'General Conference',
                'slug' => 'general-conference',
                'base_url' => 'https://www.churchofjesuschrist.org/study/general-conference',
                'platform' => 'churchofjesuschrist.org',
                'description' => 'Semi-annual General Conference of The Church of Jesus Christ of Latter-day Saints',
            ],
            [
                'name' => 'BYU Speeches',
                'slug' => 'byu-speeches',
                'base_url' => 'https://speeches.byu.edu',
                'platform' => 'speeches.byu.edu',
                'description' => 'Devotionals, forums, and addresses given at Brigham Young University',
            ],
            [
                'name' => 'BYU-Idaho Devotionals',
                'slug' => 'byui-devotionals',
                'base_url' => 'https://www.byui.edu/devotionals',
                'platform' => 'byui.edu',
                'description' => 'Devotionals given at Brigham Young University-Idaho',
            ],
            [
                'name' => 'Ensign',
                'slug' => 'ensign',
                'base_url' => 'https://www.churchofjesuschrist.org/study/ensign',
                'platform' => 'churchofjesuschrist.org',
                'description' => 'Articles from the Ensign magazine',
            ],
            [
                'name' => 'Liahona',
                'slug' => 'liahona',
                'base_url' => 'https://www.churchofjesuschrist.org/study/liahona',
                'platform' => 'churchofjesuschrist.org',
                'description' => 'Articles from the Liahona magazine',
            ],
            [
                'name' => 'New Era',
                'slug' => 'new-era',
                'base_url' => 'https://www.churchofjesuschrist.org/study/new-era',
                'platform' => 'churchofjesuschrist.org',
                'description' => 'Articles from the New Era magazine (youth publication)',
            ],
            [
                'name' => 'For the Strength of Youth',
                'slug' => 'fsy',
                'base_url' => 'https://www.churchofjesuschrist.org/study/for-the-strength-of-youth',
                'platform' => 'churchofjesuschrist.org',
                'description' => 'Articles from For the Strength of Youth magazine',
            ],
            [
                'name' => 'Friend',
                'slug' => 'friend',
                'base_url' => 'https://www.churchofjesuschrist.org/study/friend',
                'platform' => 'churchofjesuschrist.org',
                'description' => 'Articles from the Friend magazine (children\'s publication)',
            ],
            [
                'name' => 'CES Firesides',
                'slug' => 'ces-firesides',
                'base_url' => 'https://www.churchofjesuschrist.org/study/broadcasts',
                'platform' => 'churchofjesuschrist.org',
                'description' => 'Church Educational System firesides and devotionals',
            ],
        ];

        foreach ($sources as $source) {
            Source::updateOrCreate(
                ['slug' => $source['slug']],
                $source
            );
        }
    }
}
