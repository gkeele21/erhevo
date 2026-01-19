<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FriendshipController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Friends/Index', [
            'friends' => $user->friends()->get(),
            'pendingRequests' => $user->pendingFriendRequests()
                ->with('requester')
                ->get(),
            'sentRequests' => $user->sentFriendRequests()
                ->pending()
                ->with('addressee')
                ->get(),
        ]);
    }

    public function sendRequest(Request $request, User $user)
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return back()->with('error', 'You cannot send a friend request to yourself.');
        }

        if ($currentUser->isFriendWith($user->id)) {
            return back()->with('error', 'You are already friends with this user.');
        }

        if ($currentUser->hasSentFriendRequestTo($user->id)) {
            return back()->with('error', 'You have already sent a friend request to this user.');
        }

        if ($currentUser->hasPendingFriendRequestFrom($user->id)) {
            // Auto-accept if they already sent us a request
            $friendship = Friendship::where('requester_id', $user->id)
                ->where('addressee_id', $currentUser->id)
                ->pending()
                ->first();

            if ($friendship) {
                $friendship->accept();

                return back()->with('success', 'Friend request accepted!');
            }
        }

        $currentUser->sendFriendRequest($user);

        return back()->with('success', 'Friend request sent!');
    }

    public function accept(Friendship $friendship)
    {
        Gate::authorize('respond', $friendship);

        $friendship->accept();

        return back()->with('success', 'Friend request accepted!');
    }

    public function decline(Friendship $friendship)
    {
        Gate::authorize('respond', $friendship);

        $friendship->decline();

        return back()->with('success', 'Friend request declined.');
    }

    public function remove(Request $request, User $user)
    {
        $request->user()->removeFriend($user);

        return back()->with('success', 'Friend removed.');
    }

    public function block(Request $request, User $user)
    {
        $request->user()->blockUser($user);

        return back()->with('success', 'User blocked.');
    }

    public function searchUsers(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::where('id', '!=', $request->user()->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'profile_photo_path']);

        return response()->json($users);
    }
}
