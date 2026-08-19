<?php

namespace Tests\Feature;

use App\Models\Temple;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TempleFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_temple_can_be_favorited_and_unfavorited(): void
    {
        $temple = Temple::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post("/temples/{$temple->slug}/favorite")->assertRedirect();
        $this->assertTrue($user->favoriteTemples()->where('temples.id', $temple->id)->exists());

        $this->actingAs($user)->post("/temples/{$temple->slug}/favorite")->assertRedirect();
        $this->assertFalse($user->favoriteTemples()->where('temples.id', $temple->id)->exists());
    }

    public function test_the_index_exposes_the_current_users_favorites(): void
    {
        [$favorite, $other] = Temple::factory()->count(2)->create();
        $user = User::factory()->create();
        $user->favoriteTemples()->attach($favorite->id);

        $this->actingAs($user)
            ->get('/temples')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Temples/Index')
                ->where('favoriteTempleIds', [$favorite->id]));
    }

    public function test_the_detail_page_knows_whether_the_temple_is_a_favorite(): void
    {
        $temple = Temple::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get("/temples/{$temple->slug}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('isFavorite', false));

        $user->favoriteTemples()->attach($temple->id);

        $this->actingAs($user)
            ->get("/temples/{$temple->slug}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('isFavorite', true));
    }

    public function test_favorites_are_scoped_to_the_user(): void
    {
        $temple = Temple::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherUser->favoriteTemples()->attach($temple->id);

        $this->actingAs($user)
            ->get('/temples')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('favoriteTempleIds', []));
    }

    public function test_favoriting_the_same_temple_twice_never_duplicates_a_row(): void
    {
        $temple = Temple::factory()->create();
        $user = User::factory()->create();

        $user->favoriteTemples()->attach($temple->id);
        $this->actingAs($user)->post("/temples/{$temple->slug}/favorite");
        $this->actingAs($user)->post("/temples/{$temple->slug}/favorite");

        $this->assertDatabaseCount('temple_favorites', 1);
    }

    public function test_guests_cannot_favorite(): void
    {
        $temple = Temple::factory()->create();

        $this->post("/temples/{$temple->slug}/favorite")->assertRedirect('/login');
        $this->assertDatabaseCount('temple_favorites', 0);
    }
}
