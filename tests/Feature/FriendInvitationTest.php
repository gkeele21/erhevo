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
            'email' => 'newfriend@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('friend_invitations', [
            'inviter_id' => $user->id,
            'email' => 'newfriend@example.com',
            'accepted_at' => null,
        ]);
        Mail::assertSent(FriendInvitationMail::class, fn ($mail) => $mail->hasTo('newfriend@example.com'));
    }

    public function test_inviting_an_existing_member_sends_a_friend_request_instead(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $member = User::factory()->create(['email' => 'member@example.com']);

        $this->actingAs($user)->post('/friends/invite', [
            'email' => 'member@example.com',
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

        $this->actingAs($user)->post('/friends/invite', ['email' => 'friend@example.com']);
        $this->actingAs($user)->post('/friends/invite', ['email' => 'friend@example.com']);

        $this->assertSame(1, FriendInvitation::where('email', 'friend@example.com')->count());
        Mail::assertSentCount(1);
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
