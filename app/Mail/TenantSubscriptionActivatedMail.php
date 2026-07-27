<?php

namespace App\Mail;

use App\Models\Central\Tenant;
use App\Models\Central\TenantPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantSubscriptionActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public TenantPayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Ledrix subscription is now active',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-subscription-activated',
        );
    }
}
