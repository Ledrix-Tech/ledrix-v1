<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class LeadViewedNotification extends Notification
{
    use Queueable, Notifiable;

    public $lead;
    public $seller;

    public function __construct($lead, $seller)
    {
        $this->lead = $lead;
        $this->seller = $seller;
    }

    public function via($notifiable)
    {
        return ['slack'];
    }

    public function toSlack($notifiable)
    {
        return (new SlackMessage)
            ->linkNames()            // ensure mentions are parsed
            ->content("<@U097ZV9KZM5> Lead #{$this->lead->id} viewed by seller {$this->seller->name}")
            ->attachment(function ($attachment) {
                $attachment->fields([
                    'Lead Email' => $this->lead->email,
                    'Lead Phone' => $this->lead->phone,
                ]);
            });
    }


}
