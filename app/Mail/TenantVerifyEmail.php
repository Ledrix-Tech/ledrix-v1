<?php

namespace App\Mail;

use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantVerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $verifyUrl,
        public ?PackagePricing $plan = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your Ledrix account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-verify-email',
        );
    }
}
