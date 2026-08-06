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

class TenantSubscriptionActivatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public TenantPayment $payment,
        public ?TenantInvoice $invoice = null,
    ) {
        $this->invoice = $invoice ?? $payment->invoice;
    }

    public function envelope(): Envelope
    {
        $number = $this->invoice?->invoice_number;

        return new Envelope(
            subject: $number
                ? "Payment received — Invoice {$number}"
                : 'Your Ledrix subscription is now active',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-subscription-activated',
            with: [
                'tenant'      => $this->tenant,
                'payment'     => $this->payment,
                'invoice'     => $this->invoice,
                'invoiceUrl'  => $this->invoice
                    ? route('tenant.billing.invoice.show', $this->invoice->id)
                    : route('tenant.billing'),
                'billingUrl'  => route('tenant.billing'),
            ],
        );
    }
}
