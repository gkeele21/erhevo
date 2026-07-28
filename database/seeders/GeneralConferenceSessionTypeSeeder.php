<?php

namespace Database\Seeders;

use App\Models\GeneralConferenceSessionType;
use Illuminate\Database\Seeder;

class GeneralConferenceSessionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Saturday Morning', 'slug' => 'saturday-morning', 'display_order' => 1],
            ['name' => 'Saturday Afternoon', 'slug' => 'saturday-afternoon', 'display_order' => 2],
            ['name' => 'Saturday Evening', 'slug' => 'saturday-evening', 'display_order' => 3],
            ['name' => 'General Priesthood', 'slug' => 'priesthood', 'display_order' => 4],
            ['name' => 'General Women\'s', 'slug' => 'womens', 'display_order' => 5],
            ['name' => 'Sunday Morning', 'slug' => 'sunday-morning', 'display_order' => 6],
            ['name' => 'Sunday Afternoon', 'slug' => 'sunday-afternoon', 'display_order' => 7],
            // Historical sessions that appear on churchofjesuschrist.org
            // conference pages (synced by talks:sync-conference).
            ['name' => 'General Relief Society', 'slug' => 'relief-society', 'display_order' => 8],
            ['name' => 'Friday Morning', 'slug' => 'friday-morning', 'display_order' => 9],
            ['name' => 'Friday Afternoon', 'slug' => 'friday-afternoon', 'display_order' => 10],
            ['name' => 'General Welfare', 'slug' => 'welfare', 'display_order' => 11],
            ['name' => 'General Young Women', 'slug' => 'young-women', 'display_order' => 12],
            ['name' => 'Tuesday Morning', 'slug' => 'tuesday-morning', 'display_order' => 13],
            ['name' => 'Tuesday Afternoon', 'slug' => 'tuesday-afternoon', 'display_order' => 14],
            ['name' => 'Thursday Morning', 'slug' => 'thursday-morning', 'display_order' => 15],
            ['name' => 'Thursday Afternoon', 'slug' => 'thursday-afternoon', 'display_order' => 16],
        ];

        foreach ($types as $type) {
            GeneralConferenceSessionType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
