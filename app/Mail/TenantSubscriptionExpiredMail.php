<?php

namespace App\Mail;

use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantSubscriptionExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public TenantMembership $membership,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action required — your Ledrix subscription has expired',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-subscription-expired',
        );
    }
}
