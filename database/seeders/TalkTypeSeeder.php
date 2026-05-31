<?php

namespace Database\Seeders;

use App\Models\TalkType;
use Illuminate\Database\Seeder;

class TalkTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Conference Talk', 'slug' => 'conference-talk', 'display_order' => 1],
            ['name' => 'Devotional', 'slug' => 'devotional', 'display_order' => 2],
            ['name' => 'Forum', 'slug' => 'forum', 'display_order' => 3],
            ['name' => 'Fireside', 'slug' => 'fireside', 'display_order' => 4],
            ['name' => 'Commencement', 'slug' => 'commencement', 'display_order' => 5],
            ['name' => 'Education Week', 'slug' => 'education-week', 'display_order' => 6],
            ['name' => 'Women\'s Conference', 'slug' => 'womens-conference', 'display_order' => 7],
            ['name' => 'Article', 'slug' => 'article', 'display_order' => 8],
            ['name' => 'Message', 'slug' => 'message', 'display_order' => 9],
            ['name' => 'Other', 'slug' => 'other', 'display_order' => 99],
        ];

        foreach ($types as $type) {
            TalkType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
