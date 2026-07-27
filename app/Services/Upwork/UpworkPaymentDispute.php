<?php

namespace App\Services\Upwork;

use App\Services\Upwork\UpworkPaymentRefundProcessor;
use Illuminate\Support\Facades\Log;

class UpworkPaymentDispute
{
    public function __construct(
        protected UpworkPaymentRefundProcessor $processor
    ) {}

    public function handleRefundEvent(\Stripe\Event $event): void
    {
        Log::info('Upwork refund webhook', [
            'event_id' => $event->id ?? null,
            'type'     => $event->type ?? null,
        ]);

        $this->processor->processStripeRefundEvent($event);
    }

    public function handleDisputeEvent(\Stripe\Event $event): void
    {
        Log::info('Upwork dispute webhook', [
            'event_id' => $event->id ?? null,
            'type'     => $event->type ?? null,
        ]);

        $this->processor->processStripeDisputeEvent($event);
    }
}
