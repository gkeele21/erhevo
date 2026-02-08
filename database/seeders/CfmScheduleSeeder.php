<?php

namespace Database\Seeders;

use App\Models\CfmSpecialTopic;
use App\Models\CfmStudyYear;
use App\Models\ScriptureBook;
use App\Models\ScriptureChapter;
use App\Models\ScriptureVolume;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CfmScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding CFM schedule data...');

        $scheduleFiles = glob(database_path('data/cfm-schedule-*.json'));

        if (empty($scheduleFiles)) {
            $this->command->warn('No CFM schedule files found in database/data/');
            return;
        }

        foreach ($scheduleFiles as $file) {
            $this->seedFromFile($file);
        }

        $this->command->info('CFM schedule seeding complete!');
        $this->command->info('  Study years: ' . CfmStudyYear::count());
    }

    /**
     * Seed CFM schedule from a JSON file.
     */
    protected function seedFromFile(string $filePath): void
    {
        $this->command->info("Loading: " . basename($filePath));

        $data = json_decode(file_get_contents($filePath), true);

        if (!isset($data['year'])) {
            $this->command->warn("Invalid schedule file: {$filePath}");
            return;
        }

        // Check if already seeded
        if (CfmStudyYear::where('year', $data['year'])->exists()) {
            $this->command->info("  Year {$data['year']} already exists, skipping");
            return;
        }

        // Create study year
        $studyYear = CfmStudyYear::create([
            'year' => $data['year'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        // Link volumes
        if (!empty($data['volumes'])) {
            foreach ($data['volumes'] as $volumeSlug) {
                $volume = ScriptureVolume::where('slug', $volumeSlug)->first();
                if ($volume) {
                    $studyYear->volumes()->attach($volume);
                }
            }
        }

        // Create weeks
        foreach ($data['weeks'] as $weekData) {
            $this->seedWeek($studyYear, $weekData);
        }

        $this->command->info("  Created year: {$studyYear->title} with " . $studyYear->weeks()->count() . " weeks");
    }

    /**
     * Seed a single CFM week.
     */
    protected function seedWeek(CfmStudyYear $studyYear, array $weekData): void
    {
        $week = $studyYear->weeks()->create([
            'week_number' => $weekData['week'],
            'start_date' => $weekData['start_date'],
            'end_date' => $weekData['end_date'],
            'title' => $weekData['title'],
            'slug' => Str::slug($weekData['title'] . '-' . $weekData['week']),
            'description' => $weekData['description'] ?? null,
            'is_special_topic' => $weekData['is_special'] ?? false,
        ]);

        // Link chapters
        if (!empty($weekData['chapters'])) {
            foreach ($weekData['chapters'] as $chapterRef) {
                $chapter = $this->resolveChapter($chapterRef);
                if ($chapter) {
                    $week->chapters()->attach($chapter);
                }
            }
        }

        // Link special topics
        if (!empty($weekData['special_topic'])) {
            $topic = CfmSpecialTopic::where('slug', $weekData['special_topic'])->first();
            if ($topic) {
                $week->specialTopics()->attach($topic);
            }
        }
    }

    /**
     * Resolve a chapter reference like "1-nephi:3" to a ScriptureChapter model.
     */
    protected function resolveChapter(string $ref): ?ScriptureChapter
    {
        $parts = explode(':', $ref);
        if (count($parts) !== 2) {
            return null;
        }

        [$bookSlug, $chapterNum] = $parts;

        return ScriptureChapter::whereHas('book', function ($query) use ($bookSlug) {
            $query->where('slug', $bookSlug);
        })->where('chapter_number', $chapterNum)->first();
    }
}
