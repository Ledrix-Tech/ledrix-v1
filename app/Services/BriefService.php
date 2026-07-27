<?php

namespace App\Services;

use App\Jobs\SendBriefLinkJob;
use App\Models\Order;
use App\Models\Questionnair;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BriefService
{
    public function ensureBriefForOrder(Order $order): Questionnair
    {
        $brief = Questionnair::firstOrCreate(
            ['order_id' => $order->id],
            [
                'client_id' => $order->client_id,
                'service_name' => $order->service_name ?? 'Unknown Service',
                'meta' => [],
                'status' => 'pending',
            ]
        );

        if (! $brief->brief_token || ($brief->brief_token_expires_at && $brief->brief_token_expires_at->isPast())) {
            $brief->brief_token = (string) Str::uuid();
            $brief->brief_token_expires_at = now()->addDays(14);
            $brief->save();
        }

        return $brief;
    }

    public function publicBriefUrl(Questionnair $brief): string
    {
        return route('brief.show', ['token' => $brief->brief_token]);
    }

    public function clientPortalBriefUrl(): string
    {
        return route('client.brief.get');
    }

    public function dispatchBriefEmail(int $orderId): void
    {
        SendBriefLinkJob::dispatch($orderId);
    }

    public function sendBriefLinkIfNeeded(int $orderId): void
    {
        $order = Order::with(['client', 'brand'])->find($orderId);
        if (!$order || !$order->client) return;

        // send only for originals (or include renewals if you want)
        if ($order->order_type !== 'original') return;

        // prevent duplicates
        // if ($order->brief_sent_at) return;

        $brief = $this->ensureBriefForOrder($order);

        $briefUrl  = $this->publicBriefUrl($brief);
        $brandName = $order->brand->brand_name ?? config('app.name');

        try {
            $order->client->notify(new \App\Notifications\SendBriefLinkMail(
                $order->client,
                $order,
                $brandName,
                $briefUrl
            ));

            // $order->brief_sent_at = now();
            $order->save();
        } catch (\Throwable $e) {
            Log::error('Failed to send brief link mail', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
