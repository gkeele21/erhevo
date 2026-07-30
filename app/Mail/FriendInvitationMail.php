<?php

namespace App\Mail;

use App\Models\FriendInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FriendInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FriendInvitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->invitation->inviter->name.' invited you to join '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.friend-invitation',
            with: [
                'inviterName' => $this->invitation->inviter->name,
                'registerUrl' => route('friends.invite-link', $this->invitation->token),
            ],
        );
    }
}
