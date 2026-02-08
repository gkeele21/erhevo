<?php

namespace Database\Seeders;

use App\Models\CfmSpecialTopic;
use Illuminate\Database\Seeder;

class CfmSpecialTopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topics = [
            [
                'name' => 'Christmas',
                'slug' => 'christmas',
                'description' => 'Special week focusing on the birth of Jesus Christ',
            ],
            [
                'name' => 'Easter',
                'slug' => 'easter',
                'description' => 'Special week focusing on the Atonement and Resurrection of Jesus Christ',
            ],
            [
                'name' => 'General Conference',
                'slug' => 'general-conference',
                'description' => 'Week to study and ponder General Conference messages',
            ],
        ];

        foreach ($topics as $topic) {
            CfmSpecialTopic::firstOrCreate(
                ['slug' => $topic['slug']],
                $topic
            );
        }

        $this->command->info('CFM special topics seeded: ' . CfmSpecialTopic::count());
    }
}
