<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InitialPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public mixed $payment, // PPCPayment|UpworkPayment
        public mixed $order,   // PPCOrder|UpworkOrder
        public mixed $client,  // Client|UpworkClient|Lead
        public string $module = 'upwork' // 'ppc' or 'upwork'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amountCents = (int) ($this->payment->amount ?? 0);

        // PPC might store dollars, Upwork stores cents — adjust if needed:
        // If your PPC payment.amount is already cents, remove this.
        $amount = number_format($amountCents / 100, 2);

        $currency = $this->payment->currency
            ?? $this->order->currency
            ?? 'USD';

        $service = $this->order->service_name
            ?? $this->order->service
            ?? 'N/A';

        $clientName = $this->client->name
            ?? $this->client->client_name
            ?? 'Valued Customer';

        return (new MailMessage)
            ->subject('Your Payment Was Successful')
            ->view('emails.initial-payment', [
                'payment'    => $this->payment,
                'order'      => $this->order,
                'client'     => $this->client,
                'amount'     => $amount . ' ' . $currency,
                'service'    => $service,
                'clientName' => $clientName,
                'module'     => $this->module,
            ]);
    }
}
// class InitialPaymentNotification extends Notification implements ShouldQueue
// {
//     use Queueable;

//     public function __construct(
//         public $payment,  // Accept both PPC and Upwork Payment models
//         public $order,    // Accept both PPC and Upwork Order models
//         public $client    // Accept both PPC and Upwork Client models
//     ) {}

//     public function via(object $notifiable): array
//     {
//         return ['mail']; // Email notification channel
//     }

//     public function toMail(object $notifiable): MailMessage
//     {
//         // Optional check for PPC and Upwork models - ensures compatibility for both modules
//         $paymentAmount = optional($this->payment)->amount ?? 0;
//         $orderService = optional($this->order)->service_name ?? 'N/A';
//         $clientName = optional($this->client)->name ?? 'N/A';

//         return (new MailMessage)
//             ->subject('Your Payment Was Successful')
//             ->view('emails.initial-payment', [
//                 'payment' => $this->payment,
//                 'order'   => $this->order,
//                 'client'  => $this->client,
//                 'amount'  => $paymentAmount,
//                 'service' => $orderService,
//                 'clientName' => $clientName
//             ]);
//     }
// }
