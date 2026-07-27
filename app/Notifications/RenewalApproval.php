<?php

namespace App\Notifications;

use App\Models\Central\Tenant;
use App\Models\Central\TenantRenewalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RenewalApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public TenantRenewalRequest $renewalRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Approve your Ledrix subscription renewal')
            ->view('emails.renewal-approval', [
                'tenant' => $this->tenant,
                'renewalRequest' => $this->renewalRequest,
            ]);
    }
}
