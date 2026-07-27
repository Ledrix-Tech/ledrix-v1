<?php

namespace App\Mail;

use App\Models\Central\TenantPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantBankTransferReportedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TenantPayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bank transfer reported — verify in super admin',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-bank-transfer-reported',
        );
    }
}
