<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $myStories = Story::where('user_id', $user->id)
            ->with(['category', 'tags'])
            ->latest()
            ->paginate(10);

        $friendStories = Story::with(['user', 'category', 'tags'])
            ->whereIn('user_id', $user->friendIds())
            ->where('visibility', '!=', 'private')
            ->published()
            ->latest('published_at')
            ->limit(5)
            ->get();

        $pendingFriendRequests = $user->pendingFriendRequests()
            ->with('requester')
            ->count();

        return Inertia::render('Dashboard', [
            'myStories' => $myStories,
            'friendStories' => $friendStories,
            'pendingFriendRequestsCount' => $pendingFriendRequests,
        ]);
    }
}
