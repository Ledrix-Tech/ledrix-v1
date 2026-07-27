<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Log;

class PaymentEventRouter
{
    protected PaymentRefundProcessor $refundProcessor;

    public function __construct(PaymentRefundProcessor $refundProcessor)
    {
        $this->refundProcessor = $refundProcessor;
    }


    /* =====================
     *   STRIPE EVENTS
     * ===================== */
    public function handleStripeEvent(\Stripe\Event $event, array $payload): void
    {
        $type = $event->type;

        switch ($type) {

            /* --- Successful payments --- */
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                // DO NOTHING HERE — your StripeGateway already captures payment
                Log::info("Stripe success event handled externally");
                break;


            /* --- Refunds --- */
            case 'charge.refunded':
            case 'charge.refund.updated':
                $this->refundProcessor->processStripeRefund($event);
                break;

            /* --- Dispute / Chargeback --- */
            case 'charge.dispute.created':
            case 'charge.dispute.funds_withdrawn':
            case 'charge.dispute.closed':
                $this->refundProcessor->processStripeChargeback($event);
                break;


            default:
                Log::info("Stripe event ignored", ['type' => $type]);
        }
    }



    /* =====================
     *   PAYPAL EVENTS
     * ===================== */
    public function handlePaypalEvent(array $webhook): void
    {
        $eventType = $webhook['event_type'] ?? null;

        switch ($eventType) {

            /* --- Successful capture --- */
            case 'PAYMENT.CAPTURE.COMPLETED':
                // Already handled by your PayPal gateway code
                break;

            /* --- Refunds --- */
            case 'PAYMENT.CAPTURE.REFUNDED':
            case 'PAYMENT.CAPTURE.PARTIALLY_REFUNDED':
                $this->refundProcessor->processPaypalRefund($webhook);
                break;

            /* --- Chargeback --- */
            case 'CUSTOMER.DISPUTE.CREATED':
            case 'CUSTOMER.DISPUTE.UPDATED':
            case 'CUSTOMER.DISPUTE.RESOLVED':
                $this->refundProcessor->processPaypalChargeback($webhook);
                break;

            default:
                Log::info("Paypal event ignored", ['type' => $eventType]);
        }
    }
}
