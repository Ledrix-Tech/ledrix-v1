<?php

namespace App\Services;

use App\Services\PaymentRefundProcessor;
use Illuminate\Support\Facades\Log;

class PaypalDisputeService
{
    public function __construct(
        protected PaymentRefundProcessor $processor
    ) {}

    public function created(array $webhook): void
    {
        $this->handle($webhook, 'CUSTOMER.DISPUTE.CREATED');
    }

    public function updated(array $webhook): void
    {
        $this->handle($webhook, 'CUSTOMER.DISPUTE.UPDATED');
    }

    public function closed(array $webhook): void
    {
        $this->handle($webhook, 'CUSTOMER.DISPUTE.RESOLVED');
    }

    protected function handle(array $webhook, string $label): void
    {
        try {
            Log::info("PaypalDisputeService handling {$label}", [
                'event_type' => $webhook['event_type'] ?? null,
                'dispute_id' => $webhook['resource']['dispute_id'] ?? null,
            ]);

            $this->processor->processPaypalDisputeEvent($webhook, $label);
        } catch (\Throwable $e) {
            Log::error("PaypalDisputeService {$label} failed", [
                'error'   => $e->getMessage(),
                'payload' => $webhook,
            ]);

            throw $e;
        }
    }
}
