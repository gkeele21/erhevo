<?php

namespace Tests\Feature;

use App\Models\Temple;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TempleVisitTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visit_can_be_logged_with_ordinances(): void
    {
        $temple = Temple::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/temple-visits', [
                'temple_id' => $temple->id,
                'visited_on' => '2026-08-01',
                'ordinances' => ['endowment', 'sealing'],
                'notes' => 'Great day.',
            ])
            ->assertRedirect();

        $visit = $user->templeVisits()->sole();
        $this->assertSame(['endowment', 'sealing'], $visit->ordinances);
        $this->assertSame('2026-08-01', $visit->visited_on->toDateString());
    }

    public function test_a_visit_with_no_ordinances_is_just_a_visit(): void
    {
        $temple = Temple::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/temple-visits', [
                'temple_id' => $temple->id,
                'visited_on' => '2026-08-01',
                'ordinances' => [],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame([], $user->templeVisits()->sole()->ordinances);
    }

    public function test_unknown_ordinance_values_are_rejected(): void
    {
        $temple = Temple::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post('/temple-visits', [
                'temple_id' => $temple->id,
                'visited_on' => '2026-08-01',
                'ordinances' => ['baptism_confirmation', 'ward_activity'],
            ])
            ->assertSessionHasErrors('ordinances.1');
    }

    public function test_future_visit_dates_are_rejected(): void
    {
        $temple = Temple::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post('/temple-visits', [
                'temple_id' => $temple->id,
                'visited_on' => now()->addDay()->toDateString(),
                'ordinances' => [],
            ])
            ->assertSessionHasErrors('visited_on');
    }

    public function test_a_user_can_update_and_delete_their_own_visit(): void
    {
        $temple = Temple::factory()->create();
        $user = User::factory()->create();
        $visit = $user->templeVisits()->create([
            'temple_id' => $temple->id,
            'visited_on' => '2026-08-01',
            'ordinances' => [],
        ]);

        $this->actingAs($user)
            ->put("/temple-visits/{$visit->id}", [
                'temple_id' => $temple->id,
                'visited_on' => '2026-07-15',
                'ordinances' => ['initiatory'],
                'notes' => 'updated',
            ])
            ->assertRedirect();

        $visit->refresh();
        $this->assertSame('2026-07-15', $visit->visited_on->toDateString());
        $this->assertSame(['initiatory'], $visit->ordinances);

        $this->actingAs($user)->delete("/temple-visits/{$visit->id}")->assertRedirect();
        $this->assertDatabaseMissing('temple_visits', ['id' => $visit->id]);
    }

    public function test_another_users_visit_cannot_be_updated_or_deleted(): void
    {
        $temple = Temple::factory()->create();
        $owner = User::factory()->create();
        $visit = $owner->templeVisits()->create([
            'temple_id' => $temple->id,
            'visited_on' => '2026-08-01',
            'ordinances' => [],
        ]);

        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->put("/temple-visits/{$visit->id}", [
                'temple_id' => $temple->id,
                'visited_on' => '2026-08-02',
                'ordinances' => [],
            ])
            ->assertForbidden();

        $this->actingAs($intruder)->delete("/temple-visits/{$visit->id}")->assertForbidden();
        $this->assertDatabaseHas('temple_visits', ['id' => $visit->id]);
    }
}
