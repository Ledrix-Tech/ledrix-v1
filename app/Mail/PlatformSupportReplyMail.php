<?php

namespace App\Mail;

use App\Models\Central\PlatformSupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformSupportReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PlatformSupportTicket $ticket,
        public string $replyMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: ' . $this->ticket->subject . ' (#' . $this->ticket->id . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.platform-support-reply',
            with: [
                'ticket'  => $this->ticket,
                'message' => $this->replyMessage,
                'url'     => route('tenant.support.show', $this->ticket->id),
            ],
        );
    }
}
