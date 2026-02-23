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

        // Link chapters (with optional verse ranges)
        if (!empty($weekData['chapters'])) {
            foreach ($weekData['chapters'] as $chapterRef) {
                $resolved = $this->resolveChapterWithVerses($chapterRef);
                if ($resolved['chapter']) {
                    $week->chapters()->attach($resolved['chapter'], [
                        'start_verse' => $resolved['start_verse'],
                        'end_verse' => $resolved['end_verse'],
                    ]);
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
        $resolved = $this->resolveChapterWithVerses($ref);
        return $resolved['chapter'];
    }

    /**
     * Resolve a chapter reference with optional verse range.
     *
     * Supported formats:
     *   "book-slug:chapter" - full chapter (e.g., "doctrine-and-covenants:1")
     *   "book-slug:chapter:startVerse-endVerse" - verse range (e.g., "joseph-smith-history:1:1-26")
     *   "book-slug:chapter:verse" - single verse (e.g., "joseph-smith-history:1:17")
     *
     * @return array{chapter: ?ScriptureChapter, start_verse: ?int, end_verse: ?int}
     */
    protected function resolveChapterWithVerses(string $ref): array
    {
        $parts = explode(':', $ref);

        if (count($parts) < 2) {
            return ['chapter' => null, 'start_verse' => null, 'end_verse' => null];
        }

        $bookSlug = $parts[0];
        $chapterNum = $parts[1];
        $startVerse = null;
        $endVerse = null;

        // Parse optional verse specification
        if (isset($parts[2])) {
            $versePart = $parts[2];
            if (str_contains($versePart, '-')) {
                // Verse range: "1-26"
                [$startVerse, $endVerse] = array_map('intval', explode('-', $versePart));
            } else {
                // Single verse: "17"
                $startVerse = (int) $versePart;
                $endVerse = $startVerse;
            }
        }

        $chapter = ScriptureChapter::whereHas('book', function ($query) use ($bookSlug) {
            $query->where('slug', $bookSlug);
        })->where('chapter_number', $chapterNum)->first();

        return [
            'chapter' => $chapter,
            'start_verse' => $startVerse,
            'end_verse' => $endVerse,
        ];
    }
}
