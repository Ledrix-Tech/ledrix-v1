<?php

namespace App\Http\Controllers\Compliances;

use App\Http\Controllers\Controller;
use App\Services\PaypalDisputeService;
use App\Services\PaypalRefundService;
use App\Services\StripeDisputeService;
use App\Services\StripeRefundService;
use App\Support\PpcWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class RefundWebhookController extends Controller
{
    public function stripeDisputeHandle(
        Request $request,
        PpcWebhookVerifier $verifier,
        StripeDisputeService $service,
    ) {
        $event = $verifier->verifyStripeEvent($request);

        if (PpcWebhookVerifier::isDuplicate('stripe-dispute', $event->id)) {
            return response()->json(['status' => 'duplicate'], Response::HTTP_OK);
        }

        try {
            match ($event->type) {
                'charge.dispute.created' => $service->created($event),
                'charge.dispute.updated' => $service->updated($event),
                'charge.dispute.closed'  => $service->closed($event),
                default                  => null,
            };
        } catch (\Throwable $e) {
            PpcWebhookVerifier::releaseDuplicateLock('stripe-dispute', $event->id);
            Log::error('Stripe dispute webhook processing failed', [
                'event_id' => $event->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['status' => 'processed'], Response::HTTP_OK);
    }

    public function stripeRefundHandle(
        Request $request,
        PpcWebhookVerifier $verifier,
        StripeRefundService $service,
    ) {
        $event = $verifier->verifyStripeEvent($request);

        if (PpcWebhookVerifier::isDuplicate('stripe-refund', $event->id)) {
            return response()->json(['status' => 'duplicate'], Response::HTTP_OK);
        }

        try {
            match ($event->type) {
                'charge.refunded'       => $service->refunded($event),
                'charge.refund.updated' => $service->updated($event),
                default                 => null,
            };
        } catch (\Throwable $e) {
            PpcWebhookVerifier::releaseDuplicateLock('stripe-refund', $event->id);
            Log::error('Stripe refund webhook processing failed', [
                'event_id' => $event->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['status' => 'processed'], Response::HTTP_OK);
    }

    public function paypalDisputeHandle(
        Request $request,
        PpcWebhookVerifier $verifier,
        PaypalDisputeService $service,
    ) {
        $webhook = $verifier->verifyPayPalEvent($request);
        $eventId = (string) ($webhook['id'] ?? '');

        if ($eventId !== '' && PpcWebhookVerifier::isDuplicate('paypal-dispute', $eventId)) {
            return response()->json(['status' => 'duplicate'], Response::HTTP_OK);
        }

        try {
            match ($webhook['event_type'] ?? null) {
                'CUSTOMER.DISPUTE.CREATED'  => $service->created($webhook),
                'CUSTOMER.DISPUTE.UPDATED'  => $service->updated($webhook),
                'CUSTOMER.DISPUTE.RESOLVED' => $service->closed($webhook),
                default                     => null,
            };
        } catch (\Throwable $e) {
            if ($eventId !== '') {
                PpcWebhookVerifier::releaseDuplicateLock('paypal-dispute', $eventId);
            }
            Log::error('PayPal dispute webhook processing failed', [
                'event_id' => $eventId,
                'error'    => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['status' => 'processed'], Response::HTTP_OK);
    }

    public function paypalRefundHandle(
        Request $request,
        PpcWebhookVerifier $verifier,
        PaypalRefundService $service,
    ) {
        $webhook = $verifier->verifyPayPalEvent($request);
        $eventId = (string) ($webhook['id'] ?? '');

        if ($eventId !== '' && PpcWebhookVerifier::isDuplicate('paypal-refund', $eventId)) {
            return response()->json(['status' => 'duplicate'], Response::HTTP_OK);
        }

        try {
            match ($webhook['event_type'] ?? null) {
                'PAYMENT.CAPTURE.REFUNDED', 'PAYMENT.SALE.REFUNDED' => $service->refunded($webhook),
                default => null,
            };
        } catch (\Throwable $e) {
            if ($eventId !== '') {
                PpcWebhookVerifier::releaseDuplicateLock('paypal-refund', $eventId);
            }
            Log::error('PayPal refund webhook processing failed', [
                'event_id' => $eventId,
                'error'    => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['status' => 'processed'], Response::HTTP_OK);
    }
}
