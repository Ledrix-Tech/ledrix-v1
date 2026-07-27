<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RenewalOrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $client = $this->order->client;
        $service = $this->order->service_name;
        $brand = $this->order->brand->brand_name ?? 'Our Team';

        return (new MailMessage)
            ->subject("Your Renewal Order is Ready – {$service}")
            ->view('emails.renewal-order', [
                'client' => $client,
                'order'  => $this->order,
                'brand'  => $brand,
                'service' => $service,
            ]);
    }
}
