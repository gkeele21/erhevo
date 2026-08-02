<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PostIndexTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(User $user, string $title, bool $published = true, string $visibility = 'public'): Post
    {
        $post = new Post([
            'post_type' => 'thought',
            'title' => $title,
            'content' => '<p>Content.</p>',
            'visibility' => $visibility,
        ]);
        $post->user_id = $user->id;
        $post->published_at = $published ? now() : null;
        $post->save();

        return $post;
    }

    public function test_users_see_their_own_drafts_but_not_others(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->makePost($user, 'My Published');
        $this->makePost($user, 'My Draft', published: false);
        $this->makePost($other, 'Their Published');
        $this->makePost($other, 'Their Draft', published: false);

        $this->actingAs($user)->get('/posts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('posts.data', 3)
                ->where('posts.data', fn ($posts) => collect($posts)->pluck('title')
                    ->diff(['My Published', 'My Draft', 'Their Published'])->isEmpty()));
    }

    public function test_custom_visibility_shows_only_to_selected_friends(): void
    {
        $owner = User::factory()->create();
        $chosen = User::factory()->create();
        $otherFriend = User::factory()->create();
        $stranger = User::factory()->create();
        \App\Models\Friendship::create(['requester_id' => $owner->id, 'addressee_id' => $chosen->id, 'status' => 'accepted']);
        \App\Models\Friendship::create(['requester_id' => $owner->id, 'addressee_id' => $otherFriend->id, 'status' => 'accepted']);

        // Created through the real endpoint; a non-friend id in the list is ignored.
        $this->actingAs($owner)->post('/posts', [
            'post_type' => 'thought',
            'title' => 'Just for you',
            'content' => '<p>Between us.</p>',
            'author_type' => 'self',
            'visibility' => 'custom',
            'shared_user_ids' => [$chosen->id, $stranger->id],
            'publish' => true,
        ])->assertSessionHasNoErrors();

        $post = Post::firstOrFail();
        $this->assertSame([$chosen->id], $post->sharedWith()->pluck('users.id')->all());

        // Index: only the chosen friend (and the owner) see it.
        foreach ([[$chosen, 1], [$otherFriend, 0], [$stranger, 0], [$owner, 1]] as [$viewer, $expected]) {
            $this->actingAs($viewer)->get('/posts')
                ->assertInertia(fn (AssertableInertia $page) => $page->has('posts.data', $expected));
        }

        // Show page enforces the same rule.
        $this->actingAs($chosen)->get(route('posts.show', $post->slug))->assertOk();
        $this->actingAs($otherFriend)->get(route('posts.show', $post->slug))->assertForbidden();
    }

    public function test_guests_never_see_drafts(): void
    {
        $author = User::factory()->create();
        $this->makePost($author, 'Published');
        $this->makePost($author, 'Draft', published: false);

        $this->get('/posts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Published'));
    }
}
