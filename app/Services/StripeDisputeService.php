<?php

namespace App\Services;

use App\Services\PaymentRefundProcessor;
use Illuminate\Support\Facades\Log;
use Stripe\Event;

class StripeDisputeService
{
    public function __construct(
        protected PaymentRefundProcessor $processor
    ) {}

    public function created(Event $event): void
    {
        $this->handle($event, 'charge.dispute.created');
    }

    public function updated(Event $event): void
    {
        $this->handle($event, 'charge.dispute.updated');
    }

    public function closed(Event $event): void
    {
        $this->handle($event, 'charge.dispute.closed');
    }

    protected function handle(Event $event, string $label): void
    {
        try {
            Log::info("StripeDisputeService handling {$label}", [
                'event_id' => $event->id ?? null,
                'type'     => $event->type ?? null,
            ]);

            $this->processor->processStripeDisputeEvent($event);
        } catch (\Throwable $e) {
            Log::error("StripeDisputeService {$label} failed", [
                'error'    => $e->getMessage(),
                'event_id' => $event->id ?? null,
            ]);

            throw $e;
        }
    }
}
