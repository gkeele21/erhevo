<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Post;
use App\Models\ScriptureBook;
use App\Models\ScriptureChapter;
use App\Models\ScriptureVerse;
use App\Models\ScriptureVolume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScriptureBrowseTest extends TestCase
{
    use RefreshDatabase;

    private ScriptureBook $book;

    /** @var array<int, ScriptureChapter> keyed by chapter number */
    private array $chapters;

    protected function setUp(): void
    {
        parent::setUp();

        $volume = ScriptureVolume::create([
            'name' => 'Book of Mormon',
            'slug' => 'book-of-mormon',
            'abbreviation' => 'BoM',
            'sort_order' => 1,
        ]);

        $this->book = $volume->books()->create([
            'name' => '1 Nephi',
            'slug' => '1-nephi',
            'abbreviation' => '1 Ne.',
            'sort_order' => 1,
            'chapter_count' => 3,
        ]);

        foreach ([1, 2, 3] as $n) {
            $chapter = $this->book->chapters()->create([
                'chapter_number' => $n,
                'verse_count' => 10,
            ]);

            foreach (range(1, 10) as $v) {
                ScriptureVerse::create([
                    'chapter_id' => $chapter->id,
                    'verse_number' => $v,
                    'text' => "Chapter {$n} verse {$v}.",
                ]);
            }

            $this->chapters[$n] = $chapter;
        }
    }

    private function postFor(User $user, array $attributes, array $references): Post
    {
        $post = Post::create(array_merge([
            'post_type' => 'thought',
            'title' => 'On obedience',
            'content' => 'Thoughts.',
            'excerpt' => 'A short summary.',
            'user_id' => $user->id,
            'author_type' => 'self',
            'visibility' => 'private',
            'published_at' => now(),
        ], $attributes));

        $post->syncScriptureReferences($references);

        return $post;
    }

    private function chapterUrl(int $number): string
    {
        return route('scriptures.show', ['book' => $this->book->slug, 'chapter' => $number]);
    }

    public function test_a_chapter_page_renders_its_verses(): void
    {
        $this->actingAs(User::factory()->create())
            ->get($this->chapterUrl(2))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Scriptures/Show')
                ->where('chapter.reference', '1 Nephi 2')
                ->has('verses', 10)
                ->where('verses.0.text', 'Chapter 2 verse 1.')
                ->where('prevChapter', 1)
                ->where('nextChapter', 3));
    }

    public function test_your_own_post_appears_on_the_passage(): void
    {
        $user = User::factory()->create();

        $this->postFor($user, ['title' => 'Nephi goes back'], [
            ['start_chapter_id' => $this->chapters[2]->id, 'start_verse' => 4],
        ]);

        $this->actingAs($user)
            ->get($this->chapterUrl(2))
            ->assertInertia(fn ($page) => $page
                ->has('entries', 1)
                ->where('entries.0.title', 'Nephi goes back')
                ->where('entries.0.kind', 'post')
                ->where('entries.0.passages.0', '1 Nephi 2:4'));
    }

    public function test_another_users_private_post_is_hidden(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();

        $this->postFor($author, ['visibility' => 'private'], [
            ['start_chapter_id' => $this->chapters[2]->id, 'start_verse' => 4],
        ]);

        $this->actingAs($stranger)
            ->get($this->chapterUrl(2))
            ->assertInertia(fn ($page) => $page->has('entries', 0));

        $this->actingAs($author)
            ->get($this->chapterUrl(2))
            ->assertInertia(fn ($page) => $page->has('entries', 1));
    }

    public function test_a_public_post_is_visible_to_everyone(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();

        $this->postFor($author, ['visibility' => 'public'], [
            ['start_chapter_id' => $this->chapters[2]->id],
        ]);

        $this->actingAs($stranger)
            ->get($this->chapterUrl(2))
            ->assertInertia(fn ($page) => $page->has('entries', 1));
    }

    public function test_an_unpublished_post_is_hidden(): void
    {
        $author = User::factory()->create();

        $this->postFor($author, ['visibility' => 'public', 'published_at' => null], [
            ['start_chapter_id' => $this->chapters[2]->id],
        ]);

        $this->actingAs(User::factory()->create())
            ->get($this->chapterUrl(2))
            ->assertInertia(fn ($page) => $page->has('entries', 0));
    }

    public function test_a_lesson_block_appears_on_the_passage(): void
    {
        $user = User::factory()->create();

        $lesson = Lesson::create([
            'user_id' => $user->id,
            'title' => 'Faith and obedience',
            'description' => 'A lesson outline.',
            'kind' => 'lesson',
            'visibility' => 'private',
            'published_at' => now(),
        ]);

        $lesson->syncItems([[
            'type' => 'scripture',
            'content' => 'text',
            'config' => [
                'start_chapter_id' => $this->chapters[2]->id,
                'start_verse' => 4,
                'reference' => '1 Nephi 2:4',
            ],
        ]]);

        $this->actingAs($user)
            ->get($this->chapterUrl(2))
            ->assertInertia(fn ($page) => $page
                ->has('entries', 1)
                ->where('entries.0.kind', 'lesson')
                ->where('entries.0.title', 'Faith and obedience'));
    }

    public function test_a_lesson_using_a_passage_twice_appears_once(): void
    {
        $user = User::factory()->create();

        $lesson = Lesson::create([
            'user_id' => $user->id,
            'title' => 'Faith and obedience',
            'kind' => 'lesson',
            'visibility' => 'private',
            'published_at' => now(),
        ]);

        $lesson->syncItems([
            ['type' => 'scripture', 'content' => '', 'config' => [
                'start_chapter_id' => $this->chapters[2]->id, 'start_verse' => 4,
            ]],
            ['type' => 'scripture', 'content' => '', 'config' => [
                'start_chapter_id' => $this->chapters[2]->id, 'start_verse' => 9,
            ]],
        ]);

        $this->actingAs($user)
            ->get($this->chapterUrl(2))
            ->assertInertia(fn ($page) => $page
                ->has('entries', 1)
                ->has('entries.0.passages', 2));
    }

    public function test_a_range_spanning_a_chapter_covers_the_middle(): void
    {
        $user = User::factory()->create();

        // "1 Nephi 1:5-3:2" never names chapter 2, but runs straight through it.
        $this->postFor($user, ['title' => 'The whole journey'], [[
            'start_chapter_id' => $this->chapters[1]->id,
            'start_verse' => 5,
            'end_chapter_id' => $this->chapters[3]->id,
            'end_verse' => 2,
        ]]);

        $this->actingAs($user)
            ->get($this->chapterUrl(2))
            ->assertInertia(fn ($page) => $page
                ->has('entries', 1)
                ->where('entries.0.title', 'The whole journey')
                // Every verse of the passed-through chapter is covered.
                ->has('verses.0.entry_ids', 1)
                ->has('verses.9.entry_ids', 1));
    }

    public function test_verses_carry_only_the_entries_that_name_them(): void
    {
        $user = User::factory()->create();

        $this->postFor($user, ['title' => 'On verses 4 to 6'], [[
            'start_chapter_id' => $this->chapters[2]->id,
            'start_verse' => 4,
            'end_verse' => 6,
        ]]);

        $this->actingAs($user)
            ->get($this->chapterUrl(2))
            ->assertInertia(fn ($page) => $page
                ->has('verses.2.entry_ids', 0)   // verse 3
                ->has('verses.3.entry_ids', 1)   // verse 4
                ->has('verses.5.entry_ids', 1)   // verse 6
                ->has('verses.6.entry_ids', 0)); // verse 7
    }

    public function test_a_whole_chapter_reference_covers_every_verse(): void
    {
        $user = User::factory()->create();

        $this->postFor($user, [], [
            ['start_chapter_id' => $this->chapters[2]->id],
        ]);

        $this->actingAs($user)
            ->get($this->chapterUrl(2))
            ->assertInertia(fn ($page) => $page
                ->has('verses.0.entry_ids', 1)
                ->has('verses.9.entry_ids', 1));
    }

    public function test_an_unknown_chapter_is_a_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->get($this->chapterUrl(99))
            ->assertNotFound();
    }

    public function test_the_index_lists_volumes_and_books(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('scriptures.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Scriptures/Index')
                ->where('volumes.0.name', 'Book of Mormon')
                ->where('volumes.0.books.0.name', '1 Nephi'));
    }
}
