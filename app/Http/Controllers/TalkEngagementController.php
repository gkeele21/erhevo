<?php

namespace App\Http\Controllers;

use App\Models\Talk;
use App\Models\TalkRead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A user's personal engagement with a library talk: their star rating, whether
 * they favorited it, and the dates they read it. Every action redirects back so
 * the library page re-renders with fresh state.
 */
class TalkEngagementController extends Controller
{
    /** Set or change the current user's rating of a talk. */
    public function rate(Request $request, Talk $talk): RedirectResponse
    {
        $this->authorizeLibrary($request);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $talk->ratings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['rating' => $validated['rating']]
        );

        return back();
    }

    /** Withdraw the current user's rating, leaving the talk unrated by them. */
    public function destroyRating(Request $request, Talk $talk): RedirectResponse
    {
        $this->authorizeLibrary($request);

        // Deleted one at a time (rather than by mass delete) so the model's
        // hook recalculates the talk's cached average.
        $talk->ratings()
            ->where('user_id', $request->user()->id)
            ->get()
            ->each->delete();

        return back();
    }

    public function toggleFavorite(Request $request, Talk $talk): RedirectResponse
    {
        $this->authorizeLibrary($request);

        $existing = $talk->favorites()->where('user_id', $request->user()->id)->first();

        if ($existing) {
            $existing->delete();

            return back()->with('success', 'Removed from your favorites.');
        }

        $talk->favorites()->create(['user_id' => $request->user()->id]);

        return back()->with('success', 'Added to your favorites.');
    }

    /**
     * Log a date the user read this talk. Re-reads are kept as separate dates;
     * logging the same date twice is a no-op rather than an error.
     */
    public function storeRead(Request $request, Talk $talk): RedirectResponse
    {
        $this->authorizeLibrary($request);

        $validated = $request->validate([
            'read_on' => 'required|date|before_or_equal:today',
        ], [
            'read_on.before_or_equal' => 'You can only log a date you have already reached.',
        ]);

        $read = $talk->reads()->firstOrCreate([
            'user_id' => $request->user()->id,
            'read_on' => date('Y-m-d', strtotime($validated['read_on'])),
        ]);

        return back()->with('success', $read->wasRecentlyCreated
            ? 'Marked as read on ' . $read->read_on->format('M j, Y') . '.'
            : 'You had already logged that date.');
    }

    public function destroyRead(Request $request, Talk $talk, TalkRead $read): RedirectResponse
    {
        $this->authorizeLibrary($request);

        abort_unless(
            $read->user_id === $request->user()->id && $read->talk_id === $talk->id,
            403
        );

        $read->delete();

        return back()->with('success', 'Read date removed.');
    }

    /** Same gate the library itself uses. */
    private function authorizeLibrary(Request $request): void
    {
        abort_unless($request->user()->show_lds_content, 403);
    }
}
