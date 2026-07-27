<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;

class NotifyAdminOfLeadView implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $seller, $lead;

    public function __construct($seller, $lead)
    {
        $this->seller = $seller;
        $this->lead = $lead;
    }


    public function handle()
    {
        $channel = 'U097ZV9KZM5'; // Replace with actual Slack user/channel ID
        $text = "<@{$channel}> Lead #{$this->lead->id} was viewed by seller {$this->seller->name}";

        slackPostMessage($channel, $text, [
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "*Lead Details*",
                ],
            ],
            [
                "type" => "section",
                "fields" => [
                    [
                        "type" => "mrkdwn",
                        "text" => "*Email:*\n{$this->lead->email}"
                    ],
                    [
                        "type" => "mrkdwn",
                        "text" => "*Phone:*\n{$this->lead->phone}"
                    ],
                ],
            ],
        ]);
    }
}
