<?php

namespace App\Notifications;

use App\Models\ClientTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ClientTicket $ticket) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    private function resolveTicketUrl($notifiable): string
    {
        if ($notifiable instanceof \App\Models\Admin) {
            return route('admin.tickets.details', $this->ticket->id);
        }

        if ($notifiable instanceof \App\Models\Seller) {
            return route('seller.tickets.details', $this->ticket->id);
        }

        // No client links anymore
        return '#';
    }



    public function toMail($notifiable)
    {
        $order  = $this->ticket->order;
        $client = $order->client;

        $url = $this->resolveTicketUrl($notifiable);

        return (new MailMessage)
            ->subject("New Ticket Created – Order #{$order->id}")
            ->view('emails.tickets.ticket-created', [
                'ticket' => $this->ticket,
                'order'  => $order,
                'client' => $client,
                'url'    => $url,
            ]);
    }
}
