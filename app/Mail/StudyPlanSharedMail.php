<?php

namespace App\Mail;

use App\Models\StudyPlan;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudyPlanSharedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StudyPlan $plan,
        public User $member,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->plan->user->name.' wants to study with you',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.study-plan-shared',
            with: [
                'ownerName' => $this->plan->user->name,
                'planName' => $this->plan->name,
                'planSummary' => $this->plan->criteria_summary,
                'planUrl' => route('study-plans.show', $this->plan),
            ],
        );
    }
}
