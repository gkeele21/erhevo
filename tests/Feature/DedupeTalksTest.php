<?php

namespace Tests\Feature;

use App\Models\GeneralConference;
use App\Models\GeneralConferenceSession;
use App\Models\GeneralConferenceSessionType;
use App\Models\Source;
use App\Models\StudyPlan;
use App\Models\StudyPlanItem;
use App\Models\Tag;
use App\Models\Talk;
use App\Models\TalkFavorite;
use App\Models\TalkRating;
use App\Models\TalkRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DedupeTalksTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://example.test/study/general-conference/1973/10/behold-thy-mother?lang=eng';

    public function test_a_dry_run_reports_without_changing_anything(): void
    {
        ['loser' => $loser] = $this->makeDuplicatePair();

        $this->artisan('talks:dedupe')
            ->expectsOutputToContain('1 duplicate group: 1 resolvable, 0 needing review.')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertDatabaseHas('talks', ['id' => $loser->id]);
    }

    public function test_applying_keeps_the_filed_talk_and_deletes_the_stub(): void
    {
        ['winner' => $winner, 'loser' => $loser] = $this->makeDuplicatePair();

        $this->artisan('talks:dedupe --apply')->assertSuccessful();

        $this->assertDatabaseHas('talks', ['id' => $winner->id]);
        $this->assertDatabaseMissing('talks', ['id' => $loser->id]);
    }

    public function test_the_merge_unions_tags_without_tripping_the_unique_key(): void
    {
        ['winner' => $winner, 'loser' => $loser] = $this->makeDuplicatePair();

        $shared = Tag::findOrCreateByName('Faith');
        $onlyOnLoser = Tag::findOrCreateByName('Charity');

        $winner->tags()->attach($shared);
        $loser->tags()->attach([$shared->id, $onlyOnLoser->id]);

        $this->artisan('talks:dedupe --apply')->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            ['Charity', 'Faith'],
            $winner->fresh()->tags->pluck('name')->sort()->values()->all()
        );
        $this->assertDatabaseMissing('talk_tag', ['talk_id' => $loser->id]);
    }

    public function test_a_study_plan_item_is_repointed_rather_than_lost(): void
    {
        ['winner' => $winner, 'loser' => $loser] = $this->makeDuplicatePair();

        $plan = StudyPlan::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Monson Talks',
            'type' => 'talks',
            'config' => ['mode' => 'author'],
        ]);
        $item = StudyPlanItem::create([
            'study_plan_id' => $plan->id,
            'talk_id' => $loser->id,
            'session_number' => 1,
            'sort_order' => 1,
        ]);

        $this->artisan('talks:dedupe --apply')->assertSuccessful();

        // The plan keeps its item; it just points at the surviving talk now.
        $this->assertSame(1, $plan->items()->count());
        $this->assertSame($winner->id, $item->fresh()->talk_id);
    }

    public function test_a_plan_holding_both_rows_keeps_only_one_item(): void
    {
        ['winner' => $winner, 'loser' => $loser] = $this->makeDuplicatePair();

        $plan = StudyPlan::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Monson Talks',
            'type' => 'talks',
            'config' => ['mode' => 'author'],
        ]);

        foreach ([$winner->id, $loser->id] as $index => $talkId) {
            StudyPlanItem::create([
                'study_plan_id' => $plan->id,
                'talk_id' => $talkId,
                'session_number' => 1,
                'sort_order' => $index + 1,
            ]);
        }

        $this->artisan('talks:dedupe --apply')->assertSuccessful();

        $this->assertSame(1, $plan->items()->count());
        $this->assertSame($winner->id, $plan->items()->first()->talk_id);
    }

    public function test_ratings_favorites_and_read_dates_move_across(): void
    {
        ['winner' => $winner, 'loser' => $loser] = $this->makeDuplicatePair();
        $user = User::factory()->create();

        TalkRating::create(['talk_id' => $loser->id, 'user_id' => $user->id, 'rating' => 5]);
        TalkFavorite::create(['talk_id' => $loser->id, 'user_id' => $user->id]);
        TalkRead::create(['talk_id' => $loser->id, 'user_id' => $user->id, 'read_on' => '2026-01-05']);

        $this->artisan('talks:dedupe --apply')->assertSuccessful();

        $this->assertDatabaseHas('talk_ratings', ['talk_id' => $winner->id, 'user_id' => $user->id, 'rating' => 5]);
        $this->assertDatabaseHas('talk_favorites', ['talk_id' => $winner->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('talk_reads', ['talk_id' => $winner->id, 'user_id' => $user->id]);

        // And the winner's cached average reflects the moved rating.
        TalkRating::refreshTalkAverage($winner->id);
        $this->assertSame(5.0, (float) $winner->fresh()->average_rating);
    }

    public function test_a_users_rating_on_both_rows_collapses_to_one(): void
    {
        ['winner' => $winner, 'loser' => $loser] = $this->makeDuplicatePair();
        $user = User::factory()->create();

        TalkRating::create(['talk_id' => $winner->id, 'user_id' => $user->id, 'rating' => 3]);
        TalkRating::create(['talk_id' => $loser->id, 'user_id' => $user->id, 'rating' => 5]);

        $this->artisan('talks:dedupe --apply')->assertSuccessful();

        // The rating on the surviving row is the one kept — no unique violation.
        $this->assertSame(1, TalkRating::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('talk_ratings', ['talk_id' => $winner->id, 'rating' => 3]);
    }

    public function test_a_group_with_two_filed_rows_is_left_for_review(): void
    {
        ['winner' => $winner] = $this->makeDuplicatePair();

        // A second fully-filed row makes the winner ambiguous.
        $rival = Talk::create([
            'source_id' => $winner->source_id,
            'general_conference_session_id' => $winner->general_conference_session_id,
            'speaker_name' => 'Thomas S. Monson',
            'title' => 'Behold Thy Mother (Longer Title)',
            'talk_date' => '1973-10-07',
            'url' => self::URL,
        ]);

        $this->artisan('talks:dedupe --apply')
            ->expectsOutputToContain('needing review')
            ->assertSuccessful();

        $this->assertDatabaseHas('talks', ['id' => $winner->id]);
        $this->assertDatabaseHas('talks', ['id' => $rival->id]);
    }

    public function test_talks_without_a_url_are_never_grouped_together(): void
    {
        $source = Source::firstOrCreate(['slug' => 'gc'], ['name' => 'General Conference']);

        foreach (['One', 'Two'] as $title) {
            Talk::create([
                'source_id' => $source->id,
                'speaker_name' => 'Speaker',
                'title' => $title,
                'talk_date' => '2024-04-06',
                'url' => null,
            ]);
        }

        $this->artisan('talks:dedupe --apply')
            ->expectsOutputToContain('No duplicate talks found.')
            ->assertSuccessful();

        $this->assertSame(2, Talk::count());
    }

    /**
     * The real-world shape: one fully-filed row (session + true speaking date)
     * and one earlier stub (no session, date synthesized to the 1st).
     *
     * @return array{winner: Talk, loser: Talk}
     */
    private function makeDuplicatePair(): array
    {
        $source = Source::firstOrCreate(
            ['slug' => 'general-conference'],
            ['name' => 'General Conference', 'is_active' => true]
        );

        $conference = GeneralConference::create([
            'name' => 'October 1973 General Conference',
            'year' => 1973,
            'month' => 'october',
            'start_date' => '1973-10-06',
            'end_date' => '1973-10-07',
        ]);

        $session = GeneralConferenceSession::create([
            'general_conference_id' => $conference->id,
            'session_type_id' => GeneralConferenceSessionType::create([
                'name' => 'Saturday Morning',
                'slug' => 'saturday-morning',
                'display_order' => 1,
            ])->id,
            'name' => 'Saturday Morning Session',
            'session_date' => '1973-10-06',
            'display_order' => 1,
        ]);

        $loser = Talk::create([
            'source_id' => $source->id,
            'speaker_name' => 'Thomas S. Monson',
            'title' => 'Behold Thy Mother',
            'talk_date' => '1973-10-01',
            'url' => self::URL,
        ]);

        $winner = Talk::create([
            'source_id' => $source->id,
            'general_conference_session_id' => $session->id,
            'speaker_name' => 'Thomas S. Monson',
            'title' => 'Behold Thy Mother',
            'talk_date' => '1973-10-06',
            'url' => self::URL,
        ]);

        return ['winner' => $winner, 'loser' => $loser];
    }
}
