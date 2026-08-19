<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Models\Talk;
use App\Models\TalkRating;
use App\Models\TalkRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TalkEngagementTest extends TestCase
{
    use RefreshDatabase;

    // --- Ratings -----------------------------------------------------------

    public function test_a_user_can_rate_a_talk_and_the_average_is_shown(): void
    {
        $talk = $this->makeTalk();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('talks.rate', $talk), ['rating' => 4])
            ->assertRedirect();

        $this->assertDatabaseHas('talk_ratings', [
            'talk_id' => $talk->id,
            'user_id' => $user->id,
            'rating' => 4,
        ]);

        $this->actingAs($user)
            ->get('/library')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('talks.data.0.average_rating', fn ($average) => (float) $average === 4.0)
                ->where('talks.data.0.ratings_count', 1)
                ->where('talks.data.0.my_rating', 4));
    }

    public function test_rating_again_replaces_the_previous_rating(): void
    {
        $talk = $this->makeTalk();
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('talks.rate', $talk), ['rating' => 2]);
        $this->actingAs($user)->put(route('talks.rate', $talk), ['rating' => 5]);

        $this->assertSame(1, $talk->ratings()->count());
        $this->assertSame(5.0, (float) $talk->fresh()->average_rating);
    }

    public function test_the_average_blends_every_users_rating(): void
    {
        $talk = $this->makeTalk();

        $this->actingAs(User::factory()->create())->put(route('talks.rate', $talk), ['rating' => 4]);
        $this->actingAs(User::factory()->create())->put(route('talks.rate', $talk), ['rating' => 5]);

        $this->assertSame(4.5, (float) $talk->fresh()->average_rating);
        $this->assertSame(2, $talk->fresh()->ratings_count);
    }

    public function test_ratings_outside_one_to_five_are_rejected(): void
    {
        $talk = $this->makeTalk();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('talks.rate', $talk), ['rating' => 6])
            ->assertSessionHasErrors('rating');

        $this->actingAs($user)
            ->put(route('talks.rate', $talk), ['rating' => 0])
            ->assertSessionHasErrors('rating');

        $this->assertSame(0, $talk->ratings()->count());
    }

    public function test_clearing_a_rating_resets_the_cached_average(): void
    {
        $talk = $this->makeTalk();
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('talks.rate', $talk), ['rating' => 3]);
        $this->actingAs($user)->delete(route('talks.rating.destroy', $talk));

        $this->assertSame(0, $talk->ratings()->count());
        $this->assertNull($talk->fresh()->average_rating);
        $this->assertSame(0, $talk->fresh()->ratings_count);
    }

    public function test_a_user_only_sees_their_own_rating(): void
    {
        $talk = $this->makeTalk();
        $rater = User::factory()->create();
        $onlooker = User::factory()->create();

        $this->actingAs($rater)->put(route('talks.rate', $talk), ['rating' => 5]);

        // The average is public; "my_rating" is not.
        $this->actingAs($onlooker)
            ->get('/library')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('talks.data.0.average_rating', fn ($average) => (float) $average === 5.0)
                ->where('talks.data.0.my_rating', null));
    }

    public function test_talks_can_be_filtered_by_minimum_rating(): void
    {
        $loved = $this->makeTalk(['title' => 'Loved Talk']);
        $liked = $this->makeTalk(['title' => 'Liked Talk']);
        $this->makeTalk(['title' => 'Unrated Talk']);

        $user = User::factory()->create();
        $this->actingAs($user)->put(route('talks.rate', $loved), ['rating' => 5]);
        $this->actingAs($user)->put(route('talks.rate', $liked), ['rating' => 2]);

        $this->actingAs($user)
            ->get('/library?min_rating=4')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data', 1)
                ->where('talks.data.0.title', 'Loved Talk'));
    }

    public function test_sorting_by_rating_puts_the_best_first_and_unrated_last(): void
    {
        $good = $this->makeTalk(['title' => 'Good']);
        $better = $this->makeTalk(['title' => 'Better']);
        $this->makeTalk(['title' => 'Unrated']);

        $user = User::factory()->create();
        $this->actingAs($user)->put(route('talks.rate', $good), ['rating' => 3]);
        $this->actingAs($user)->put(route('talks.rate', $better), ['rating' => 5]);

        $this->actingAs($user)
            ->get('/library?sort=rating')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('talks.data.0.title', 'Better')
                ->where('talks.data.1.title', 'Good')
                ->where('talks.data.2.title', 'Unrated'));
    }

    // --- Favorites ---------------------------------------------------------

    public function test_favoriting_a_talk_toggles_on_and_off(): void
    {
        $talk = $this->makeTalk();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('talks.favorite', $talk));
        $this->assertDatabaseHas('talk_favorites', ['talk_id' => $talk->id, 'user_id' => $user->id]);

        $this->actingAs($user)->post(route('talks.favorite', $talk));
        $this->assertDatabaseMissing('talk_favorites', ['talk_id' => $talk->id, 'user_id' => $user->id]);
    }

    public function test_the_favorites_page_lists_only_the_users_favorites(): void
    {
        $kept = $this->makeTalk(['title' => 'Kept Talk']);
        $this->makeTalk(['title' => 'Other Talk']);

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('talks.favorite', $kept));

        $this->actingAs($user)
            ->get(route('talks.favorites'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Talks/Index')
                ->where('filters.favorites', true)
                ->has('talks.data', 1)
                ->where('talks.data.0.title', 'Kept Talk')
                ->where('talks.data.0.is_favorite', true));
    }

    public function test_one_users_favorite_is_not_another_users(): void
    {
        $talk = $this->makeTalk();
        $this->actingAs(User::factory()->create())->post(route('talks.favorite', $talk));

        $this->actingAs(User::factory()->create())
            ->get(route('talks.favorites'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('talks.data', 0));
    }

    // --- Read dates --------------------------------------------------------

    public function test_a_user_can_log_the_date_they_read_a_talk(): void
    {
        $talk = $this->makeTalk();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('talks.reads.store', $talk), ['read_on' => '2026-01-05'])
            ->assertRedirect();

        $this->assertDatabaseHas('talk_reads', [
            'talk_id' => $talk->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/library')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data.0.reads', 1)
                ->where('talks.data.0.reads.0.read_on', '2026-01-05')
                ->where('talks.data.0.reads.0.label', 'Jan 5, 2026'));
    }

    public function test_re_reading_a_talk_records_every_date_newest_first(): void
    {
        $talk = $this->makeTalk();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('talks.reads.store', $talk), ['read_on' => '2026-01-05']);
        $this->actingAs($user)->post(route('talks.reads.store', $talk), ['read_on' => '2026-04-20']);

        $this->actingAs($user)
            ->get('/library')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data.0.reads', 2)
                ->where('talks.data.0.reads.0.read_on', '2026-04-20')
                ->where('talks.data.0.reads.1.read_on', '2026-01-05'));
    }

    public function test_logging_the_same_date_twice_does_not_duplicate_it(): void
    {
        $talk = $this->makeTalk();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('talks.reads.store', $talk), ['read_on' => '2026-01-05']);
        $this->actingAs($user)->post(route('talks.reads.store', $talk), ['read_on' => '2026-01-05']);

        $this->assertSame(1, $talk->reads()->count());
    }

    public function test_a_future_read_date_is_rejected(): void
    {
        $talk = $this->makeTalk();

        $this->actingAs(User::factory()->create())
            ->post(route('talks.reads.store', $talk), ['read_on' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors('read_on');

        $this->assertSame(0, $talk->reads()->count());
    }

    public function test_a_user_can_remove_a_read_date(): void
    {
        $talk = $this->makeTalk();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('talks.reads.store', $talk), ['read_on' => '2026-01-05']);
        $read = TalkRead::firstOrFail();

        $this->actingAs($user)
            ->delete(route('talks.reads.destroy', [$talk, $read]))
            ->assertRedirect();

        $this->assertSame(0, $talk->reads()->count());
    }

    public function test_a_user_cannot_remove_someone_elses_read_date(): void
    {
        $talk = $this->makeTalk();

        $this->actingAs(User::factory()->create())
            ->post(route('talks.reads.store', $talk), ['read_on' => '2026-01-05']);
        $read = TalkRead::firstOrFail();

        $this->actingAs(User::factory()->create())
            ->delete(route('talks.reads.destroy', [$talk, $read]))
            ->assertForbidden();

        $this->assertSame(1, $talk->reads()->count());
    }

    // --- Random pick -------------------------------------------------------

    public function test_the_library_returns_a_random_talk_alongside_the_results(): void
    {
        $talk = $this->makeTalk(['title' => 'The Only Talk']);

        $this->get('/library')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('randomTalk.id', $talk->id)
                ->where('randomTalk.title', 'The Only Talk'));
    }

    public function test_the_random_talk_honors_the_active_filters(): void
    {
        $this->makeTalk(['title' => 'Excluded Talk']);
        $match = $this->makeTalk(['title' => 'Matching Talk']);

        // Ten draws is plenty to catch a random pick that ignores the filter.
        foreach (range(1, 10) as $ignored) {
            $this->get('/library?search=Matching')
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->where('randomTalk.id', $match->id));
        }
    }

    public function test_the_random_talk_is_null_when_nothing_matches(): void
    {
        $this->makeTalk();

        $this->get('/library?search=nothing-matches-this')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('randomTalk', null)
                ->has('talks.data', 0));
    }

    // --- Access ------------------------------------------------------------

    public function test_guests_cannot_rate_favorite_or_mark_talks_read(): void
    {
        $talk = $this->makeTalk();

        $this->put(route('talks.rate', $talk), ['rating' => 5])->assertRedirect(route('login'));
        $this->post(route('talks.favorite', $talk))->assertRedirect(route('login'));
        $this->post(route('talks.reads.store', $talk), ['read_on' => '2026-01-05'])->assertRedirect(route('login'));
        $this->get(route('talks.favorites'))->assertRedirect(route('login'));
    }

    public function test_guests_see_averages_but_no_personal_state(): void
    {
        $talk = $this->makeTalk();
        TalkRating::create([
            'talk_id' => $talk->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 4,
        ]);

        $this->get('/library')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('canEngage', false)
                ->where('talks.data.0.average_rating', fn ($average) => (float) $average === 4.0)
                ->where('talks.data.0.my_rating', null)
                ->where('talks.data.0.is_favorite', false)
                ->where('talks.data.0.reads', []));
    }

    public function test_users_with_lds_content_disabled_cannot_engage(): void
    {
        $talk = $this->makeTalk();
        $user = User::factory()->create();
        $user->setSetting('show_lds_content', false)->save();

        $this->actingAs($user)->put(route('talks.rate', $talk), ['rating' => 5])->assertForbidden();
        $this->actingAs($user)->post(route('talks.favorite', $talk))->assertForbidden();
        $this->actingAs($user)->get(route('talks.favorites'))->assertForbidden();
    }

    private function makeTalk(array $attributes = []): Talk
    {
        $source = Source::firstOrCreate(
            ['slug' => 'general-conference'],
            ['name' => 'General Conference', 'is_active' => true]
        );

        return Talk::create([
            'source_id' => $source->id,
            'speaker_name' => 'Speaker One',
            'title' => 'A Talk',
            'talk_date' => '2024-04-06',
            ...$attributes,
        ]);
    }
}
