<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class LeadViewedSlackApiNotification extends Notification
{
    use Queueable, Notifiable;

    public $lead, $seller;

    public function __construct($lead, $seller)
    {
        $this->lead = $lead;
        $this->seller = $seller;
    }

    public function via($notifiable)
    {
        return [];  // no built-in slack channel
    }

    public function sendSlack()
    {
        $channel = 'CABC12345'; // or user id
        $text = "<@UUserID> Lead #{$this->lead->id} viewed by seller {$this->seller->name}";
        slackPostMessage($channel, $text, [
            // blocks or attachments if you want
        ]);
    }
}
