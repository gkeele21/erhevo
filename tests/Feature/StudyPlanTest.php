<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\ScriptureBook;
use App\Models\ScriptureChapter;
use App\Models\ScriptureVolume;
use App\Models\StudyPlan;
use App\Models\Talk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function makeVolume(): ScriptureVolume
    {
        $volume = ScriptureVolume::create(['name' => 'Book of Mormon', 'slug' => 'bofm', 'abbreviation' => 'BoM', 'sort_order' => 1]);
        $book = ScriptureBook::create(['volume_id' => $volume->id, 'name' => '1 Nephi', 'slug' => '1-ne', 'abbreviation' => '1 Ne.', 'sort_order' => 1, 'chapter_count' => 3]);
        foreach (range(1, 3) as $n) {
            ScriptureChapter::create(['book_id' => $book->id, 'chapter_number' => $n, 'verse_count' => 10]);
        }

        return $volume;
    }

    public function test_user_can_create_a_scripture_plan(): void
    {
        $user = User::factory()->create();
        $volume = $this->makeVolume();

        // Payload as the form actually sends it: talk-mode defaults included.
        $response = $this->actingAs($user)->post(route('study-plans.store'), [
            'name' => 'BoM Plan',
            'type' => 'scripture',
            'volume_id' => $volume->id,
            'book_ids' => null,
            'mode' => 'author',
            'author_id' => null,
            'author_calling_id' => null,
            'church_calling_id' => null,
            'years_back' => null,
            'start_date' => null,
            'end_date' => null,
            'frequency' => null,
        ]);

        $response->assertSessionHasNoErrors();
        $plan = StudyPlan::first();
        $response->assertRedirect(route('study-plans.show', $plan));
        $this->assertSame(3, $plan->items()->count());
        $this->assertNull($plan->items()->first()->scheduled_date);
    }

    public function test_user_can_create_a_talks_plan_by_author(): void
    {
        $user = User::factory()->create();
        $author = Author::create(['first_name' => 'Russell', 'last_name' => 'Nelson']);
        $source = \App\Models\Source::create(['name' => 'General Conference', 'slug' => 'gc']);
        foreach ([1, 2] as $n) {
            Talk::create(['source_id' => $source->id, 'author_id' => $author->id, 'speaker_name' => 'Russell M. Nelson', 'title' => "Talk {$n}", 'talk_date' => "2024-0{$n}-01"]);
        }

        $response = $this->actingAs($user)->post(route('study-plans.store'), [
            'name' => 'Nelson Talks',
            'type' => 'talks',
            'volume_id' => null,
            'book_ids' => null,
            'mode' => 'author',
            'author_id' => $author->id,
            'author_calling_id' => null,
            'church_calling_id' => null,
            'years_back' => null,
            'start_date' => '2026-08-01',
            'end_date' => null,
            'frequency' => 'weekly',
        ]);

        $response->assertSessionHasNoErrors();
        $plan = StudyPlan::first();
        $this->assertSame(2, $plan->items()->count());
        $this->assertSame('2026-08-01', $plan->items()->first()->scheduled_date->toDateString());
    }

    public function test_scripture_plan_requires_a_volume(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('study-plans.store'), [
            'name' => 'Broken',
            'type' => 'scripture',
            'volume_id' => null,
            'mode' => 'author',
            'author_id' => null,
        ]);

        $response->assertSessionHasErrors('volume_id');
        $response->assertSessionDoesntHaveErrors('author_id');
    }

    public function test_user_can_create_a_talks_plan_for_a_conference(): void
    {
        $user = User::factory()->create();
        $source = \App\Models\Source::create(['name' => 'General Conference', 'slug' => 'gc']);
        $conference = \App\Models\GeneralConference::create(['name' => 'October 2025 General Conference', 'year' => 2025, 'month' => 'october', 'start_date' => '2025-10-04', 'end_date' => '2025-10-05']);
        $type = \App\Models\GeneralConferenceSessionType::create(['name' => 'Morning', 'slug' => 'morning', 'display_order' => 1]);
        $saturday = \App\Models\GeneralConferenceSession::create(['general_conference_id' => $conference->id, 'session_type_id' => $type->id, 'name' => 'Saturday Morning', 'session_date' => '2025-10-04', 'display_order' => 1]);
        $sunday = \App\Models\GeneralConferenceSession::create(['general_conference_id' => $conference->id, 'session_type_id' => $type->id, 'name' => 'Sunday Morning', 'session_date' => '2025-10-05', 'display_order' => 2]);
        // Created out of session order to prove ordering follows sessions.
        Talk::create(['source_id' => $source->id, 'general_conference_session_id' => $sunday->id, 'speaker_name' => 'Speaker B', 'title' => 'Sunday Talk', 'display_order' => 1]);
        Talk::create(['source_id' => $source->id, 'general_conference_session_id' => $saturday->id, 'speaker_name' => 'Speaker A', 'title' => 'Saturday Talk', 'display_order' => 1]);

        $this->actingAs($user)->post(route('study-plans.store'), [
            'name' => 'Oct 2025 Conference',
            'type' => 'talks',
            'mode' => 'conference',
            'general_conference_id' => $conference->id,
        ])->assertSessionHasNoErrors();

        $plan = StudyPlan::first();
        $titles = $plan->items()->with('talk')->get()->pluck('talk.title')->all();
        $this->assertSame(['Saturday Talk', 'Sunday Talk'], $titles);
        $this->assertSame('October 2025 General Conference', $plan->criteria_summary);
    }

    public function test_scripture_sessions_are_balanced_by_verse_count(): void
    {
        $user = User::factory()->create();
        $volume = ScriptureVolume::create(['name' => 'Test Volume', 'slug' => 'test', 'abbreviation' => 'TV', 'sort_order' => 1]);
        $book = ScriptureBook::create(['volume_id' => $volume->id, 'name' => 'Test Book', 'slug' => 'test-book', 'abbreviation' => 'TB', 'sort_order' => 1, 'chapter_count' => 4]);
        // One long chapter then three short ones.
        foreach ([60, 5, 5, 5] as $n => $verses) {
            ScriptureChapter::create(['book_id' => $book->id, 'chapter_number' => $n + 1, 'verse_count' => $verses]);
        }

        $this->actingAs($user)->post(route('study-plans.store'), [
            'name' => 'Balanced',
            'type' => 'scripture',
            'volume_id' => $volume->id,
            'mode' => 'author',
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-04',
            'frequency' => 'daily',
        ])->assertSessionHasNoErrors();

        $plan = StudyPlan::first();
        $bySession = $plan->items()->with('chapter')->get()->groupBy('session_number');

        // The 60-verse chapter fills day one alone; the three 5-verse
        // chapters share day two — not a 2/2 split by chapter count.
        $this->assertCount(1, $bySession[1]);
        $this->assertSame(60, (int) $bySession[1]->first()->chapter->verse_count);
        $this->assertCount(3, $bySession[2]);
    }

    public function test_shared_members_can_view_and_complete_but_not_edit(): void
    {
        $owner = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();
        \App\Models\Friendship::create(['requester_id' => $owner->id, 'addressee_id' => $friend->id, 'status' => 'accepted']);
        $volume = $this->makeVolume();

        $plan = StudyPlan::create([
            'user_id' => $owner->id,
            'name' => 'Group Study',
            'type' => 'scripture',
            'config' => ['volume_id' => $volume->id, 'book_ids' => null],
        ]);
        app(\App\Services\StudyPlanScheduler::class)->generate($plan);

        // Owner shares with the friend; a non-friend id is silently ignored.
        \Illuminate\Support\Facades\Mail::fake();
        $this->actingAs($owner)->put(route('study-plans.members.update', $plan), [
            'user_ids' => [$friend->id, $stranger->id],
        ])->assertSessionHasNoErrors();
        $this->assertSame([$friend->id], $plan->members()->pluck('users.id')->all());

        // The new member is emailed; re-syncing the same list emails nobody.
        \Illuminate\Support\Facades\Mail::assertSent(
            \App\Mail\StudyPlanSharedMail::class,
            fn ($mail) => $mail->hasTo($friend->email)
        );
        $this->actingAs($owner)->put(route('study-plans.members.update', $plan), ['user_ids' => [$friend->id]]);
        \Illuminate\Support\Facades\Mail::assertSentCount(1);

        // Unseen until the member opens the plan — the bell shows it and the
        // plan is badged; both clear after viewing.
        $this->actingAs($friend)->get(route('study-plans.index'))
            ->assertInertia(fn ($page) => $page
                ->where('notifications.count', 1)
                ->where('notifications.items.0.type', 'shared_plan')
                ->where('plans.0.is_new', true));
        $this->actingAs($friend)->get(route('study-plans.show', $plan))->assertOk();
        $this->actingAs($friend)->get(route('study-plans.index'))
            ->assertInertia(fn ($page) => $page
                ->where('notifications.count', 0)
                ->where('plans.0.is_new', false));
        $item = $plan->items()->first();
        $this->actingAs($friend)->patch(route('study-plans.items.toggle', [$plan, $item]));
        $item->refresh();
        $this->assertNotNull($item->completed_at);
        $this->assertSame($friend->id, $item->completed_by);

        // But members cannot edit, reshare, or delete the plan.
        $this->actingAs($friend)->get(route('study-plans.edit', $plan))->assertForbidden();
        $this->actingAs($friend)->put(route('study-plans.members.update', $plan), ['user_ids' => []])->assertForbidden();
        $this->actingAs($friend)->delete(route('study-plans.destroy', $plan))->assertForbidden();

        // Strangers still see nothing.
        $this->actingAs($stranger)->get(route('study-plans.show', $plan))->assertForbidden();

        // The shared plan appears on the member's index.
        $this->actingAs($friend)->get(route('study-plans.index'))
            ->assertInertia(fn ($page) => $page->has('plans', 1));
    }

    public function test_users_cannot_view_others_plans(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $volume = $this->makeVolume();

        $plan = StudyPlan::create([
            'user_id' => $owner->id,
            'name' => 'Private',
            'type' => 'scripture',
            'config' => ['volume_id' => $volume->id, 'book_ids' => null],
        ]);

        $this->actingAs($other)->get(route('study-plans.show', $plan))->assertForbidden();
        $this->actingAs($owner)->get(route('study-plans.show', $plan))->assertOk();
    }
}
