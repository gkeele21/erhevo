<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\AuthorCalling;
use App\Models\ChurchCalling;
use App\Models\ChurchOrganization;
use App\Models\GeneralConference;
use App\Models\GeneralConferenceSession;
use App\Models\GeneralConferenceSessionType;
use App\Models\Source;
use App\Models\Talk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The library's speaker / calling / conference / recency filters — the same
 * criteria the study plan builder offers.
 */
class TalkLibraryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_talks_can_be_filtered_by_author(): void
    {
        $holland = $this->makeAuthor('Jeffrey', 'Holland');
        $this->makeTalk(['title' => 'Holland Talk', 'author_id' => $holland->id]);
        $this->makeTalk(['title' => 'Someone Else']);

        $this->get("/library?author_id={$holland->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data', 1)
                ->where('talks.data.0.title', 'Holland Talk')
                ->where('selectedAuthor.id', $holland->id)
                ->where('selectedAuthor.name', 'Jeffrey Holland'));
    }

    public function test_an_author_filter_exposes_that_authors_calling_windows(): void
    {
        $author = $this->makeAuthor('Russell', 'Nelson');
        $apostle = $this->makeCalling('Apostle', 'The Quorum of the Twelve Apostles');
        AuthorCalling::create([
            'author_id' => $author->id,
            'church_calling_id' => $apostle->id,
            'start_date' => '1984-04-07',
            'end_date' => '2018-01-14',
        ]);
        $this->makeTalk(['author_id' => $author->id]);

        $this->get("/library?author_id={$author->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('authorCallings', 1)
                ->where('authorCallings.0.label', 'The Quorum of the Twelve Apostles — Apostle')
                ->where('authorCallings.0.start_date', '1984-04-07')
                ->where('authorCallings.0.end_date', '2018-01-14'));
    }

    public function test_an_author_filter_can_be_limited_to_one_calling_window(): void
    {
        $author = $this->makeAuthor('Russell', 'Nelson');
        $apostle = $this->makeCalling('Apostle', 'The Quorum of the Twelve Apostles');
        $period = AuthorCalling::create([
            'author_id' => $author->id,
            'church_calling_id' => $apostle->id,
            'start_date' => '2000-01-01',
            'end_date' => '2010-12-31',
        ]);

        $this->makeTalk(['title' => 'Before', 'author_id' => $author->id, 'talk_date' => '1999-04-04']);
        $this->makeTalk(['title' => 'During', 'author_id' => $author->id, 'talk_date' => '2005-04-04']);
        $this->makeTalk(['title' => 'After', 'author_id' => $author->id, 'talk_date' => '2020-04-04']);

        $this->get("/library?author_id={$author->id}&author_calling_ids[]={$period->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data', 1)
                ->where('talks.data.0.title', 'During'));
    }

    public function test_an_undated_talk_falls_outside_every_calling_window(): void
    {
        $author = $this->makeAuthor('Russell', 'Nelson');
        $apostle = $this->makeCalling('Apostle', 'The Quorum of the Twelve Apostles');
        $period = AuthorCalling::create([
            'author_id' => $author->id,
            'church_calling_id' => $apostle->id,
            'start_date' => '2000-01-01',
            'end_date' => null,
        ]);

        $this->makeTalk(['title' => 'Undated', 'author_id' => $author->id, 'talk_date' => null]);

        // Matches StudyPlanScheduler: a window requires a date to compare.
        $this->get("/library?author_id={$author->id}&author_calling_ids[]={$period->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->has('talks.data', 0));
    }

    public function test_several_calling_windows_can_be_selected_at_once(): void
    {
        $author = $this->makeAuthor('Russell', 'Nelson');
        $apostle = $this->makeCalling('Apostle', 'The Quorum of the Twelve Apostles');
        $presidency = $this->makeCalling('President', 'The First Presidency');

        $asApostle = AuthorCalling::create([
            'author_id' => $author->id,
            'church_calling_id' => $apostle->id,
            'start_date' => '1984-01-01',
            'end_date' => '2015-12-31',
        ]);
        $asPresident = AuthorCalling::create([
            'author_id' => $author->id,
            'church_calling_id' => $presidency->id,
            'start_date' => '2018-01-01',
            'end_date' => '2020-12-31',
        ]);

        $this->makeTalk(['title' => 'As Apostle', 'author_id' => $author->id, 'talk_date' => '1990-04-04']);
        $this->makeTalk(['title' => 'In The Gap', 'author_id' => $author->id, 'talk_date' => '2016-04-04']);
        $this->makeTalk(['title' => 'As President', 'author_id' => $author->id, 'talk_date' => '2019-04-04']);

        // Windows OR together — the talk between them is still excluded.
        $this->get("/library?author_id={$author->id}"
            . "&author_calling_ids[]={$asApostle->id}&author_calling_ids[]={$asPresident->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data', 2)
                ->where('talks.data.0.title', 'As Apostle')
                ->where('talks.data.1.title', 'As President')
                ->where('filters.author_calling_ids', [$asApostle->id, $asPresident->id]));
    }

    public function test_a_single_calling_id_link_still_works(): void
    {
        $author = $this->makeAuthor('Russell', 'Nelson');
        $apostle = $this->makeCalling('Apostle', 'The Quorum of the Twelve Apostles');
        $period = AuthorCalling::create([
            'author_id' => $author->id,
            'church_calling_id' => $apostle->id,
            'start_date' => '2000-01-01',
            'end_date' => '2010-12-31',
        ]);

        $this->makeTalk(['title' => 'During', 'author_id' => $author->id, 'talk_date' => '2005-04-04']);
        $this->makeTalk(['title' => 'After', 'author_id' => $author->id, 'talk_date' => '2020-04-04']);

        // The filter used to be single-select; old bookmarks must keep working.
        $this->get("/library?author_id={$author->id}&author_calling_id={$period->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data', 1)
                ->where('talks.data.0.title', 'During')
                ->where('filters.author_calling_ids', [$period->id]));
    }

    public function test_a_boundless_calling_window_matches_every_dated_talk(): void
    {
        $author = $this->makeAuthor('Russell', 'Nelson');
        $apostle = $this->makeCalling('Apostle', 'The Quorum of the Twelve Apostles');
        $period = AuthorCalling::create([
            'author_id' => $author->id,
            'church_calling_id' => $apostle->id,
            'start_date' => null,
            'end_date' => null,
        ]);

        $this->makeTalk(['title' => 'Dated', 'author_id' => $author->id, 'talk_date' => '1975-04-04']);
        $this->makeTalk(['title' => 'Undated', 'author_id' => $author->id, 'talk_date' => null]);

        $this->get("/library?author_id={$author->id}&author_calling_ids[]={$period->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data', 1)
                ->where('talks.data.0.title', 'Dated'));
    }

    public function test_an_unknown_calling_window_does_not_empty_the_results(): void
    {
        $author = $this->makeAuthor('Russell', 'Nelson');
        $this->makeTalk(['author_id' => $author->id]);

        $this->get("/library?author_id={$author->id}&author_calling_ids[]=999999")
            ->assertInertia(fn (AssertableInertia $page) => $page->has('talks.data', 1));
    }

    public function test_a_calling_window_filter_is_ignored_without_an_author(): void
    {
        $author = $this->makeAuthor('Russell', 'Nelson');
        $apostle = $this->makeCalling('Apostle', 'The Quorum of the Twelve Apostles');
        $period = AuthorCalling::create([
            'author_id' => $author->id,
            'church_calling_id' => $apostle->id,
            'start_date' => '2000-01-01',
        ]);

        $this->makeTalk(['title' => 'Old Talk', 'talk_date' => '1990-04-04']);

        $this->get("/library?author_calling_ids[]={$period->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->has('talks.data', 1));
    }

    public function test_talks_can_be_filtered_by_church_calling(): void
    {
        $apostle = $this->makeCalling('Apostle', 'The Quorum of the Twelve Apostles');
        $seventy = $this->makeCalling('Seventy', 'General Authority Seventies');

        $this->makeTalk(['title' => 'By an Apostle', 'church_calling_id' => $apostle->id]);
        $this->makeTalk(['title' => 'By a Seventy', 'church_calling_id' => $seventy->id]);

        $this->get("/library?church_calling_id={$apostle->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data', 1)
                ->where('talks.data.0.title', 'By an Apostle'));
    }

    public function test_callings_and_conferences_ship_with_the_page(): void
    {
        $this->makeCalling('Apostle', 'The Quorum of the Twelve Apostles');
        $this->seedConference();

        $this->get('/library')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('churchCallings', 1)
                ->where('churchCallings.0.label', 'The Quorum of the Twelve Apostles — Apostle')
                ->has('conferences', 1)
                ->where('conferences.0.name', 'April 2024 General Conference'));
    }

    public function test_talks_can_be_filtered_by_conference(): void
    {
        ['conference' => $conference] = $this->seedConference();
        $this->makeTalk(['title' => 'Unrelated Talk']);

        $this->get("/library?general_conference_id={$conference->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data', 1)
                ->where('talks.data.0.title', 'Conference Talk'));
    }

    public function test_talks_can_be_filtered_to_the_last_n_years(): void
    {
        $this->makeTalk(['title' => 'Recent', 'talk_date' => now()->subYear()->toDateString()]);
        $this->makeTalk(['title' => 'Ancient', 'talk_date' => now()->subYears(20)->toDateString()]);

        $this->get('/library?years_back=5')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data', 1)
                ->where('talks.data.0.title', 'Recent'));
    }

    public function test_an_out_of_range_years_back_is_ignored(): void
    {
        $this->makeTalk(['talk_date' => now()->subYears(20)->toDateString()]);

        foreach (['0', '-3', '500', 'abc'] as $value) {
            $this->get("/library?years_back={$value}")
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->has('talks.data', 1)
                    ->where('filters.years_back', null));
        }
    }

    public function test_filters_combine_rather_than_replace_each_other(): void
    {
        $author = $this->makeAuthor('Jeffrey', 'Holland');
        $apostle = $this->makeCalling('Apostle', 'The Quorum of the Twelve Apostles');

        $this->makeTalk([
            'title' => 'Wanted',
            'author_id' => $author->id,
            'church_calling_id' => $apostle->id,
            'talk_date' => now()->subYear()->toDateString(),
        ]);
        // Right author, wrong calling.
        $this->makeTalk(['title' => 'Wrong Calling', 'author_id' => $author->id]);
        // Right calling, too old.
        $this->makeTalk([
            'title' => 'Too Old',
            'church_calling_id' => $apostle->id,
            'talk_date' => now()->subYears(30)->toDateString(),
        ]);

        $this->get("/library?author_id={$author->id}&church_calling_id={$apostle->id}&years_back=5")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data', 1)
                ->where('talks.data.0.title', 'Wanted'));
    }

    public function test_the_random_pick_honors_the_new_filters(): void
    {
        $author = $this->makeAuthor('Jeffrey', 'Holland');
        $wanted = $this->makeTalk(['title' => 'Wanted', 'author_id' => $author->id]);
        $this->makeTalk(['title' => 'Excluded']);

        foreach (range(1, 5) as $ignored) {
            $this->get("/library?author_id={$author->id}")
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->where('randomTalk.id', $wanted->id));
        }
    }

    public function test_active_filters_round_trip_to_the_page(): void
    {
        $author = $this->makeAuthor('Jeffrey', 'Holland');
        $apostle = $this->makeCalling('Apostle', 'The Quorum of the Twelve Apostles');
        ['conference' => $conference] = $this->seedConference();

        $this->get("/library?author_id={$author->id}&church_calling_id={$apostle->id}"
            . "&general_conference_id={$conference->id}&years_back=7")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.author_id', $author->id)
                ->where('filters.church_calling_id', $apostle->id)
                ->where('filters.general_conference_id', $conference->id)
                ->where('filters.years_back', 7));
    }

    public function test_the_result_total_reflects_the_active_filters(): void
    {
        $author = $this->makeAuthor('Jeffrey', 'Holland');
        foreach (range(1, 3) as $n) {
            $this->makeTalk(['title' => "Holland {$n}", 'author_id' => $author->id]);
        }
        $this->makeTalk(['title' => 'Someone Else']);

        $this->get('/library')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('talks.total', 4));

        $this->get("/library?author_id={$author->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('talks.total', 3));
    }

    public function test_the_total_counts_every_match_not_just_the_current_page(): void
    {
        // The page holds 15, so 18 proves the total isn't the page size.
        foreach (range(1, 18) as $n) {
            $this->makeTalk(['title' => "Talk {$n}"]);
        }

        $this->get('/library')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('talks.total', 18)
                ->where('talks.from', 1)
                ->where('talks.to', 15)
                ->has('talks.data', 15));

        $this->get('/library?page=2')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('talks.total', 18)
                ->where('talks.from', 16)
                ->where('talks.to', 18)
                ->has('talks.data', 3));
    }

    // --- Author type-ahead -------------------------------------------------

    public function test_the_author_search_finds_authors_with_talks(): void
    {
        $holland = $this->makeAuthor('Jeffrey', 'Holland');
        $this->makeTalk(['author_id' => $holland->id]);

        $this->getJson(route('talks.authors.search', ['q' => 'Holl']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $holland->id)
            ->assertJsonPath('0.name', 'Jeffrey Holland');
    }

    public function test_the_author_search_skips_authors_without_talks(): void
    {
        $this->makeAuthor('Jeffrey', 'Holland');

        $this->getJson(route('talks.authors.search', ['q' => 'Holl']))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_the_author_search_needs_at_least_two_characters(): void
    {
        $holland = $this->makeAuthor('Jeffrey', 'Holland');
        $this->makeTalk(['author_id' => $holland->id]);

        $this->getJson(route('talks.authors.search', ['q' => 'H']))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_the_author_search_respects_the_lds_content_setting(): void
    {
        $user = User::factory()->create();
        $user->setSetting('show_lds_content', false)->save();

        $this->actingAs($user)
            ->getJson(route('talks.authors.search', ['q' => 'Holl']))
            ->assertForbidden();
    }

    // --- Helpers -----------------------------------------------------------

    private function makeAuthor(string $first, string $last): Author
    {
        return Author::create([
            'first_name' => $first,
            'last_name' => $last,
            'slug' => strtolower("{$first}-{$last}"),
        ]);
    }

    private function makeCalling(string $name, string $organization): ChurchCalling
    {
        return ChurchCalling::create([
            'church_organization_id' => ChurchOrganization::firstOrCreate(['name' => $organization])->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    /** @return array{conference: GeneralConference} */
    private function seedConference(): array
    {
        $conference = GeneralConference::create([
            'name' => 'April 2024 General Conference',
            'year' => 2024,
            'month' => 'april',
            'start_date' => '2024-04-06',
            'end_date' => '2024-04-07',
        ]);

        $session = GeneralConferenceSession::create([
            'general_conference_id' => $conference->id,
            'session_type_id' => GeneralConferenceSessionType::create([
                'name' => 'Saturday Morning',
                'slug' => 'saturday-morning',
                'display_order' => 1,
            ])->id,
            'name' => 'Saturday Morning Session',
            'session_date' => '2024-04-06',
            'display_order' => 1,
        ]);

        $this->makeTalk([
            'title' => 'Conference Talk',
            'general_conference_session_id' => $session->id,
        ]);

        return ['conference' => $conference];
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
