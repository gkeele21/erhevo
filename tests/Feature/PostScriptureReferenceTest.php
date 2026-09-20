<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\ScriptureChapter;
use App\Models\ScriptureVolume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostScriptureReferenceTest extends TestCase
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

    private function postPayload(array $overrides = []): array
    {
        return array_merge([
            'post_type' => 'thought',
            'title' => 'On obedience',
            'content' => 'Some thoughts worth keeping.',
            'author_type' => 'self',
            'visibility' => 'private',
            'publish' => true,
        ], $overrides);
    }

    public function test_storing_a_post_links_its_scripture_references(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('posts.store'), $this->postPayload([
                'scripture_references' => [
                    ['start_chapter_id' => $chapters[3]->id, 'start_verse' => 7],
                ],
            ]))
            ->assertRedirect();

        $post = Post::latest('id')->first();

        $this->assertCount(1, $post->scriptureReferences);
        $this->assertSame('1 Nephi 3:7', $post->scriptureReferences->first()->display_reference);
    }

    public function test_a_cross_chapter_range_round_trips(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('posts.store'), $this->postPayload([
                'scripture_references' => [[
                    'start_chapter_id' => $chapters[3]->id,
                    'start_verse' => 15,
                    'end_chapter_id' => $chapters[4]->id,
                    'end_verse' => 5,
                ]],
            ]))
            ->assertRedirect();

        $this->assertSame(
            '1 Nephi 3:15-4:5',
            Post::latest('id')->first()->scriptureReferences->first()->display_reference
        );
    }

    public function test_updating_a_post_replaces_its_references(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), $this->postPayload([
            'scripture_references' => [
                ['start_chapter_id' => $chapters[3]->id, 'start_verse' => 7],
            ],
        ]));

        $post = Post::latest('id')->first();

        $this->actingAs($user)
            ->put(route('posts.update', $post), $this->postPayload([
                'scripture_references' => [
                    ['start_chapter_id' => $chapters[4]->id, 'start_verse' => 2],
                ],
            ]))
            ->assertRedirect();

        $post->refresh()->load('scriptureReferences');

        $this->assertCount(1, $post->scriptureReferences);
        $this->assertSame('1 Nephi 4:2', $post->scriptureReferences->first()->display_reference);
    }

    public function test_an_empty_array_clears_references(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), $this->postPayload([
            'scripture_references' => [
                ['start_chapter_id' => $chapters[3]->id, 'start_verse' => 7],
            ],
        ]));

        $post = Post::latest('id')->first();

        $this->actingAs($user)->put(route('posts.update', $post), $this->postPayload([
            'scripture_references' => [],
        ]));

        $this->assertCount(0, $post->refresh()->scriptureReferences);
    }

    public function test_an_unknown_chapter_is_rejected(): void
    {
        $this->seedScripture();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('posts.store'), $this->postPayload([
                'scripture_references' => [
                    ['start_chapter_id' => 99999, 'start_verse' => 7],
                ],
            ]))
            ->assertSessionHasErrors('scripture_references.0.start_chapter_id');

        $this->assertSame(0, Post::count());
    }

    public function test_backfill_extracts_references_from_post_content(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();

        // The closing/opening tag pair is the case that used to swallow the
        // verse number and record a whole-chapter reference instead.
        $post = Post::create([
            'post_type' => 'thought',
            'title' => 'Obedience',
            'content' => '<p>As taught in 1 Nephi 3:7</p><p>See also First Nephi 4:2.</p>',
            'user_id' => $user->id,
            'author_type' => 'self',
            'visibility' => 'private',
            'published_at' => now(),
        ]);

        $this->artisan('posts:backfill-scripture-references')->assertSuccessful();

        $refs = $post->refresh()->scriptureReferences->pluck('display_reference')->all();

        $this->assertContains('1 Nephi 3:7', $refs);
        $this->assertContains('1 Nephi 4:2', $refs);
    }

    public function test_backfill_drops_a_whole_chapter_when_verses_are_also_named(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();

        $post = Post::create([
            'post_type' => 'thought',
            'title' => 'Obedience',
            // Both shapes for chapter 3; only the verse-level one should survive.
            // Chapter 4 is named on its own, so it stays.
            'content' => '<p>1 Nephi 3 teaches us, as it says in 1 Nephi 3:7.</p><p>See 1 Nephi 4.</p>',
            'user_id' => $user->id,
            'author_type' => 'self',
            'visibility' => 'private',
            'published_at' => now(),
        ]);

        $this->artisan('posts:backfill-scripture-references')->assertSuccessful();

        $refs = $post->refresh()->scriptureReferences->pluck('display_reference')->sort()->values()->all();

        $this->assertSame(['1 Nephi 3:7', '1 Nephi 4'], $refs);
    }

    public function test_backfill_skips_posts_that_already_have_references(): void
    {
        $chapters = $this->seedScripture();
        $user = User::factory()->create();

        $post = Post::create([
            'post_type' => 'thought',
            'title' => 'Obedience',
            'content' => '<p>As taught in 1 Nephi 3:7.</p>',
            'user_id' => $user->id,
            'author_type' => 'self',
            'visibility' => 'private',
            'published_at' => now(),
        ]);

        // A hand-picked reference the scan would not produce.
        $post->syncScriptureReferences([
            ['start_chapter_id' => $chapters[4]->id, 'start_verse' => 18],
        ]);

        $this->artisan('posts:backfill-scripture-references')->assertSuccessful();

        $this->assertSame(
            ['1 Nephi 4:18'],
            $post->refresh()->scriptureReferences->pluck('display_reference')->all()
        );
    }
}
