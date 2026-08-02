<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_create_a_lesson(): void
    {
        $this->post('/lessons', [
            'title' => 'Faith',
            'visibility' => 'private',
        ])->assertRedirect('/login');
    }

    public function test_a_user_can_create_a_lesson_with_ordered_items(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/lessons', [
            'title' => 'Faith in Jesus Christ',
            'description' => 'A lesson about faith.',
            'visibility' => 'private',
            'publish' => true,
            'items' => [
                ['type' => 'text', 'content' => '<p>Welcome</p>', 'config' => null],
                ['type' => 'scripture', 'content' => null, 'config' => ['reference' => '1 Nephi 3:7']],
                ['type' => 'question', 'content' => 'What is faith?', 'config' => null],
            ],
        ]);

        $lesson = Lesson::firstOrFail();

        $response->assertRedirect(route('lessons.show', $lesson));

        $this->assertSame($user->id, $lesson->user_id);
        $this->assertNotNull($lesson->published_at);

        $items = $lesson->items()->get();
        $this->assertCount(3, $items);

        // Order is preserved via sort_order.
        $this->assertSame(['text', 'scripture', 'question'], $items->pluck('type.value')->all());
        $this->assertSame([0, 1, 2], $items->pluck('sort_order')->all());

        // Type-specific data round-trips through the JSON config / content columns.
        $this->assertSame('<p>Welcome</p>', $items[0]->content);
        $this->assertSame('1 Nephi 3:7', $items[1]->config['reference']);
        $this->assertSame('What is faith?', $items[2]->content);
    }

    public function test_custom_visibility_lesson_shows_only_to_selected_friends(): void
    {
        $owner = User::factory()->create();
        $chosen = User::factory()->create();
        $otherFriend = User::factory()->create();
        \App\Models\Friendship::create(['requester_id' => $owner->id, 'addressee_id' => $chosen->id, 'status' => 'accepted']);
        \App\Models\Friendship::create(['requester_id' => $owner->id, 'addressee_id' => $otherFriend->id, 'status' => 'accepted']);

        $this->actingAs($owner)->post('/lessons', [
            'title' => 'Private Group Lesson',
            'visibility' => 'custom',
            'shared_user_ids' => [$chosen->id],
            'publish' => true,
            'items' => [],
        ])->assertSessionHasNoErrors();

        $lesson = Lesson::firstOrFail();

        $this->actingAs($chosen)->get(route('lessons.show', $lesson->slug))->assertOk();
        $this->actingAs($otherFriend)->get(route('lessons.show', $lesson->slug))->assertForbidden();
    }

    public function test_a_user_can_write_a_talk(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/lessons', [
            'title' => 'My Sacrament Talk',
            'kind' => 'talk',
            'visibility' => 'friends',
            'publish' => true,
            // A CFM week id is ignored for talks even if submitted.
            'cfm_week_id' => null,
            'items' => [
                ['type' => 'text', 'content' => '<p>Opening thought</p>', 'config' => null],
            ],
        ])->assertSessionHasNoErrors();

        $talk = Lesson::firstOrFail();
        $this->assertSame('talk', $talk->kind);
        $this->assertNull($talk->cfm_week_id);

        // Updating without resending kind keeps it a talk.
        $this->actingAs($user)->put(route('lessons.update', $talk->slug), [
            'title' => 'My Sacrament Talk (revised)',
            'visibility' => 'friends',
            'publish' => true,
            'items' => [],
        ])->assertSessionHasNoErrors();

        $this->assertSame('talk', $talk->fresh()->kind);
    }

    private function makePost(User $user, string $type, string $title): \App\Models\Post
    {
        $post = new \App\Models\Post([
            'post_type' => $type,
            'title' => $title,
            'content' => '<p>Some content.</p>',
            'visibility' => 'private',
        ]);
        $post->user_id = $user->id;
        $post->published_at = now();
        $post->save();

        return $post;
    }

    public function test_a_lesson_can_include_one_of_the_users_posts(): void
    {
        $user = User::factory()->create();
        $post = $this->makePost($user, 'story', 'The Day Everything Changed');

        $this->actingAs($user)->post('/lessons', [
            'title' => 'Lesson with a story',
            'visibility' => 'private',
            'publish' => true,
            'items' => [
                ['type' => 'post', 'content' => '<p>An excerpt of my story.</p>', 'config' => ['post_title' => $post->title], 'post_id' => $post->id],
            ],
        ])->assertSessionHasNoErrors();

        $item = Lesson::firstOrFail()->items()->firstOrFail();
        $this->assertSame('post', $item->type->value);
        $this->assertSame($post->id, $item->post_id);
    }

    public function test_post_search_only_returns_the_users_own_posts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->makePost($user, 'thought', 'Mine');
        $this->makePost($user, 'quote', 'My Quote');
        $this->makePost($other, 'thought', 'Theirs');

        $this->makePost($user, 'video', 'My Video');

        $response = $this->actingAs($user)->getJson(route('lessons.post-search'));

        $response->assertOk();
        // Own posts only, and neither quotes nor videos by default — those
        // types have their own dedicated blocks.
        $this->assertSame(['Mine'], collect($response->json())->pluck('title')->all());

        // Asking for videos explicitly returns them (the Video block's search).
        $videos = $this->actingAs($user)->getJson(route('lessons.post-search', ['type' => 'video']));
        $this->assertSame(['My Video'], collect($videos->json())->pluck('title')->all());
    }

    public function test_an_uploaded_lesson_image_can_be_saved_as_an_image_post(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $user = User::factory()->create();

        // Simulate a lesson image upload on disk.
        $path = "lesson-images/{$user->id}/photo.jpg";
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, 'fake-image-bytes');

        $response = $this->actingAs($user)->postJson(route('lessons.save-post'), [
            'post_type' => 'image',
            'title' => 'Family Photo',
            'content' => null,
            'image_path' => $path,
            'visibility' => 'private',
        ]);

        $response->assertOk();
        $post = \App\Models\Post::firstOrFail();
        $this->assertSame('image', $post->post_type->value);
        // The post got its own copy — deleting the lesson file can't break it.
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists('post-images/photo.jpg');
        $this->assertStringContainsString('post-images/photo.jpg', $post->cover_image);

        // Someone else's path (or a traversal attempt) is rejected.
        $other = User::factory()->create();
        $this->actingAs($other)->postJson(route('lessons.save-post'), [
            'post_type' => 'image',
            'image_path' => $path,
        ])->assertStatus(422);
    }

    public function test_a_video_link_can_be_saved_as_a_video_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('lessons.save-post'), [
            'post_type' => 'video',
            'title' => 'General Conference Clip',
            'content' => null,
            'source_url' => 'https://www.youtube.com/watch?v=abc123',
            'visibility' => 'private',
        ]);

        $response->assertOk();
        $post = \App\Models\Post::firstOrFail();
        $this->assertSame('video', $post->post_type->value);
        $this->assertSame('https://www.youtube.com/watch?v=abc123', $post->source_url);
        $this->assertNotNull($post->source_platform);
    }

    public function test_a_my_words_block_can_be_saved_as_a_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('lessons.save-post'), [
            'content' => '<p>A thought worth keeping beyond this lesson.</p>',
            'title' => 'Worth Keeping',
            'post_type' => 'thought',
            'visibility' => 'friends',
            'tags' => ['faith'],
        ]);

        $response->assertOk()->assertJsonStructure(['id', 'slug', 'title', 'url']);

        $post = \App\Models\Post::firstOrFail();
        $this->assertSame($user->id, $post->user_id);
        $this->assertSame('Worth Keeping', $post->title);
        $this->assertSame('thought', $post->post_type->value);
        $this->assertSame('friends', $post->visibility->value);
        $this->assertSame(['faith'], $post->tags->pluck('name')->all());
        $this->assertNotNull($post->published_at);
        // Attributed to the user's own Author entity.
        $this->assertSame($user->id, $post->author?->user_id);
    }

    public function test_a_lesson_can_contain_a_named_group_of_items(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/lessons', [
            'title' => 'Grouped Lesson',
            'visibility' => 'private',
            'publish' => true,
            'items' => [
                ['type' => 'text', 'content' => '<p>Loose intro</p>', 'config' => null],
                [
                    'type' => 'group',
                    'config' => ['title' => 'Part 1 - Samuel'],
                    'children' => [
                        ['type' => 'scripture', 'content' => null, 'config' => ['reference' => '1 Samuel 1:1']],
                        ['type' => 'question', 'content' => 'What stands out?', 'config' => null],
                    ],
                ],
            ],
        ]);

        $lesson = Lesson::firstOrFail();
        $top = $lesson->items()->with('children')->get();

        // Two top-level nodes: a loose item then a group, in order.
        $this->assertSame(['text', 'group'], $top->pluck('type.value')->all());
        $this->assertSame('Part 1 - Samuel', $top[1]->config['title']);

        // The group owns its two children, in order, pointing back at it.
        $this->assertSame(['scripture', 'question'], $top[1]->children->pluck('type.value')->all());
        $this->assertSame($top[1]->id, $top[1]->children[0]->parent_id);
        $this->assertSame([0, 1], $top[1]->children->pluck('sort_order')->all());

        // Leaf count (excluding the group container) is 3.
        $this->assertSame(3, $lesson->allItems()->where('type', '!=', 'group')->count());
    }

    public function test_groups_cannot_be_nested_inside_groups(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/lessons', [
            'title' => 'Bad Nesting',
            'visibility' => 'private',
            'items' => [
                [
                    'type' => 'group',
                    'config' => ['title' => 'Outer'],
                    'children' => [
                        ['type' => 'group', 'config' => ['title' => 'Inner'], 'children' => []],
                    ],
                ],
            ],
        ])->assertSessionHasErrors('items.0.children.0.type');
    }

    public function test_a_draft_lesson_is_not_published(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/lessons', [
            'title' => 'Draft Lesson',
            'visibility' => 'private',
            'publish' => false,
            'items' => [],
        ]);

        $this->assertNull(Lesson::firstOrFail()->published_at);
    }

    public function test_updating_a_lesson_replaces_and_reorders_items(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->for($user)->create();
        $lesson->syncItems([
            ['type' => 'text', 'content' => '<p>Old intro</p>', 'config' => null],
            ['type' => 'question', 'content' => 'Old question?', 'config' => null],
        ]);
        $originalItemIds = $lesson->items()->pluck('id')->all();

        $this->actingAs($user)->put(route('lessons.update', $lesson), [
            'title' => 'Updated Title',
            'visibility' => 'public',
            'publish' => true,
            'items' => [
                ['type' => 'scripture', 'content' => null, 'config' => ['reference' => 'Alma 32']],
                ['type' => 'text', 'content' => '<p>New intro</p>', 'config' => null],
            ],
        ])->assertRedirect(route('lessons.show', $lesson));

        $lesson->refresh();
        $items = $lesson->items()->get();

        $this->assertSame('Updated Title', $lesson->title);
        $this->assertSame('public', $lesson->visibility->value);

        // Old items were deleted, replaced with the new ordered set.
        $this->assertCount(2, $items);
        $this->assertSame(['scripture', 'text'], $items->pluck('type.value')->all());
        $this->assertEmpty(array_intersect($originalItemIds, $items->pluck('id')->all()));
    }

    public function test_a_user_cannot_update_another_users_lesson(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $lesson = Lesson::factory()->for($owner)->create();

        $this->actingAs($intruder)->put(route('lessons.update', $lesson), [
            'title' => 'Hijacked',
            'visibility' => 'private',
            'items' => [],
        ])->assertForbidden();

        $this->assertNotSame('Hijacked', $lesson->fresh()->title);
    }

    public function test_creating_a_lesson_validates_item_types(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/lessons', [
            'title' => 'Bad Items',
            'visibility' => 'private',
            'items' => [
                ['type' => 'not-a-real-type', 'content' => 'x', 'config' => null],
            ],
        ])->assertSessionHasErrors('items.0.type');
    }

    public function test_a_user_can_upload_a_local_video(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('sermon.mp4', 500, 'video/mp4');

        $response = $this->actingAs($user)
            ->postJson(route('lessons.video-upload'), ['video' => $file])
            ->assertOk()
            ->assertJsonStructure(['path', 'url', 'filename']);

        Storage::disk('public')->assertExists($response->json('path'));
        $this->assertSame('sermon.mp4', $response->json('filename'));
    }

    public function test_video_upload_rejects_non_video_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->postJson(route('lessons.video-upload'), ['video' => $file])
            ->assertStatus(422);
    }

    public function test_a_user_can_delete_their_uploaded_video(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $path = $this->actingAs($user)
            ->postJson(route('lessons.video-upload'), ['video' => UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4')])
            ->json('path');
        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)
            ->deleteJson(route('lessons.video-delete'), ['path' => $path])
            ->assertOk();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_user_cannot_delete_another_users_video(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $path = $this->actingAs($owner)
            ->postJson(route('lessons.video-upload'), ['video' => UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4')])
            ->json('path');

        $this->actingAs($intruder)
            ->deleteJson(route('lessons.video-delete'), ['path' => $path])
            ->assertForbidden();

        Storage::disk('public')->assertExists($path);
    }

    public function test_a_user_can_upload_and_delete_a_lesson_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $path = $this->actingAs($user)
            ->postJson(route('lessons.image-upload'), ['image' => UploadedFile::fake()->image('slide.png', 800, 600)])
            ->assertOk()
            ->json('path');
        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)
            ->deleteJson(route('lessons.image-delete'), ['path' => $path])
            ->assertOk();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_image_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('lessons.image-upload'), ['image' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')])
            ->assertStatus(422);
    }

    public function test_a_lesson_can_contain_an_image_block(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/lessons', [
            'title' => 'Lesson with image',
            'visibility' => 'private',
            'publish' => true,
            'items' => [
                ['type' => 'image', 'content' => null, 'config' => [
                    'source' => 'url',
                    'url' => 'https://example.com/pic.jpg',
                    'caption' => 'A sunrise',
                ]],
            ],
        ]);

        $item = Lesson::firstOrFail()->items()->firstOrFail();
        $this->assertSame('image', $item->type->value);
        $this->assertSame('A sunrise', $item->config['caption']);
    }

    public function test_a_private_lesson_is_hidden_from_other_users(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $lesson = Lesson::factory()->for($owner)->create(['visibility' => 'private']);

        $this->actingAs($other)->get(route('lessons.show', $lesson))->assertForbidden();
        $this->actingAs($owner)->get(route('lessons.show', $lesson))->assertOk();
    }
}
