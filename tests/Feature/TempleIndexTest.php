<?php

namespace Tests\Feature;

use App\Models\Temple;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TempleIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_all_temples_with_expected_shape(): void
    {
        Temple::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->get('/temples')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Temples/Index')
                ->has('temples', 3)
                ->has('temples.0', fn (AssertableInertia $temple) => $temple
                    ->hasAll(['id', 'slug', 'name', 'city', 'state', 'country', 'latitude', 'longitude', 'photo_url', 'dedicated_on']))
                ->where('visitedTempleIds', []));
    }

    public function test_visited_temple_ids_are_scoped_to_the_current_user(): void
    {
        [$visitedTemple, $otherTemple] = Temple::factory()->count(2)->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->templeVisits()->create([
            'temple_id' => $visitedTemple->id,
            'visited_on' => '2026-08-01',
            'ordinances' => [],
        ]);
        $otherUser->templeVisits()->create([
            'temple_id' => $otherTemple->id,
            'visited_on' => '2026-08-01',
            'ordinances' => [],
        ]);

        $this->actingAs($user)
            ->get('/temples')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('visitedTempleIds', [$visitedTemple->id]));
    }

    public function test_show_resolves_by_slug_and_includes_only_own_visits(): void
    {
        $temple = Temple::factory()->create(['slug' => 'test-city-temple']);
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->templeVisits()->create([
            'temple_id' => $temple->id,
            'visited_on' => '2026-07-04',
            'ordinances' => ['endowment'],
            'notes' => 'mine',
        ]);
        $otherUser->templeVisits()->create([
            'temple_id' => $temple->id,
            'visited_on' => '2026-07-05',
            'ordinances' => [],
            'notes' => 'not mine',
        ]);

        $this->actingAs($user)
            ->get('/temples/test-city-temple')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Temples/Show')
                ->where('temple.slug', 'test-city-temple')
                ->has('visits', 1)
                ->where('visits.0.notes', 'mine')
                ->where('visits.0.ordinances', ['endowment']));
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/temples/nope-temple')
            ->assertNotFound();
    }
}
