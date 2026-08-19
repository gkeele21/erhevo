<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WhatsNewPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['whats_new.entries' => [
            ['date' => '2026-03-01', 'title' => 'Newest', 'body' => 'Body', 'help_anchor' => 'posts'],
            ['date' => '2026-01-01', 'title' => 'Oldest', 'body' => 'Body'],
            ['date' => '2026-03-01', 'title' => 'Same day as newest', 'body' => 'Body'],
        ]]);
    }

    public function test_guests_see_the_whole_history_grouped_by_date_newest_first(): void
    {
        $this->get('/whats-new')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('WhatsNew')
                ->where('latestDate', '2026-03-01')
                ->has('releases', 2)
                ->where('releases.0.date', '2026-03-01')
                ->has('releases.0.entries', 2)
                ->where('releases.0.entries.0.title', 'Newest')
                ->where('releases.0.entries.0.help_anchor', 'posts')
                ->where('releases.1.date', '2026-01-01')
                ->where('releases.1.entries.0.title', 'Oldest')
                // Nothing is "new" to someone without an account.
                ->where('releases.0.is_new', false)
                ->where('releases.1.is_new', false));
    }

    public function test_entries_newer_than_the_users_cutoff_are_flagged_new(): void
    {
        $user = User::factory()->create();
        $user->setSetting('whats_new_seen_through', '2026-02-01')->save();

        $this->actingAs($user)
            ->get('/whats-new')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('releases.0.is_new', true)
                ->where('releases.1.is_new', false));
    }

    public function test_nothing_is_flagged_new_once_the_user_is_caught_up(): void
    {
        $user = User::factory()->create();
        $user->setSetting('whats_new_seen_through', '2026-03-01')->save();

        $this->actingAs($user)
            ->get('/whats-new')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('releases.0.is_new', false)
                ->where('releases.1.is_new', false));
    }

    public function test_users_only_see_entries_shipped_after_they_joined_as_new(): void
    {
        // No cutoff setting yet — the account's creation date stands in, so a
        // brand-new user isn't told the whole back-catalog is new.
        $user = User::factory()->create(['created_at' => '2026-02-15 12:00:00']);

        $this->actingAs($user)
            ->get('/whats-new')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('releases.0.is_new', true)
                ->where('releases.1.is_new', false));
    }

    public function test_page_renders_with_no_entries_configured(): void
    {
        config(['whats_new.entries' => []]);

        $this->get('/whats-new')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('WhatsNew')
                ->has('releases', 0)
                ->where('latestDate', null));
    }
}
