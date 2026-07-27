<?php

namespace App\Http\Controllers\Upwork;

use App\Http\Controllers\Controller;
use App\Services\Upwork\UpworkPaymentDispute;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class DisputeController extends Controller
{
    public function stripeRefundHandle(Request $request, UpworkPaymentDispute $service)
    {
        $event = $this->verifyStripeEvent($request);

        // Map refund events only
        if (!in_array($event->type, ['charge.refunded', 'charge.refund.updated'], true)) {
            return response()->json(['status' => 'ignored'], Response::HTTP_OK);
        }

        $service->handleRefundEvent($event);

        return response()->json(['status' => 'processed'], Response::HTTP_OK);
    }

    public function stripeDisputeHandle(Request $request, UpworkPaymentDispute $service)
    {
        $event = $this->verifyStripeEvent($request);

        // Map dispute events only
        if (!in_array($event->type, [
            'charge.dispute.created',
            'charge.dispute.updated',
            'charge.dispute.closed',
        ], true)) {
            return response()->json(['status' => 'ignored'], Response::HTTP_OK);
        }

        $service->handleDisputeEvent($event);

        return response()->json(['status' => 'processed'], Response::HTTP_OK);
    }

    private function verifyStripeEvent(Request $request): \Stripe\Event
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');

        if (!$sig) {
            Log::error('Stripe webhook missing signature header');
            abort(400, 'Missing Stripe-Signature header');
        }

        $secret = config('services.stripe.webhook_secret');
        if (!$secret) {
            Log::error('Stripe webhook secret missing (services.stripe.webhook_secret)');
            abort(500, 'Webhook secret missing');
        }

        try {
            return Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            abort(400, 'Invalid signature');
        }
    }
}
