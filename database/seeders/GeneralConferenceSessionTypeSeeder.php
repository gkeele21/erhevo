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
        ];

        foreach ($types as $type) {
            GeneralConferenceSessionType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
