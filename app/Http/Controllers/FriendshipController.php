<?php

namespace App\Http\Controllers;

use App\Mail\FriendInvitationMail;
use App\Models\FriendInvitation;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
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
            'invitations' => FriendInvitation::where('inviter_id', $user->id)
                ->pending()
                ->latest()
                ->get(['id', 'email', 'created_at']),
        ]);
    }

    /**
     * Invite one or more people by email to join and become friends on
     * registration. Emails that already belong to members get a normal
     * friend request instead.
     */
    public function invite(Request $request)
    {
        $validated = $request->validate([
            'emails' => 'required|array|min:1|max:10',
            'emails.*' => 'required|string|email|max:255',
            'message' => 'nullable|string|max:1000',
        ], [
            'emails.required' => 'Please add at least one email address.',
            'emails.max' => 'You can invite up to 10 people at a time.',
            'emails.*.email' => 'One of the addresses is not a valid email.',
        ]);

        $currentUser = $request->user();
        $message = $validated['message'] ?? null;
        $emails = collect($validated['emails'])
            ->map(fn ($email) => strtolower(trim($email)))
            ->unique()
            ->values();

        $invited = [];
        $requested = [];
        $skipped = [];

        foreach ($emails as $email) {
            if ($email === strtolower($currentUser->email)) {
                $skipped[] = "{$email} (that's you)";

                continue;
            }

            // Already a member? Send a normal friend request instead.
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                if ($currentUser->isFriendWith($existingUser->id)) {
                    $skipped[] = "{$email} (already friends)";

                    continue;
                }
                if ($currentUser->hasSentFriendRequestTo($existingUser->id)) {
                    $skipped[] = "{$email} (friend request already pending)";

                    continue;
                }

                $currentUser->sendFriendRequest($existingUser);
                $requested[] = $email;

                continue;
            }

            $alreadyInvited = FriendInvitation::where('inviter_id', $currentUser->id)
                ->where('email', $email)
                ->pending()
                ->exists();

            if ($alreadyInvited) {
                $skipped[] = "{$email} (already invited)";

                continue;
            }

            $invitation = FriendInvitation::create([
                'inviter_id' => $currentUser->id,
                'email' => $email,
                'token' => FriendInvitation::generateToken(),
                'message' => $message,
            ]);

            try {
                Mail::to($email)->send(new FriendInvitationMail($invitation));
            } catch (\Throwable $e) {
                report($e);
                // The invitation is useless without its email — remove it so
                // the user can retry once mail is working again.
                $invitation->delete();
                $skipped[] = "{$email} (email could not be sent)";

                continue;
            }

            $invited[] = $email;
        }

        $successParts = [];
        if ($invited) {
            $successParts[] = count($invited) === 1
                ? "Invitation sent to {$invited[0]}!"
                : 'Invitations sent to '.count($invited).' people!';
        }
        if ($requested) {
            $successParts[] = count($requested) === 1
                ? "{$requested[0]} is already a member, so we sent them a friend request instead."
                : implode(', ', $requested).' are already members, so we sent them friend requests instead.';
        }

        $response = back();
        if ($successParts) {
            $response->with('success', implode(' ', $successParts));
        }
        if ($skipped) {
            $response->with('error', 'Skipped: '.implode(', ', $skipped).'.');
        }

        return $response;
    }

    /**
     * Landing page for the "Join" link in invitation emails. Works whatever
     * state the recipient is in:
     *  - guest: on to registration (with the invite carried through, and the
     *    intended URL saved so logging in instead also connects them)
     *  - logged-in member: befriend the inviter right now
     *  - already connected (e.g. they just registered): friendly confirmation
     */
    public function acceptInvitationLink(Request $request, string $token)
    {
        $invitation = FriendInvitation::with('inviter')->where('token', $token)->first();

        if (! $invitation || ! $invitation->inviter) {
            return $request->user()
                ? redirect()->route('friends.index')->with('error', 'That invitation link is no longer valid.')
                : redirect()->route('register');
        }

        if (! $request->user()) {
            // After registering — or logging in instead — come back here so
            // the friendship gets made either way.
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('register', ['invite' => $invitation->token]);
        }

        $user = $request->user();

        if ($invitation->inviter_id === $user->id) {
            return redirect()->route('friends.index')
                ->with('error', 'That is your own invitation — forward it to your friend instead.');
        }

        if (! $invitation->accepted_at) {
            $invitation->acceptFor($user);

            return redirect()->route('friends.index')
                ->with('success', "You and {$invitation->inviter->name} are now friends!");
        }

        if ($invitation->registered_user_id === $user->id || $invitation->inviter->isFriendWith($user->id)) {
            return redirect()->route('friends.index')
                ->with('success', "You and {$invitation->inviter->name} are connected!");
        }

        return redirect()->route('friends.index')
            ->with('error', 'That invitation was already used by someone else.');
    }

    /**
     * Cancel a pending invitation.
     */
    public function cancelInvitation(Request $request, FriendInvitation $invitation)
    {
        if ($invitation->inviter_id !== $request->user()->id) {
            abort(403);
        }

        if ($invitation->accepted_at) {
            return back()->with('error', 'This invitation has already been accepted.');
        }

        $invitation->delete();

        return back()->with('success', 'Invitation cancelled.');
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
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'profile_photo_path']);

        return response()->json($users);
    }
}
