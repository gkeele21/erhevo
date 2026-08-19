<?php

namespace Tests\Feature;

use App\Models\Temple;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TempleTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trip_can_be_created_with_initial_temples(): void
    {
        $temples = Temple::factory()->count(3)->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/temple-trips', [
                'name' => 'Utah trip',
                'temple_ids' => $temples->pluck('id')->all(),
            ])
            ->assertRedirect();

        $trip = $user->templeTrips()->sole();
        $this->assertSame('Utah trip', $trip->name);
        $this->assertSame(
            $temples->pluck('id')->all(),
            $trip->items()->orderBy('sort_order')->pluck('temple_id')->all()
        );
    }

    public function test_updating_items_preserves_completed_state_on_kept_temples(): void
    {
        [$kept, $removed, $added] = Temple::factory()->count(3)->create();
        $user = User::factory()->create();
        $trip = $user->templeTrips()->create(['name' => 'Trip']);
        $keptItem = $trip->items()->create(['temple_id' => $kept->id, 'sort_order' => 0, 'completed_at' => now()]);
        $trip->items()->create(['temple_id' => $removed->id, 'sort_order' => 1]);

        $this->actingAs($user)
            ->put("/temple-trips/{$trip->id}", [
                'name' => 'Trip',
                'temple_ids' => [$added->id, $kept->id],
            ])
            ->assertRedirect();

        $items = $trip->items()->orderBy('sort_order')->get();
        $this->assertSame([$added->id, $kept->id], $items->pluck('temple_id')->all());
        $this->assertNotNull($items->firstWhere('temple_id', $kept->id)->completed_at);
        $this->assertSame($keptItem->id, $items->firstWhere('temple_id', $kept->id)->id);
    }

    public function test_show_ships_coordinates_for_every_temple_so_nearby_ones_can_be_plotted(): void
    {
        $planned = Temple::factory()->create();
        $other = Temple::factory()->create();
        $user = User::factory()->create();
        $trip = $user->templeTrips()->create(['name' => 'Trip']);
        $trip->items()->create(['temple_id' => $planned->id, 'sort_order' => 0]);

        $this->actingAs($user)
            ->get("/temple-trips/{$trip->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Temples/Trips/Show')
                ->has('allTemples', 2)
                ->has('allTemples.0', fn (AssertableInertia $temple) => $temple
                    ->hasAll(['id', 'slug', 'name', 'city', 'state', 'country', 'latitude', 'longitude'])));
    }

    public function test_toggle_flips_completed_at(): void
    {
        $temple = Temple::factory()->create();
        $user = User::factory()->create();
        $trip = $user->templeTrips()->create(['name' => 'Trip']);
        $item = $trip->items()->create(['temple_id' => $temple->id, 'sort_order' => 0]);

        $this->actingAs($user)->patch("/temple-trips/{$trip->id}/items/{$item->id}")->assertRedirect();
        $this->assertNotNull($item->refresh()->completed_at);

        $this->actingAs($user)->patch("/temple-trips/{$trip->id}/items/{$item->id}")->assertRedirect();
        $this->assertNull($item->refresh()->completed_at);
    }

    public function test_items_can_be_added_and_removed_individually(): void
    {
        [$first, $second] = Temple::factory()->count(2)->create();
        $user = User::factory()->create();
        $trip = $user->templeTrips()->create(['name' => 'Trip']);

        $this->actingAs($user)
            ->post("/temple-trips/{$trip->id}/items", ['temple_id' => $first->id])
            ->assertRedirect();
        $this->actingAs($user)
            ->post("/temple-trips/{$trip->id}/items", ['temple_id' => $second->id])
            ->assertRedirect();
        // Adding a temple already in the trip is a no-op, not a duplicate.
        $this->actingAs($user)
            ->post("/temple-trips/{$trip->id}/items", ['temple_id' => $first->id])
            ->assertRedirect();

        $this->assertSame([$first->id, $second->id], $trip->items()->orderBy('sort_order')->pluck('temple_id')->all());

        $item = $trip->items()->firstWhere('temple_id', $first->id);
        $this->actingAs($user)
            ->delete("/temple-trips/{$trip->id}/items/{$item->id}")
            ->assertRedirect();

        $this->assertSame([$second->id], $trip->items()->pluck('temple_id')->all());
    }

    public function test_toggling_an_item_from_a_different_trip_is_a_404(): void
    {
        $temple = Temple::factory()->create();
        $user = User::factory()->create();
        $trip = $user->templeTrips()->create(['name' => 'Trip A']);
        $otherTrip = $user->templeTrips()->create(['name' => 'Trip B']);
        $item = $otherTrip->items()->create(['temple_id' => $temple->id, 'sort_order' => 0]);

        $this->actingAs($user)
            ->patch("/temple-trips/{$trip->id}/items/{$item->id}")
            ->assertNotFound();
    }

    public function test_another_users_trip_is_off_limits(): void
    {
        $temple = Temple::factory()->create();
        $owner = User::factory()->create();
        $trip = $owner->templeTrips()->create(['name' => 'Private trip']);
        $item = $trip->items()->create(['temple_id' => $temple->id, 'sort_order' => 0]);

        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get("/temple-trips/{$trip->id}")->assertForbidden();
        $this->actingAs($intruder)->put("/temple-trips/{$trip->id}", ['name' => 'Hacked'])->assertForbidden();
        $this->actingAs($intruder)->patch("/temple-trips/{$trip->id}/items/{$item->id}")->assertForbidden();
        $this->actingAs($intruder)->delete("/temple-trips/{$trip->id}")->assertForbidden();

        $this->assertDatabaseHas('temple_trips', ['id' => $trip->id, 'name' => 'Private trip']);
    }
}
