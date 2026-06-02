<?php

namespace Tests\Feature;

use App\Models\GeneralConference;
use App\Models\GeneralConferenceSession;
use App\Models\GeneralConferenceSessionType;
use App\Models\Source;
use App\Models\Talk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TalkLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_the_library(): void
    {
        $this->get('/library')->assertRedirect('/login');
    }

    public function test_users_with_lds_content_enabled_can_access_the_library(): void
    {
        $user = User::factory()->create();
        $user->setSetting('show_lds_content', true)->save();

        $this->actingAs($user)
            ->get('/library')
            ->assertOk();
    }

    public function test_library_is_enabled_by_default(): void
    {
        // show_lds_content defaults to true (opt-out model).
        $this->actingAs(User::factory()->create())
            ->get('/library')
            ->assertOk();
    }

    public function test_users_with_lds_content_disabled_are_forbidden(): void
    {
        $user = User::factory()->create();
        $user->setSetting('show_lds_content', false)->save();

        $this->actingAs($user)
            ->get('/library')
            ->assertForbidden();
    }

    public function test_general_conference_source_exposes_available_years(): void
    {
        $this->seedGeneralConferenceData();

        $this->actingAs(User::factory()->create())
            ->get('/library?source=general-conference')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Talks/Index')
                ->where('conferenceFilters.years', [2024, 2023])
                ->where('conferenceFilters.months', [])
                ->where('conferenceFilters.sessions', []));
    }

    public function test_selecting_a_year_exposes_available_months(): void
    {
        $this->seedGeneralConferenceData();

        $this->actingAs(User::factory()->create())
            ->get('/library?source=general-conference&year=2024')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('conferenceFilters.months', [
                    ['value' => 'april', 'label' => 'April'],
                    ['value' => 'october', 'label' => 'October'],
                ]));
    }

    public function test_selecting_a_month_exposes_that_conferences_sessions(): void
    {
        $this->seedGeneralConferenceData();

        $this->actingAs(User::factory()->create())
            ->get('/library?source=general-conference&year=2024&month=april')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('conferenceFilters.sessions', 2)
                ->where('conferenceFilters.sessions.0.name', 'Saturday Morning Session')
                ->where('conferenceFilters.sessions.1.name', 'Saturday Afternoon Session'));
    }

    public function test_filtering_by_session_returns_only_that_sessions_talks(): void
    {
        $data = $this->seedGeneralConferenceData();

        $this->actingAs(User::factory()->create())
            ->get("/library?source=general-conference&year=2024&month=april&session={$data['morningSession']->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('talks.data', 1)
                ->where('talks.data.0.title', 'Morning Talk'));
    }

    /**
     * Seed a small General Conference dataset:
     * 2024 April (morning + afternoon sessions, one talk each), 2024 October, 2023 April.
     *
     * @return array{morningSession: GeneralConferenceSession}
     */
    private function seedGeneralConferenceData(): array
    {
        $source = Source::create([
            'name' => 'General Conference',
            'slug' => 'general-conference',
            'is_active' => true,
        ]);

        $sessionType = GeneralConferenceSessionType::create([
            'name' => 'Saturday Morning',
            'slug' => 'saturday-morning',
            'display_order' => 1,
        ]);

        $apr2024 = GeneralConference::create([
            'name' => 'April 2024 General Conference',
            'year' => 2024,
            'month' => 'april',
            'start_date' => '2024-04-06',
            'end_date' => '2024-04-07',
        ]);

        GeneralConference::create([
            'name' => 'October 2024 General Conference',
            'year' => 2024,
            'month' => 'october',
            'start_date' => '2024-10-05',
            'end_date' => '2024-10-06',
        ]);

        GeneralConference::create([
            'name' => 'April 2023 General Conference',
            'year' => 2023,
            'month' => 'april',
            'start_date' => '2023-04-01',
            'end_date' => '2023-04-02',
        ]);

        $morningSession = GeneralConferenceSession::create([
            'general_conference_id' => $apr2024->id,
            'session_type_id' => $sessionType->id,
            'name' => 'Saturday Morning Session',
            'session_date' => '2024-04-06',
            'display_order' => 1,
        ]);

        $afternoonSession = GeneralConferenceSession::create([
            'general_conference_id' => $apr2024->id,
            'session_type_id' => $sessionType->id,
            'name' => 'Saturday Afternoon Session',
            'session_date' => '2024-04-06',
            'display_order' => 2,
        ]);

        Talk::create([
            'source_id' => $source->id,
            'general_conference_session_id' => $morningSession->id,
            'speaker_name' => 'Speaker One',
            'title' => 'Morning Talk',
            'talk_date' => '2024-04-06',
        ]);

        Talk::create([
            'source_id' => $source->id,
            'general_conference_session_id' => $afternoonSession->id,
            'speaker_name' => 'Speaker Two',
            'title' => 'Afternoon Talk',
            'talk_date' => '2024-04-06',
        ]);

        return ['morningSession' => $morningSession];
    }
}
