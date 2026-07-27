<?php

namespace App\Notifications;

use App\Models\Central\Tenant;
use App\Models\Central\TenantPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipInvoice extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public TenantPayment $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Ledrix payment receipt')
            ->view('emails.membership-invoice', [
                'tenant' => $this->tenant,
                'payment' => $this->payment,
            ]);
    }
}
