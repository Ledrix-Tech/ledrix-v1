<?php

namespace App\Notifications;

use App\Models\PaymentLink;
use App\Models\Upwork\UpworkPaymentLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;      // remove this if you don't want to queue
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentLinkNotification extends Notification implements ShouldQueue
{

    use Queueable;

    public function __construct(
        public PaymentLink|UpworkPaymentLink $link,
        public string $url,
        public string $module = 'ppc' // 'ppc' or 'upwork'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format(((int)$this->link->unit_amount) / 100, 2) . ' ' . $this->link->currency;

        $brandName = $this->link->brand->brand_name ?? config('app.name', 'Ledrix');

        // recipient name (lead/client)
        $recipientName = $this->module === 'upwork'
            ? ($this->link->client->name ?? 'Customer')
            : ($this->link->lead->name ?? 'Customer');

        return (new MailMessage)
            ->subject('Your Secure Payment Link')
            ->view('emails.payment_link_created', [
                'module'    => $this->module,
                'brandName' => $brandName,
                'recipient' => $recipientName,
                'service'   => $this->link->service_name,
                'amount'    => $amount,
                'url'       => $this->url,
                'expiresAt' => $this->link->expires_at,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'module' => $this->module,
            'payment_link_id' => $this->link->id,
            'url' => $this->url,
        ];
    }
}
// class PaymentLinkNotification extends Notification implements ShouldQueue
// {
//     use Queueable;

//     public function __construct(
//         public PaymentLink $link,
//         public string $url
//     ) {}

//     public function via(object $notifiable): array
//     {
//         return ['mail'];
//     }

//     public function toMail(object $notifiable): MailMessage
//     {
//         $amount = number_format($this->link->unit_amount / 100, 2) . ' ' . $this->link->currency;

//         return (new MailMessage)
//             ->subject('Your Secure Payment Link')
//             ->markdown('emails.payment_link_created', [
//                 'brandName' => $this->link->brand->brand_name ?? 'The DPM Team',
//                 'service'   => $this->link->service_name,
//                 'amount'    => $amount,
//                 'url'       => $this->url,
//                 'expiresAt' => $this->link->expires_at,
//                 'lead'      => $this->link->lead,
//             ]);
//     }

//     // public function toMail(object $notifiable): MailMessage
//     // {
//     //     $amount = number_format($this->link->unit_amount / 100, 2) . ' ' . $this->link->currency;

//     //     return (new MailMessage)
//     //         ->subject('Your secure payment link')
//     //         ->greeting('Hi!')
//     //         ->line("Service: {$this->link->service_name}")
//     //         ->line("Amount: {$amount}")
//     //         ->when(
//     //             $this->link->expires_at,
//     //             fn($msg) =>
//     //             $msg->line('Expires: ' . $this->link->expires_at->toDayDateTimeString())
//     //         )
//     //         ->action('Pay Now', $this->url)
//     //         ->line('If the button does not work, copy and paste this link into your browser:')
//     //         ->line($this->url);
//     // }

//     // Optional: for database channel etc.
//     public function toArray(object $notifiable): array
//     {
//         return [
//             'payment_link_id' => $this->link->id,
//             'url'             => $this->url,
//         ];
//     }
// }
