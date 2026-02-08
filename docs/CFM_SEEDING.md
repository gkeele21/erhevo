# Seeding Scripture and CFM Data

## Overview

The CFM feature requires seeding:
1. Scripture structure (volumes, books, chapters, verses)
2. CFM study schedule (years, weeks, chapter assignments)
3. Special topics (Christmas, Easter, General Conference)
4. Optionally, publisher data

## Scripture Data

### Data Source

Scripture structure data (verse counts per chapter) can be obtained from:
- LDS Church scripture library API
- Open scripture databases
- Manual compilation from printed editions

### JSON Format

Create `database/data/scriptures.json`:

```json
{
  "volumes": [
    {
      "name": "Book of Mormon",
      "slug": "book-of-mormon",
      "abbreviation": "BoM",
      "sort_order": 1,
      "books": [
        {
          "name": "1 Nephi",
          "slug": "1-nephi",
          "abbreviation": "1 Ne.",
          "sort_order": 1,
          "chapters": [
            { "number": 1, "verse_count": 20 },
            { "number": 2, "verse_count": 24 },
            { "number": 3, "verse_count": 31 }
          ]
        }
      ]
    }
  ]
}
```

### Seeder

```php
class ScriptureSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('data/scriptures.json')),
            true
        );

        foreach ($data['volumes'] as $volumeData) {
            $volume = ScriptureVolume::create([
                'name' => $volumeData['name'],
                'slug' => $volumeData['slug'],
                'abbreviation' => $volumeData['abbreviation'],
                'sort_order' => $volumeData['sort_order'],
            ]);

            foreach ($volumeData['books'] as $bookData) {
                $book = $volume->books()->create([
                    'name' => $bookData['name'],
                    'slug' => $bookData['slug'],
                    'abbreviation' => $bookData['abbreviation'],
                    'sort_order' => $bookData['sort_order'],
                    'chapter_count' => count($bookData['chapters']),
                ]);

                foreach ($bookData['chapters'] as $chapterData) {
                    $chapter = $book->chapters()->create([
                        'chapter_number' => $chapterData['number'],
                        'verse_count' => $chapterData['verse_count'],
                    ]);

                    // Create verse records
                    for ($v = 1; $v <= $chapterData['verse_count']; $v++) {
                        $chapter->verses()->create(['verse_number' => $v]);
                    }
                }
            }
        }
    }
}
```

## CFM Schedule Data

### Data Source

The official CFM schedule is published annually at:
- churchofjesuschrist.org/study/come-follow-me

### JSON Format

Create `database/data/cfm-schedule-2024.json`:

```json
{
  "year": 2024,
  "title": "Book of Mormon 2024",
  "volumes": ["book-of-mormon"],
  "weeks": [
    {
      "week": 1,
      "start_date": "2024-01-01",
      "end_date": "2024-01-07",
      "title": "1 Nephi 1-5",
      "chapters": [
        "1-nephi:1",
        "1-nephi:2",
        "1-nephi:3",
        "1-nephi:4",
        "1-nephi:5"
      ]
    },
    {
      "week": 52,
      "start_date": "2024-12-23",
      "end_date": "2024-12-29",
      "title": "Christmas",
      "is_special": true,
      "special_topic": "christmas"
    }
  ]
}
```

### Seeder

```php
class CfmScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $scheduleFiles = glob(database_path('data/cfm-schedule-*.json'));

        foreach ($scheduleFiles as $file) {
            $data = json_decode(file_get_contents($file), true);

            $studyYear = CfmStudyYear::create([
                'year' => $data['year'],
                'title' => $data['title'],
            ]);

            // Link volumes
            foreach ($data['volumes'] as $volumeSlug) {
                $volume = ScriptureVolume::where('slug', $volumeSlug)->first();
                $studyYear->volumes()->attach($volume);
            }

            // Create weeks
            foreach ($data['weeks'] as $weekData) {
                $week = $studyYear->weeks()->create([
                    'week_number' => $weekData['week'],
                    'start_date' => $weekData['start_date'],
                    'end_date' => $weekData['end_date'],
                    'title' => $weekData['title'],
                    'slug' => Str::slug($weekData['title']),
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

                // Link special topic
                if (!empty($weekData['special_topic'])) {
                    $topic = CfmSpecialTopic::where('slug', $weekData['special_topic'])->first();
                    if ($topic) {
                        $week->specialTopics()->attach($topic);
                    }
                }
            }
        }
    }

    private function resolveChapter(string $ref): ?ScriptureChapter
    {
        [$bookSlug, $chapterNum] = explode(':', $ref);

        return ScriptureChapter::whereHas('book', function ($q) use ($bookSlug) {
            $q->where('slug', $bookSlug);
        })->where('chapter_number', $chapterNum)->first();
    }
}
```

## Special Topics Seeder

```php
class CfmSpecialTopicSeeder extends Seeder
{
    public function run(): void
    {
        $topics = [
            ['name' => 'Christmas', 'slug' => 'christmas'],
            ['name' => 'Easter', 'slug' => 'easter'],
            ['name' => 'General Conference', 'slug' => 'general-conference'],
        ];

        foreach ($topics as $topic) {
            CfmSpecialTopic::create($topic);
        }
    }
}
```

## Running Seeders

```bash
# Seed scripture structure (run once)
php artisan db:seed --class=ScriptureSeeder

# Seed special topics (run once)
php artisan db:seed --class=CfmSpecialTopicSeeder

# Seed CFM schedules (run annually with new data)
php artisan db:seed --class=CfmScheduleSeeder
```

## Record Counts

Expected approximate record counts:
- Scripture volumes: 5
- Scripture books: ~85
- Scripture chapters: ~1,600
- Scripture verses: ~42,000
- CFM weeks per year: ~52
