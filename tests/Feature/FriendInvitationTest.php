<?php

namespace Tests\Feature;

use App\Mail\FriendInvitationMail;
use App\Models\FriendInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Features;
use Tests\TestCase;

class FriendInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_invite_someone_by_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/friends/invite', [
            'emails' => ['newfriend@example.com'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('friend_invitations', [
            'inviter_id' => $user->id,
            'email' => 'newfriend@example.com',
            'accepted_at' => null,
        ]);
        Mail::assertSent(FriendInvitationMail::class, fn ($mail) => $mail->hasTo('newfriend@example.com'));
    }

    public function test_user_can_invite_several_emails_at_once(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/friends/invite', [
            'emails' => ['one@example.com', 'two@example.com', 'One@Example.com'],
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        // The duplicate (differing only in case) is collapsed.
        $this->assertSame(2, FriendInvitation::where('inviter_id', $user->id)->count());
        Mail::assertSentCount(2);
        Mail::assertSent(FriendInvitationMail::class, fn ($mail) => $mail->hasTo('one@example.com'));
        Mail::assertSent(FriendInvitationMail::class, fn ($mail) => $mail->hasTo('two@example.com'));
    }

    public function test_mixed_batch_reports_sent_requested_and_skipped_addresses(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'me@example.com']);
        $member = User::factory()->create(['email' => 'member@example.com']);

        $response = $this->actingAs($user)->post('/friends/invite', [
            'emails' => ['new@example.com', 'member@example.com', 'me@example.com'],
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('error');

        // The new address got an email invitation.
        $this->assertDatabaseHas('friend_invitations', ['email' => 'new@example.com']);
        // The member got a friend request instead.
        $this->assertDatabaseHas('friendships', [
            'requester_id' => $user->id,
            'addressee_id' => $member->id,
            'status' => 'pending',
        ]);
        // Inviting yourself is skipped.
        $this->assertDatabaseMissing('friend_invitations', ['email' => 'me@example.com']);
        Mail::assertSentCount(1);
    }

    public function test_invitation_can_include_a_personal_message_that_appears_in_the_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/friends/invite', [
            'emails' => ['newfriend@example.com'],
            'message' => "Hey, I think you'd love this app!",
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('friend_invitations', [
            'inviter_id' => $user->id,
            'email' => 'newfriend@example.com',
            'message' => "Hey, I think you'd love this app!",
        ]);

        Mail::assertSent(FriendInvitationMail::class, function ($mail) {
            $rendered = $mail->render();

            return $mail->hasTo('newfriend@example.com')
                && str_contains($rendered, "Hey, I think you'd love this app!");
        });
    }

    public function test_personal_message_is_optional_and_limited_in_length(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        // Too long: rejected.
        $this->actingAs($user)->post('/friends/invite', [
            'emails' => ['newfriend@example.com'],
            'message' => str_repeat('a', 1001),
        ])->assertSessionHasErrors('message');

        // Omitted entirely: fine.
        $this->actingAs($user)->post('/friends/invite', [
            'emails' => ['newfriend@example.com'],
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('friend_invitations', [
            'email' => 'newfriend@example.com',
            'message' => null,
        ]);
    }

    public function test_failed_invitation_email_cleans_up_and_reports_instead_of_500(): void
    {
        // Simulate the SMTP provider rejecting the send (e.g. an unverified
        // sending domain).
        Mail::shouldReceive('to')->once()->andReturnUsing(function () {
            $pending = \Mockery::mock();
            $pending->shouldReceive('send')->once()->andThrow(
                new \Symfony\Component\Mailer\Exception\TransportException('550 5.7.1 Sending from domain not allowed')
            );

            return $pending;
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/friends/invite', [
            'emails' => ['newfriend@example.com'],
        ]);

        // No 500: redirected back with an error, and the dangling invitation
        // was removed so the user can retry later.
        $response->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseMissing('friend_invitations', ['email' => 'newfriend@example.com']);

        // The error actually reaches the page as a shared flash prop.
        $this->actingAs($user)->get('/friends')->assertInertia(fn ($page) => $page
            ->where('flash.error', 'Skipped: newfriend@example.com (email could not be sent).'));
    }

    public function test_inviting_an_existing_member_sends_a_friend_request_instead(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $member = User::factory()->create(['email' => 'member@example.com']);

        $this->actingAs($user)->post('/friends/invite', [
            'emails' => ['member@example.com'],
        ]);

        $this->assertDatabaseMissing('friend_invitations', ['email' => 'member@example.com']);
        $this->assertDatabaseHas('friendships', [
            'requester_id' => $user->id,
            'addressee_id' => $member->id,
            'status' => 'pending',
        ]);
        Mail::assertNothingSent();
    }

    public function test_duplicate_pending_invitation_is_rejected(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post('/friends/invite', ['emails' => ['friend@example.com']]);
        $this->actingAs($user)->post('/friends/invite', ['emails' => ['friend@example.com']]);

        $this->assertSame(1, FriendInvitation::where('email', 'friend@example.com')->count());
        Mail::assertSentCount(1);
    }

    public function test_invite_link_sends_guests_to_registration_and_remembers_the_link(): void
    {
        $inviter = User::factory()->create();
        $invitation = FriendInvitation::create([
            'inviter_id' => $inviter->id,
            'email' => 'friend@example.com',
            'token' => FriendInvitation::generateToken(),
        ]);

        $response = $this->get(route('friends.invite-link', $invitation->token));

        $response->assertRedirect(route('register', ['invite' => $invitation->token]));
        // Logging in (or registering) returns here so the friendship still happens.
        $this->assertSame(
            route('friends.invite-link', $invitation->token),
            session('url.intended')
        );
    }

    public function test_invite_link_connects_an_existing_logged_in_member_immediately(): void
    {
        $inviter = User::factory()->create();
        $member = User::factory()->create();
        $invitation = FriendInvitation::create([
            'inviter_id' => $inviter->id,
            'email' => $member->email,
            'token' => FriendInvitation::generateToken(),
        ]);

        $response = $this->actingAs($member)->get(route('friends.invite-link', $invitation->token));

        $response->assertRedirect(route('friends.index'))->assertSessionHas('success');
        $this->assertTrue($inviter->fresh()->isFriendWith($member->id));
        $this->assertNotNull($invitation->fresh()->accepted_at);

        // Clicking the link again is a friendly no-op.
        $this->actingAs($member)->get(route('friends.invite-link', $invitation->token))
            ->assertRedirect(route('friends.index'))->assertSessionHas('success');
        $this->assertSame(1, \App\Models\Friendship::count());
    }

    public function test_invite_link_handles_the_inviter_and_invalid_tokens(): void
    {
        $inviter = User::factory()->create();
        $invitation = FriendInvitation::create([
            'inviter_id' => $inviter->id,
            'email' => 'friend@example.com',
            'token' => FriendInvitation::generateToken(),
        ]);

        // Bad tokens fail soft for guests (before authenticating below).
        $this->get(route('friends.invite-link', 'not-a-real-token'))
            ->assertRedirect(route('register'));

        // The inviter clicking their own link gets an explanation, not a crash.
        $this->actingAs($inviter)->get(route('friends.invite-link', $invitation->token))
            ->assertRedirect(route('friends.index'))->assertSessionHas('error');

        // ...and bad tokens fail soft for members too.
        $this->actingAs($inviter)->get(route('friends.invite-link', 'not-a-real-token'))
            ->assertRedirect(route('friends.index'))->assertSessionHas('error');
    }

    public function test_registering_with_invite_token_creates_friendship(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $inviter = User::factory()->create();
        $invitation = FriendInvitation::create([
            'inviter_id' => $inviter->id,
            'email' => 'invited@example.com',
            'token' => FriendInvitation::generateToken(),
        ]);

        $response = $this->post('/register', [
            'first_name' => 'Invited',
            'last_name' => 'Person',
            'email' => 'invited@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
            'invite_token' => $invitation->token,
        ]);

        $this->assertAuthenticated();

        $newUser = User::where('email', 'invited@example.com')->first();
        $this->assertNotNull($newUser);

        $this->assertDatabaseHas('friendships', [
            'requester_id' => $inviter->id,
            'addressee_id' => $newUser->id,
            'status' => 'accepted',
        ]);

        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
        $this->assertSame($newUser->id, $invitation->registered_user_id);
        $this->assertTrue($inviter->isFriendWith($newUser->id));
    }

    public function test_registering_without_token_creates_no_friendship(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $this->post('/register', [
            'first_name' => 'Plain',
            'last_name' => 'Person',
            'email' => 'plain@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseCount('friendships', 0);
    }

    public function test_register_page_shows_inviter_for_valid_token(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $inviter = User::factory()->create();
        $invitation = FriendInvitation::create([
            'inviter_id' => $inviter->id,
            'email' => 'invited@example.com',
            'token' => FriendInvitation::generateToken(),
        ]);

        $response = $this->get('/register?invite='.$invitation->token);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->where('inviteToken', $invitation->token)
            ->where('inviteEmail', 'invited@example.com')
            ->where('inviterName', $inviter->name)
        );
    }

    public function test_user_can_cancel_a_pending_invitation(): void
    {
        $user = User::factory()->create();
        $invitation = FriendInvitation::create([
            'inviter_id' => $user->id,
            'email' => 'friend@example.com',
            'token' => FriendInvitation::generateToken(),
        ]);

        $this->actingAs($user)->delete("/friends/invitations/{$invitation->id}");

        $this->assertDatabaseMissing('friend_invitations', ['id' => $invitation->id]);
    }

    public function test_user_cannot_cancel_someone_elses_invitation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $invitation = FriendInvitation::create([
            'inviter_id' => $other->id,
            'email' => 'friend@example.com',
            'token' => FriendInvitation::generateToken(),
        ]);

        $response = $this->actingAs($user)->delete("/friends/invitations/{$invitation->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('friend_invitations', ['id' => $invitation->id]);
    }
}
