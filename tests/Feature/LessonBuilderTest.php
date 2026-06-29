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

    public function test_a_private_lesson_is_hidden_from_other_users(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $lesson = Lesson::factory()->for($owner)->create(['visibility' => 'private']);

        $this->actingAs($other)->get(route('lessons.show', $lesson))->assertForbidden();
        $this->actingAs($owner)->get(route('lessons.show', $lesson))->assertOk();
    }
}
