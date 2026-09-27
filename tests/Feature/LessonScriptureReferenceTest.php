<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\LessonItem;
use App\Models\Post;
use App\Models\ScriptureChapter;
use App\Models\ScriptureReference;
use App\Models\ScriptureVolume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonScriptureReferenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed "1 Nephi" chapters 3 and 4, each with 20 verses.
     *
     * @return array<int, ScriptureChapter> keyed by chapter number
     */
    private function seedScripture(): array
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
            'chapter_count' => 2,
        ]);

        $chapters = [];
        foreach ([3, 4] as $n) {
            $chapters[$n] = $book->chapters()->create([
                'chapter_number' => $n,
                'verse_count' => 20,
            ]);
        }

        return $chapters;
    }

    private function lessonFor(User $user): Lesson
    {
        return Lesson::create([
            'user_id' => $user->id,
            'title' => 'Obedience',
            'kind' => 'lesson',
            'visibility' => 'private',
        ]);
    }

    private function scriptureNode(ScriptureChapter $chapter, array $config = []): array
    {
        return [
            'type' => 'scripture',
            'content' => 'And it came to pass...',
            'config' => array_merge([
                'book_id' => $chapter->book_id,
                'start_chapter_id' => $chapter->id,
                'reference' => '1 Nephi 3:7',
                'start_verse' => 7,
            ], $config),
        ];
    }

    public function test_a_scripture_block_creates_a_reference(): void
    {
        $chapters = $this->seedScripture();
        $lesson = $this->lessonFor(User::factory()->create());

        $lesson->syncItems([$this->scriptureNode($chapters[3])]);

        $item = $lesson->allItems()->first();

        $this->assertCount(1, $item->scriptureReferences);
        $this->assertSame('1 Nephi 3:7', $item->scriptureReferences->first()->display_reference);
    }

    public function test_a_scripture_block_inside_a_group_creates_a_reference(): void
    {
        $chapters = $this->seedScripture();
        $lesson = $this->lessonFor(User::factory()->create());

        $lesson->syncItems([[
            'type' => 'group',
            'content' => null,
            'config' => ['title' => 'Opening'],
            'children' => [$this->scriptureNode($chapters[4], [
                'reference' => '1 Nephi 4:2',
                'start_verse' => 2,
            ])],
        ]]);

        $child = $lesson->allItems()->where('type', 'scripture')->first();

        $this->assertSame('1 Nephi 4:2', $child->scriptureReferences->first()->display_reference);
    }

    public function test_non_scripture_blocks_get_no_references(): void
    {
        $this->seedScripture();
        $lesson = $this->lessonFor(User::factory()->create());

        $lesson->syncItems([[
            'type' => 'text',
            'content' => 'My thoughts on 1 Nephi 3:7.',
            'config' => null,
        ]]);

        $this->assertSame(0, ScriptureReference::count());
    }

    public function test_resaving_a_lesson_does_not_orphan_references(): void
    {
        $chapters = $this->seedScripture();
        $lesson = $this->lessonFor(User::factory()->create());

        $lesson->syncItems([$this->scriptureNode($chapters[3])]);
        $this->assertSame(1, ScriptureReference::count());

        // syncItems mass-deletes the old rows, which skips model events — the
        // references for those items have to go with them.
        $lesson->syncItems([$this->scriptureNode($chapters[4], [
            'reference' => '1 Nephi 4:2',
            'start_verse' => 2,
        ])]);

        $this->assertSame(1, ScriptureReference::count());
        $this->assertSame('1 Nephi 4:2', ScriptureReference::first()->display_reference);
    }

    public function test_removing_a_scripture_block_removes_its_references(): void
    {
        $chapters = $this->seedScripture();
        $lesson = $this->lessonFor(User::factory()->create());

        $lesson->syncItems([$this->scriptureNode($chapters[3])]);
        $lesson->syncItems([]);

        $this->assertSame(0, ScriptureReference::count());
    }

    public function test_a_same_chapter_end_is_stored_as_no_end_chapter(): void
    {
        $chapters = $this->seedScripture();
        $lesson = $this->lessonFor(User::factory()->create());

        // The picker sends the start chapter again when the range stays put.
        $lesson->syncItems([$this->scriptureNode($chapters[3], [
            'end_chapter_id' => $chapters[3]->id,
            'end_verse' => 12,
            'reference' => '1 Nephi 3:7-12',
        ])]);

        $ref = ScriptureReference::first();

        $this->assertNull($ref->end_chapter_id);
        $this->assertSame('1 Nephi 3:7-12', $ref->display_reference);
    }

    public function test_a_passage_finds_both_posts_and_lesson_blocks(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();

        $post = Post::create([
            'post_type' => 'thought',
            'title' => 'On obedience',
            'content' => 'Thoughts.',
            'user_id' => $user->id,
            'author_type' => 'self',
            'visibility' => 'private',
            'published_at' => now(),
        ]);
        $post->syncScriptureReferences([
            ['start_chapter_id' => $chapters[3]->id, 'start_verse' => 7],
        ]);

        $this->lessonFor($user)->syncItems([$this->scriptureNode($chapters[3])]);

        // The point of the polymorphic table: one query spans both.
        $types = ScriptureReference::where('start_chapter_id', $chapters[3]->id)
            ->pluck('referenceable_type')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertSame([LessonItem::class, Post::class], $types);
    }

    public function test_force_deleting_a_post_drops_its_references_but_soft_delete_keeps_them(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();

        $post = Post::create([
            'post_type' => 'thought',
            'title' => 'On obedience',
            'content' => 'Thoughts.',
            'user_id' => $user->id,
            'author_type' => 'self',
            'visibility' => 'private',
            'published_at' => now(),
        ]);
        $post->syncScriptureReferences([
            ['start_chapter_id' => $chapters[3]->id, 'start_verse' => 7],
        ]);

        $post->delete();
        $this->assertSame(1, ScriptureReference::count(), 'a soft delete should keep references');

        $post->forceDelete();
        $this->assertSame(0, ScriptureReference::count(), 'a force delete should drop them');
    }

    private function postFor(User $user): Post
    {
        return Post::create([
            'post_type' => 'thought',
            'title' => 'On obedience',
            'content' => 'Thoughts.',
            'user_id' => $user->id,
            'author_type' => 'self',
            'visibility' => 'private',
            'published_at' => now(),
        ]);
    }

    public function test_a_writing_block_references_its_linked_passages(): void
    {
        $chapters = $this->seedScripture();
        $lesson = $this->lessonFor(User::factory()->create());

        $lesson->syncItems([[
            'type' => 'text',
            'content' => 'Go and do.',
            'config' => ['scripture_references' => [
                ['start_chapter_id' => $chapters[3]->id, 'start_verse' => 7, 'reference' => '1 Nephi 3:7'],
                ['start_chapter_id' => $chapters[4]->id, 'reference' => '1 Nephi 4'],
            ]],
        ]]);

        $refs = $lesson->allItems()->first()->scriptureReferences;

        $this->assertSame(['1 Nephi 3:7', '1 Nephi 4'], $refs->map->display_reference->all());
    }

    public function test_saving_a_lesson_keeps_a_writing_blocks_passages_and_other_config(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/lessons', [
            'title' => 'Obedience',
            'visibility' => 'private',
            'publish' => true,
            'items' => [[
                'type' => 'text',
                'content' => '<p>Go and do.</p>',
                'config' => [
                    'emphasis' => 'key',
                    'scripture_references' => [
                        ['start_chapter_id' => $chapters[3]->id, 'start_verse' => 7, 'reference' => '1 Nephi 3:7'],
                    ],
                ],
            ]],
        ])->assertRedirect();

        $item = Lesson::firstOrFail()->allItems()->first();

        $this->assertSame('key', $item->config['emphasis']);
        $this->assertSame(['1 Nephi 3:7'], $item->scriptureReferences->map->display_reference->all());
    }

    public function test_a_lesson_rejects_a_passage_in_an_unknown_chapter(): void
    {
        $this->seedScripture();

        $this->actingAs(User::factory()->create())->post('/lessons', [
            'title' => 'Obedience',
            'visibility' => 'private',
            'items' => [[
                'type' => 'text',
                'content' => '<p>Go and do.</p>',
                'config' => ['scripture_references' => [['start_chapter_id' => 999999]]],
            ]],
        ])->assertSessionHasErrors('items.0.config.scripture_references.0.start_chapter_id');

        $this->assertSame(0, Lesson::count());
    }

    public function test_a_post_backed_writing_block_leaves_the_references_to_the_post(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();
        $post = $this->postFor($user);

        $this->lessonFor($user)->syncItems([[
            'type' => 'scripture_help',
            'content' => 'Context.',
            'post_id' => $post->id,
            'config' => ['post_title' => $post->title, 'scripture_references' => [
                ['start_chapter_id' => $chapters[3]->id, 'start_verse' => 7],
            ]],
        ]]);

        $this->assertSame(0, ScriptureReference::where('referenceable_type', LessonItem::class)->count());
    }

    public function test_saving_block_writing_as_a_post_carries_its_passages(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('lessons.save-post'), [
            'content' => '<p>Go and do.</p>',
            'post_type' => 'thought',
            'scripture_references' => [
                ['start_chapter_id' => $chapters[3]->id, 'start_verse' => 7, 'reference' => '1 Nephi 3:7'],
            ],
        ])->assertOk();

        $post = Post::find($response->json('id'));

        $this->assertSame(['1 Nephi 3:7'], $post->scriptureReferences->map->display_reference->all());
    }

    public function test_a_block_can_update_its_posts_passages(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();
        $post = $this->postFor($user);
        $post->syncScriptureReferences([['start_chapter_id' => $chapters[3]->id]]);

        $this->actingAs($user)
            ->putJson(route('lessons.post-scripture-references', $post->id), [
                'scripture_references' => [
                    ['start_chapter_id' => $chapters[4]->id, 'start_verse' => 2, 'end_verse' => 5],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('scripture_references.0.reference', '1 Nephi 4:2-5');

        $this->assertSame(['1 Nephi 4:2-5'], $post->fresh()->scriptureReferences->map->display_reference->all());
    }

    public function test_only_the_owner_can_update_a_posts_passages(): void
    {
        $chapters = $this->seedScripture();
        $post = $this->postFor(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->putJson(route('lessons.post-scripture-references', $post->id), [
                'scripture_references' => [['start_chapter_id' => $chapters[4]->id]],
            ])
            ->assertForbidden();

        $this->assertSame(0, ScriptureReference::count());
    }

    public function test_post_search_includes_linked_passages(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();
        $this->postFor($user)->syncScriptureReferences([
            ['start_chapter_id' => $chapters[3]->id, 'start_verse' => 7],
        ]);

        $this->actingAs($user)
            ->getJson(route('lessons.post-search'))
            ->assertOk()
            ->assertJsonPath('0.scripture_references.0.reference', '1 Nephi 3:7');
    }
}
