<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendBriefLinkMail extends Notification
{
    use Queueable;

    public $client;
    public $order;
    public $brand;
    public $briefUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct($client, $order, $brand, $briefUrl)
    {
        $this->client   = $client;
        $this->order    = $order;
        $this->brand    = $brand;
        $this->briefUrl = $briefUrl;
    }

    /**
     * Notification channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail message using custom view.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Brief Required for Your Order #{$this->order->id}")
            ->view('emails.brief-link', [
                'client'   => $this->client,
                'order'    => $this->order,
                'brand'    => $this->brand,
                'briefUrl' => $this->briefUrl,
            ]);
    }
}
