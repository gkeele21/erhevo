<?php

use App\Http\Middleware\EnsureLdsContentEnabled;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'lds' => EnsureLdsContentEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A tab left open past SESSION_LIFETIME loses its login and its CSRF
        // token together. Neither of Laravel's defaults is friendly there: a
        // dead token gives the "Page Expired" screen (an error modal, under
        // Inertia) and a dead login redirects to /login with no explanation.
        // Both cases mean the same thing to the user, so answer them the same
        // way — the login page, a reason, and a ride back to where they were.
        $sessionExpired = function (Request $request, ?string $message = null) {
            // A 419 is always an expiry. An AuthenticationException is only one
            // if the browser still holds a session cookie — otherwise this may
            // be a first-time visitor following a link to a members-only page,
            // and telling them they "timed out" would be nonsense.
            $message ??= $request->hasCookie(config('session.cookie'))
                ? 'Your session timed out for security. Please sign in again.'
                : 'Please sign in to continue.';

            // XHR callers (axios) can't follow a redirect into a rendered page,
            // so hand them the destination and let the response interceptor in
            // resources/js/bootstrap.js do the navigating.
            if ($request->expectsJson()) {
                if ($request->hasSession()) {
                    $request->session()->put('url.intended', url()->previous());
                    $request->session()->flash('error', $message);
                }

                return response()->json([
                    'message' => $message,
                    'redirect' => route('login'),
                ], 401);
            }

            // guest() remembers a GET as the intended URL and falls back to the
            // referrer for everything else, so Fortify lands them back here.
            return redirect()->guest(route('login'))->with('error', $message);
        };

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) use ($sessionExpired) {
            return $response->getStatusCode() === 419
                ? $sessionExpired($request, 'Your session timed out for security. Please sign in again.')
                : $response;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($sessionExpired) {
            return $sessionExpired($request);
        });
    })->create();
