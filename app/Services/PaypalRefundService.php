<?php

namespace App\Services;

use App\Services\PaymentRefundProcessor;
use Illuminate\Support\Facades\Log;

class PaypalRefundService
{
    public function __construct(
        protected PaymentRefundProcessor $processor
    ) {}

    /**
     * Handles:
     * - PAYMENT.CAPTURE.REFUNDED
     * - PAYMENT.SALE.REFUNDED
     */
    public function refunded(array $webhook): void
    {
        try {
            $eventType = $webhook['event_type'] ?? 'unknown';

            Log::info("PaypalRefundService handling {$eventType}", [
                'resource_id' => $webhook['resource']['id'] ?? null,
            ]);

            $this->processor->processPaypalRefund($webhook);
        } catch (\Throwable $e) {
            Log::error('PaypalRefundService failed', [
                'error'   => $e->getMessage(),
                'payload' => $webhook,
            ]);
        }
    }
}
