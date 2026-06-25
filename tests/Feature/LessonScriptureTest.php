<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\ScriptureBook;
use App\Models\ScriptureChapter;
use App\Models\ScriptureVerse;
use App\Models\ScriptureVolume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonScriptureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed a minimal "1 Nephi 3" with 3 verses.
     */
    private function seedScripture(bool $withText = true): ScriptureChapter
    {
        $volume = ScriptureVolume::create([
            'name' => 'Book of Mormon',
            'slug' => 'book-of-mormon',
            'abbreviation' => 'BoM',
            'sort_order' => 1,
        ]);

        $book = $volume->books()->create([
            'name' => '1 Nephi',
            'slug' => '1-nephi',
            'abbreviation' => '1 Ne.',
            'sort_order' => 1,
            'chapter_count' => 1,
        ]);

        $chapter = $book->chapters()->create([
            'chapter_number' => 3,
            'verse_count' => 3,
        ]);

        foreach ([1, 2, 3] as $n) {
            ScriptureVerse::create([
                'chapter_id' => $chapter->id,
                'verse_number' => $n,
                'text' => $withText ? "Verse {$n} text." : null,
            ]);
        }

        return $chapter;
    }

    public function test_import_command_fills_verse_text_and_inserts_missing_verses(): void
    {
        $chapter = $this->seedScripture(withText: false);

        // bcbooks flat file: verse 4 doesn't exist in our seed yet.
        $dir = sys_get_temp_dir() . '/scripture-text-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/book-of-mormon-flat.json', json_encode([
            'verses' => [
                ['reference' => '1 Nephi 3:1', 'text' => 'Imported one.'],
                ['reference' => '1 Nephi 3:2', 'text' => 'Imported two.'],
                ['reference' => '1 Nephi 3:3', 'text' => 'Imported three.'],
                ['reference' => '1 Nephi 3:4', 'text' => 'Imported four.'],
            ],
        ]));

        $this->artisan('scriptures:import-text', ['--path' => $dir])
            ->assertSuccessful();

        $verses = ScriptureVerse::where('chapter_id', $chapter->id)->orderBy('verse_number')->get();
        $this->assertCount(4, $verses); // verse 4 was inserted
        $this->assertSame('Imported one.', $verses[0]->text);
        $this->assertSame('Imported four.', $verses[3]->text);

        // verse_count was corrected to reflect the inserted verse.
        $this->assertSame(4, $chapter->fresh()->verse_count);

        unlink($dir . '/book-of-mormon-flat.json');
        rmdir($dir);
    }

    public function test_scripture_text_endpoint_returns_joined_passage(): void
    {
        $chapter = $this->seedScripture();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('lessons.scripture-text', [
                'chapter_id' => $chapter->id,
                'start_verse' => 1,
                'end_verse' => 2,
            ]))
            ->assertOk()
            ->assertJson([
                'reference' => '1 Nephi 3:1-2',
                'text' => "1 Verse 1 text.\n2 Verse 2 text.",
            ]);
    }

    public function test_scripture_text_endpoint_returns_whole_chapter_when_no_verses_given(): void
    {
        $chapter = $this->seedScripture();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('lessons.scripture-text', ['chapter_id' => $chapter->id]))
            ->assertOk()
            ->assertJsonPath('reference', '1 Nephi 3')
            ->assertJsonPath('text', "1 Verse 1 text.\n2 Verse 2 text.\n3 Verse 3 text.");
    }

    public function test_a_lesson_scripture_block_persists_db_references(): void
    {
        $chapter = $this->seedScripture();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/lessons', [
            'title' => 'Scripture Lesson',
            'visibility' => 'private',
            'publish' => true,
            'items' => [
                [
                    'type' => 'scripture',
                    'content' => "1 Verse 1 text.\n2 Verse 2 text.",
                    'config' => [
                        'book_id' => $chapter->book_id,
                        'book_name' => '1 Nephi',
                        'chapter_id' => $chapter->id,
                        'chapter_number' => 3,
                        'start_verse' => 1,
                        'end_verse' => 2,
                        'reference' => '1 Nephi 3:1-2',
                    ],
                ],
            ],
        ]);

        $item = Lesson::firstOrFail()->items()->firstOrFail();

        $this->assertSame('scripture', $item->type->value);
        $this->assertSame($chapter->id, $item->config['chapter_id']);
        $this->assertSame(1, $item->config['start_verse']);
        $this->assertSame('1 Nephi 3:1-2', $item->config['reference']);
        $this->assertStringContainsString('Verse 1 text.', $item->content);
    }
}
