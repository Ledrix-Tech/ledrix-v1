<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

function slackPostMessage(string $channel, string $text, array $blocks): array
{
    $token = env('SLACK_BOT_TOKEN'); // Must be set in your .env file
    $endpoint = 'https://slack.com/api/chat.postMessage';

    $payload = [
        'channel' => $channel,
        'text'    => $text,
        'link_names' => true,
    ];

    if ($blocks !== null) {
        $payload['blocks'] = $blocks;
    }

    $response = Http::withToken($token)->post($endpoint, $payload);
    $data = $response->json();

    if (!($data['ok'] ?? false)) {
        Log::error('Slack API error', ['response' => $data]);
    }

    return $data;
}
