<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates auth-only sections that exist solely for LDS content (e.g. Temple
 * Tracker). Assumes an authenticated user; combine with auth middleware.
 */
class EnsureLdsContentEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()->show_lds_content, 403);

        return $next($request);
    }
}
