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
