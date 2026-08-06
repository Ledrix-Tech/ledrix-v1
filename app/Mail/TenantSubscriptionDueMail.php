<?php

namespace App\Mail;

use App\Models\Central\Tenant;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantSubscriptionDueMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public TenantPayment $payment,
        public TenantInvoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ledrix subscription payment due',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-subscription-due',
        );
    }
}
