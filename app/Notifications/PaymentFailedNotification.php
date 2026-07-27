<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $order,
        public string $provider,
        public ?string $reason = null,
        public ?string $retryUrl = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $order = $this->order;
        $client = $order->client;

        return (new MailMessage)
            ->subject("Payment Failed – {$order->service_name}")
            ->view('emails.payment-failed', [
                'clientName' => $client->name ?? $order->buyer_name ?? 'Valued Customer',
                'service'    => $order->service_name,
                'brandName'  => $order->brand->brand_name ?? 'Our Team',
                'amount'     => number_format($order->balance_due / 100, 2) . ' ' . $order->currency,
                'provider'   => $this->provider,
                'reason'     => $this->reason,
                'retryUrl'   => $this->retryUrl,
            ]);
    }
}
