<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsFromJson;
use Illuminate\Database\Seeder;

/**
 * Church-calling types that ChurchOrganizationSeeder does NOT create (ids 30-35):
 * the historical First Presidency roles (Third Counselor, Counselor, Assistant
 * Counselor, Assistant President) and "Assistant to the Twelve". Authors and
 * talks reference these, so they must be seeded right after ChurchOrganizationSeeder.
 */
class ExtraChurchCallingSeeder extends Seeder
{
    use SeedsFromJson;

    public function run(): void
    {
        $n = $this->seedFromJson('church_callings_extra.json', 'church_callings');
        $this->command?->info("  Seeded {$n} additional church callings.");
    }
}
