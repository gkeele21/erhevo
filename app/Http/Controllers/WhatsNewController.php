<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsNewController extends Controller
{
    /**
     * The full history of announcements from config/whats_new.php, grouped by
     * the date they shipped (newest first). The dashboard card only shows what
     * a user hasn't dismissed; this page keeps everything, so people can catch
     * up on features that landed before they joined or after they hit "Got it".
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        // Same cutoff the dashboard card uses, so an entry stops being badged
        // "New" here the moment it's dismissed there (and vice versa). Null for
        // guests, who have nothing to have missed.
        $seenThrough = $user
            ? ($user->getSetting('whats_new_seen_through') ?? $user->created_at->toDateString())
            : null;

        $releases = collect(config('whats_new.entries', []))
            ->sortByDesc('date')
            ->groupBy('date')
            ->map(fn ($entries, $date) => [
                'date' => $date,
                'is_new' => $seenThrough !== null && $date > $seenThrough,
                'entries' => $entries->map(fn ($entry) => [
                    'title' => $entry['title'],
                    'body' => $entry['body'],
                    'help_anchor' => $entry['help_anchor'] ?? null,
                ])->values()->all(),
            ])
            ->values();

        return Inertia::render('WhatsNew', [
            'releases' => $releases,
            // Drives the "Mark all as read" button, which stores this date in
            // `whats_new_seen_through` exactly like dismissing the card does.
            'latestDate' => $releases->first()['date'] ?? null,
        ]);
    }
}
