<?php

namespace App\Services;

use App\Services\PaymentRefundProcessor;
use Illuminate\Support\Facades\Log;
use Stripe\Event;

class StripeRefundService
{
    public function __construct(
        protected PaymentRefundProcessor $processor
    ) {}

    public function refunded(Event $event): void
    {
        $this->handle($event, 'charge.refunded');
    }

    public function updated(Event $event): void
    {
        $this->handle($event, 'charge.refund.updated');
    }

    protected function handle(Event $event, string $label): void
    {
        try {
            Log::info("StripeRefundService handling {$label}", [
                'event_id' => $event->id ?? null,
                'type'     => $event->type ?? null,
            ]);

            $this->processor->processStripeRefundEvent($event);
        } catch (\Throwable $e) {
            Log::error("StripeRefundService {$label} failed", [
                'error'    => $e->getMessage(),
                'event_id' => $event->id ?? null,
            ]);

            throw $e;
        }
    }
}
