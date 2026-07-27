<?php

namespace App\Mail;

use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantTrialEndingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public TenantMembership $membership,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Ledrix trial is ending soon',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-trial-ending',
        );
    }
}
