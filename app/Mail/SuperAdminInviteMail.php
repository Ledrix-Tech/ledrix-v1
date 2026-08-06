<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuperAdminInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $token,
        public string $name,
        public string $role,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You are invited to Ledrix Super Admin',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.super-admin-invite',
            with: [
                'name' => $this->name,
                'role' => $this->role,
                'url'  => route('super-admin.invite.accept', ['token' => $this->token]),
            ],
        );
    }
}
