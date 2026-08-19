<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The whole Temple Tracker section is auth-only (visits and trips are
 * personal) and sits behind the `lds` middleware, unlike the
 * guest-browsable Library.
 */
class TempleTrackerGateTest extends TestCase
{
    use RefreshDatabase;

    public static function sectionPages(): array
    {
        return [
            'temple list' => ['/temples'],
            'explore' => ['/temples/explore'],
            'my visits' => ['/temple-visits'],
            'trips' => ['/temple-trips'],
        ];
    }

    /** @dataProvider sectionPages */
    public function test_guests_are_redirected_to_login(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
    }

    /** @dataProvider sectionPages */
    public function test_users_with_lds_content_enabled_can_access(string $uri): void
    {
        $user = User::factory()->create();
        $user->setSetting('show_lds_content', true)->save();

        $this->actingAs($user)->get($uri)->assertOk();
    }

    /** @dataProvider sectionPages */
    public function test_lds_content_is_enabled_by_default(string $uri): void
    {
        // show_lds_content defaults to true (opt-out model).
        $this->actingAs(User::factory()->create())->get($uri)->assertOk();
    }

    /** @dataProvider sectionPages */
    public function test_users_with_lds_content_disabled_are_forbidden(string $uri): void
    {
        $user = User::factory()->create();
        $user->setSetting('show_lds_content', false)->save();

        $this->actingAs($user)->get($uri)->assertForbidden();
    }
}
