<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TempleGeocodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_returns_a_center_for_a_typed_address(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '40.2338', 'lon' => '-111.6585', 'display_name' => 'Provo, Utah County, Utah, United States'],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/temples/geocode?q=Provo, UT')
            ->assertOk()
            ->assertJson([
                'lat' => 40.2338,
                'lng' => -111.6585,
                'label' => 'Provo, Utah County, Utah, United States',
            ]);
    }

    public function test_a_hit_is_cached_so_the_same_address_is_looked_up_once(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '40.2338', 'lon' => '-111.6585', 'display_name' => 'Provo, Utah'],
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/temples/geocode?q=Provo, UT')->assertOk();
        $this->actingAs($user)->getJson('/temples/geocode?q=provo, ut')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_an_unknown_address_is_not_found_and_is_not_cached(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/temples/geocode?q=asdfqwerzxcv')->assertNotFound();
        $this->actingAs($user)->getJson('/temples/geocode?q=asdfqwerzxcv')->assertNotFound();

        Http::assertSentCount(2);
    }

    public function test_a_too_short_query_is_rejected_without_calling_the_geocoder(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->getJson('/temples/geocode?q=a')
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_guests_cannot_geocode(): void
    {
        $this->get('/temples/geocode?q=Provo')->assertRedirect('/login');
    }
}
