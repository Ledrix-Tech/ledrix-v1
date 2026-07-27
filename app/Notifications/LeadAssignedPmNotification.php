<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\Seller;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeadAssignedPmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public Seller $pm,
        public $assigner   // ← NO type hint
    ) {}


    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Lead Assigned To You')
            ->view('emails.lead-assigned-pm', [
                'lead'     => $this->lead,
                'pm'       => $this->pm,
                'assigner' => $this->assigner,
            ]);
    }
}
