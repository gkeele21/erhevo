<?php

namespace Database\Seeders;

use App\Models\ScriptureBook;
use App\Models\ScriptureChapter;
use App\Models\ScriptureVerse;
use App\Models\ScriptureVolume;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScriptureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding scripture data...');

        // Load all scripture JSON files
        $files = [
            database_path('data/scriptures.json'),
            database_path('data/scriptures-bible.json'),
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                $this->seedFromFile($file);
            } else {
                $this->command->warn("Scripture file not found: {$file}");
            }
        }

        $this->command->info('Scripture seeding complete!');
        $this->command->info('  Volumes: ' . ScriptureVolume::count());
        $this->command->info('  Books: ' . ScriptureBook::count());
        $this->command->info('  Chapters: ' . ScriptureChapter::count());
        $this->command->info('  Verses: ' . ScriptureVerse::count());
    }

    /**
     * Seed scripture data from a JSON file.
     */
    protected function seedFromFile(string $filePath): void
    {
        $this->command->info("Loading: " . basename($filePath));

        $data = json_decode(file_get_contents($filePath), true);

        if (!isset($data['volumes'])) {
            $this->command->warn("No volumes found in: {$filePath}");
            return;
        }

        foreach ($data['volumes'] as $volumeData) {
            $this->seedVolume($volumeData);
        }
    }

    /**
     * Seed a single volume and its contents.
     */
    protected function seedVolume(array $volumeData): void
    {
        // Check if volume already exists
        $volume = ScriptureVolume::where('slug', $volumeData['slug'])->first();

        if (!$volume) {
            $volume = ScriptureVolume::create([
                'name' => $volumeData['name'],
                'slug' => $volumeData['slug'],
                'abbreviation' => $volumeData['abbreviation'],
                'sort_order' => $volumeData['sort_order'],
            ]);
            $this->command->info("  Created volume: {$volume->name}");
        } else {
            $this->command->info("  Volume exists: {$volume->name}");
        }

        foreach ($volumeData['books'] as $bookData) {
            $this->seedBook($volume, $bookData);
        }
    }

    /**
     * Seed a single book and its contents.
     */
    protected function seedBook(ScriptureVolume $volume, array $bookData): void
    {
        // Check if book already exists
        $book = ScriptureBook::where('volume_id', $volume->id)
            ->where('slug', $bookData['slug'])
            ->first();

        if (!$book) {
            $book = $volume->books()->create([
                'name' => $bookData['name'],
                'slug' => $bookData['slug'],
                'abbreviation' => $bookData['abbreviation'],
                'sort_order' => $bookData['sort_order'],
                'chapter_count' => count($bookData['chapters']),
            ]);
        }

        foreach ($bookData['chapters'] as $chapterData) {
            $this->seedChapter($book, $chapterData);
        }
    }

    /**
     * Seed a single chapter and its verses.
     */
    protected function seedChapter(ScriptureBook $book, array $chapterData): void
    {
        // Check if chapter already exists
        $chapter = ScriptureChapter::where('book_id', $book->id)
            ->where('chapter_number', $chapterData['number'])
            ->first();

        if ($chapter) {
            return;
        }

        $chapter = $book->chapters()->create([
            'chapter_number' => $chapterData['number'],
            'verse_count' => $chapterData['verse_count'],
        ]);

        // Create verse records in batches for performance
        $verses = [];
        $now = now();

        for ($v = 1; $v <= $chapterData['verse_count']; $v++) {
            $verses[] = [
                'chapter_id' => $chapter->id,
                'verse_number' => $v,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Insert in batches of 100
            if (count($verses) >= 100) {
                DB::table('scripture_verses')->insert($verses);
                $verses = [];
            }
        }

        // Insert remaining verses
        if (!empty($verses)) {
            DB::table('scripture_verses')->insert($verses);
        }
    }
}
