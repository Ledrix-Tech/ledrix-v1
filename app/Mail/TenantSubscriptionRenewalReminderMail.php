<?php

namespace App\Mail;

use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantSubscriptionRenewalReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public TenantMembership $membership,
        public int $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->daysLeft <= 1
            ? 'Your Ledrix subscription renews tomorrow'
            : "Your Ledrix subscription renews in {$this->daysLeft} days";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-subscription-renewal-reminder',
        );
    }
}
