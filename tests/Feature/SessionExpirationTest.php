<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * A tab left open past SESSION_LIFETIME should land on the login page with an
 * explanation, never on Laravel's "Page Expired" screen. See bootstrap/app.php.
 */
class SessionExpirationTest extends TestCase
{
    use RefreshDatabase;

    /** A route that fails the way a stale CSRF token does. */
    private function registerExpiredTokenRoute(): void
    {
        Route::middleware('web')->post('/__expired-token', fn () => abort(419));
    }

    public function test_expired_session_on_a_page_visit_redirects_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_a_timed_out_session_says_so_when_the_cookie_is_still_around(): void
    {
        $this->withCookie(config('session.cookie'), 'stale-session-id')
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'timed out'));
    }

    public function test_a_first_time_visitor_is_not_told_their_session_timed_out(): void
    {
        $this->get(route('dashboard'))
            ->assertSessionHas('error', fn ($message) => ! str_contains($message, 'timed out'));
    }

    public function test_the_login_page_shows_the_reason_the_user_landed_there(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/Login')
                ->where('flash.error', fn ($message) => filled($message)));
    }

    public function test_the_user_is_returned_to_where_they_were_headed_after_signing_in(): void
    {
        $this->get(route('posts.create'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', route('posts.create'));
    }

    public function test_a_dead_csrf_token_redirects_to_login_rather_than_showing_page_expired(): void
    {
        $this->registerExpiredTokenRoute();

        $this->post('/__expired-token')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'timed out'));
    }

    public function test_a_dead_csrf_token_on_an_inertia_visit_redirects_rather_than_erroring(): void
    {
        $this->registerExpiredTokenRoute();

        // Inertia sends X-Requested-With but asks for HTML, so it must get the
        // redirect and not the JSON branch meant for axios.
        $this->post('/__expired-token', [], [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html, application/xhtml+xml',
        ])->assertRedirect(route('login'));
    }

    public function test_xhr_callers_get_a_401_carrying_the_login_url(): void
    {
        // What axios sends: the interceptor in resources/js/bootstrap.js turns
        // this into a navigation rather than an inline error.
        $this->getJson(route('dashboard'))
            ->assertStatus(401)
            ->assertJsonPath('redirect', route('login'));
    }
}
