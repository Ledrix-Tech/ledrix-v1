<?php

namespace App\Notifications;

use App\Models\ClientTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TicketDeadlineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClientTicket $ticket,
        public string $stage // 'upcoming', 'today', 'overdue'
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $order = $this->ticket->order;
        $client = $order->client;

        $subjectStage = match ($this->stage) {
            'upcoming' => 'Upcoming Ticket Deadline',
            'today'    => 'Ticket Deadline Today',
            'overdue'  => 'Ticket Overdue!',
            default    => 'Ticket Deadline Alert',
        };

        return (new MailMessage)
            ->subject("{$subjectStage} – Order #{$order->id}")
            ->view('emails.tickets.ticket-deadline', [
                'ticket' => $this->ticket,
                'order'  => $order,
                'client' => $client,
                'stage'  => $this->stage,
            ]);
    }
}
