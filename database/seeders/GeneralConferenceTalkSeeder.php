<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class GeneralConferenceTalkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Import talks from the JSON data file
        Artisan::call('gc:import-talks', [
            'file' => database_path('data/general_conference_talks.json'),
        ]);

        $this->command->info(Artisan::output());
    }
}
