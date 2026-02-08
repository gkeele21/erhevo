<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\StoryEditToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StoryShareController extends Controller
{
    public function index(Story $story)
    {
        Gate::authorize('update', $story);

        return Inertia::render('Posts/Share', [
            'story' => $story,
            'tokens' => $story->editTokens()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'url' => route('posts.shared.edit', $token->token),
                    'created_at' => $token->created_at,
                    'expires_at' => $token->expires_at,
                    'last_used_at' => $token->last_used_at,
                    'is_active' => $token->is_active,
                    'is_expired' => $token->expires_at->isPast(),
                    'is_valid' => $token->isValid(),
                ]),
        ]);
    }

    public function store(Request $request, Story $story)
    {
        Gate::authorize('update', $story);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'expires_in_days' => 'required|integer|in:7,14,30',
        ]);

        $rawToken = StoryEditToken::generateToken();

        $story->editTokens()->create([
            'token' => $rawToken,
            'name' => $validated['name'] ?? null,
            'expires_at' => now()->addDays($validated['expires_in_days']),
        ]);

        $shareUrl = route('posts.shared.edit', $rawToken);

        return back()->with([
            'success' => 'Share link created successfully.',
            'shareUrl' => $shareUrl,
        ]);
    }

    public function destroy(Story $story, StoryEditToken $token)
    {
        Gate::authorize('update', $story);

        if ($token->story_id !== $story->id) {
            abort(404);
        }

        $token->update(['is_active' => false]);

        return back()->with('success', 'Share link revoked successfully.');
    }
}
