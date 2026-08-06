<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformOpsAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $alertType,
        public string $headline,
        public array $context = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Ledrix] ' . $this->headline,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.platform-ops-alert',
            with: [
                'alertType' => $this->alertType,
                'headline'  => $this->headline,
                'context'   => $this->context,
                'url'       => $this->context['url'] ?? route('super-admin.index.get'),
            ],
        );
    }
}
