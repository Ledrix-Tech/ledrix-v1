<?php

namespace App\Mail;

use App\Models\PaymentLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;

class PaymentLinkCreated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    use Queueable, SerializesModels;

    public PaymentLink $link;
    public string $url;

    public function __construct(PaymentLink $link, string $url)
    {
        $this->link = $link;
        $this->url  = $url;
    }

    public function build()
    {
        $amount = number_format($this->link->unit_amount / 100, 2) . ' ' . $this->link->currency;

        return $this->subject('Your secure payment link')
            ->view('emails.payment_link_created', [
                'brandName' => $this->link->brand->brand_name ?? config('app.name', 'Ledrix'),
                'service'   => $this->link->service_name,
                'amount'    => $amount,
                'url'       => $this->url,
                'expiresAt' => $this->link->expires_at,
                'recipient' => $this->link->lead->name ?? 'Valued Customer',
            ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Link Created',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment_link_created',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
