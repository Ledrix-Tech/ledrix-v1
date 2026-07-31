<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentLink;
use App\Support\PpcWebhookVerifier;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentRefundProcessor
{
    public function __construct(
        private PpcWebhookVerifier $webhookVerifier,
        private TenantFeatureService $tenantFeatures,
    ) {}

    public function processStripeRefundEvent(\Stripe\Event $event): void
    {
        if ($event->type === 'charge.refunded') {
            $charge          = $event->data->object;
            $paymentIntentId = $charge->payment_intent ?? null;

            if (! $paymentIntentId) {
                return;
            }

            $payment = Payment::where('provider', 'stripe')
                ->where('provider_payment_intent_id', $paymentIntentId)
                ->first();

            if (! $payment) {
                return;
            }

            $refundAmount = (int) ($charge->amount_refunded ?? 0);
            $this->applyRefund($payment, $refundAmount, 'stripe', $event->toArray());

            return;
        }

        if ($event->type === 'charge.refund.updated') {
            $refund   = $event->data->object;
            $chargeId = $refund->charge ?? null;

            if (! $chargeId) {
                return;
            }

            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            $charge          = \Stripe\Charge::retrieve($chargeId);
            $paymentIntentId = $charge->payment_intent ?? null;

            if (! $paymentIntentId) {
                return;
            }

            $payment = Payment::where('provider', 'stripe')
                ->where('provider_payment_intent_id', $paymentIntentId)
                ->first();

            if (! $payment) {
                return;
            }

            $refundAmount = (int) ($charge->amount_refunded ?? $refund->amount ?? 0);
            $this->applyRefund($payment, $refundAmount, 'stripe', $event->toArray());
        }
    }

    public function processStripeDisputeEvent(\Stripe\Event $event): void
    {
        $dispute  = $event->data->object;
        $chargeId = $dispute->charge ?? null;

        if (! $chargeId) {
            return;
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $charge          = \Stripe\Charge::retrieve($chargeId);
        $paymentIntentId = $charge->payment_intent ?? null;

        if (! $paymentIntentId) {
            return;
        }

        $payment = Payment::where('provider', 'stripe')
            ->where('provider_payment_intent_id', $paymentIntentId)
            ->first();

        if (! $payment) {
            return;
        }

        if (! $this->chargebackTrackingAllowed($payment)) {
            return;
        }

        $amount = (int) ($dispute->amount ?? 0);
        $status = (string) ($dispute->status ?? '');

        if ($event->type === 'charge.dispute.closed') {
            if ($status === 'lost') {
                $this->applyChargebackLost($payment, $amount, 'stripe', $event->toArray(), 'lost');
            } elseif ($status === 'won') {
                $this->markDisputeWon($payment, 'stripe', $event->toArray());
            } else {
                Log::warning('Stripe dispute closed with unexpected status', [
                    'status'     => $status,
                    'payment_id' => $payment->id,
                ]);
            }

            return;
        }

        $stage = $event->type === 'charge.dispute.created' ? 'created' : 'updated';
        $this->markDisputeOpen($payment, $amount, 'stripe', $event->toArray(), $stage);
    }

    /** @deprecated Use processStripeRefundEvent */
    public function processStripeRefund(\Stripe\Event $event): void
    {
        $this->processStripeRefundEvent($event);
    }

    /** @deprecated Use processStripeDisputeEvent */
    public function processStripeChargeback(\Stripe\Event $event): void
    {
        $this->processStripeDisputeEvent($event);
    }

    public function processPaypalRefund(array $webhook): void
    {
        $resource = $webhook['resource'] ?? [];
        $captureId = $this->webhookVerifier->extractPaypalCaptureIdFromRefund($resource);
        $refundValue = $resource['amount']['value'] ?? null;

        if (! $captureId || $refundValue === null) {
            Log::warning('PayPal refund webhook missing capture id or amount', [
                'event_type' => $webhook['event_type'] ?? null,
                'resource'   => $resource,
            ]);

            return;
        }

        $refundCents = (int) round((float) $refundValue * 100);

        $payment = Payment::where('provider', 'paypal')
            ->where('provider_payment_intent_id', $captureId)
            ->first();

        if (! $payment) {
            Log::warning('PayPal refund webhook: payment not found', ['capture_id' => $captureId]);

            return;
        }

        $this->applyRefund($payment, $refundCents, 'paypal', $webhook);
    }

    public function processPaypalDisputeEvent(array $webhook, string $label): void
    {
        $txn = $webhook['resource']['disputed_transactions'][0]['seller_transaction_id'] ?? null;

        if (! $txn) {
            return;
        }

        $payment = Payment::where('provider', 'paypal')
            ->where('provider_payment_intent_id', $txn)
            ->first();

        if (! $payment) {
            return;
        }

        if (! $this->chargebackTrackingAllowed($payment)) {
            return;
        }

        if ($label === 'CUSTOMER.DISPUTE.RESOLVED') {
            $outcome = strtoupper((string) ($webhook['resource']['outcome'] ?? ''));

            if (in_array($outcome, ['RESOLVED_BUYER_FAVOUR', 'RESOLVED_BUYER_FAVOR'], true)) {
                $this->applyChargebackLost(
                    $payment,
                    (int) $payment->amount,
                    'paypal',
                    $webhook,
                    'lost'
                );
            } elseif (in_array($outcome, ['RESOLVED_SELLER_FAVOUR', 'RESOLVED_SELLER_FAVOR'], true)) {
                $this->markDisputeWon($payment, 'paypal', $webhook);
            }

            return;
        }

        $stage = $label === 'CUSTOMER.DISPUTE.CREATED' ? 'created' : 'updated';
        $this->markDisputeOpen($payment, (int) $payment->amount, 'paypal', $webhook, $stage);
    }

    /** @deprecated Use processPaypalDisputeEvent */
    public function processPaypalChargeback(array $webhook): void
    {
        $this->processPaypalDisputeEvent($webhook, 'CUSTOMER.DISPUTE.CREATED');
    }

    private function applyRefund(Payment $payment, int $refundCents, string $provider, array $raw): void
    {
        DB::transaction(function () use ($payment, $refundCents, $raw, $provider) {
            $payment = Payment::lockForUpdate()->find($payment->id);

            if (! $payment) {
                return;
            }

            $order = $payment->order;

            if (! $order) {
                return;
            }

            $delta = max(0, $refundCents - (int) $payment->refunded_amount);

            if ($delta <= 0) {
                return;
            }

            $isFull = $refundCents >= (int) $payment->amount;

            $payment->refunded_amount = $refundCents;
            $payment->refund_status   = $isFull ? 'full' : 'partial';
            $payment->status          = $isFull ? 'refunded' : 'partially_refunded';
            $payment->refund_payload  = $raw;
            $payment->save();

            $order->refunded_amount += $delta;
            $order->refund_status = $order->refunded_amount >= (int) $order->unit_amount ? 'full' : 'partial';

            if ($order->refund_status === 'full') {
                $order->status = 'refunded';
            }

            $order->save();

            PaymentLink::where('order_id', $order->id)
                ->update(['is_active_link' => false]);

            NotifyStakeholders::refund($payment, $order, $provider, null);

            Log::info('Refund applied safely', [
                'payment_id' => $payment->id,
                'order_id'   => $order->id,
                'delta'      => $delta,
                'full'       => $isFull,
            ]);
        });
    }

    private function markDisputeOpen(Payment $payment, int $disputeAmount, string $provider, array $raw, string $stage): void
    {
        DB::transaction(function () use ($payment, $disputeAmount, $provider, $raw, $stage) {
            $payment = Payment::lockForUpdate()->find($payment->id);

            if (! $payment) {
                return;
            }

            $order = $payment->order;

            if (! $order) {
                return;
            }

            if ($payment->refund_status !== 'chargeback') {
                $payment->refund_status  = 'chargeback';
                $payment->refund_payload = $raw;
                $payment->save();
            }

            if ($order->refund_status !== 'chargeback') {
                $order->refund_status = 'chargeback';
                $order->save();
            }

            PaymentLink::where('order_id', $order->id)
                ->update(['is_active_link' => false]);

            NotifyStakeholders::dispute(
                payment: $payment,
                order: $order,
                provider: $provider,
                stage: $stage,
                reason: 'A dispute/chargeback was filed by the customer.'
            );

            Log::warning('Dispute opened/updated', [
                'payment_id' => $payment->id,
                'order_id'   => $order->id,
                'stage'      => $stage,
                'amount'     => $disputeAmount,
            ]);
        });
    }

    private function applyChargebackLost(Payment $payment, int $disputeAmount, string $provider, array $raw, string $stage): void
    {
        DB::transaction(function () use ($payment, $disputeAmount, $provider, $raw, $stage) {
            $payment = Payment::lockForUpdate()->find($payment->id);

            if (! $payment) {
                return;
            }

            $order = $payment->order;

            if (! $order) {
                return;
            }

            $payment->refund_status  = 'chargeback';
            $payment->status           = 'refunded';
            $payment->refund_payload   = $raw;
            $payment->save();

            $order->refund_status = 'chargeback';
            $order->status        = 'refunded';
            $order->save();

            PaymentLink::where('order_id', $order->id)
                ->update(['is_active_link' => false]);

            NotifyStakeholders::dispute(
                payment: $payment,
                order: $order,
                provider: $provider,
                stage: $stage,
                reason: 'The dispute was resolved against the merchant.'
            );

            Log::warning('Chargeback applied', [
                'payment_id' => $payment->id,
                'order_id'   => $order->id,
                'stage'      => $stage,
                'amount'     => $disputeAmount,
            ]);
        });
    }

    private function markDisputeWon(Payment $payment, string $provider, array $raw): void
    {
        DB::transaction(function () use ($payment, $provider, $raw) {
            $payment = Payment::lockForUpdate()->find($payment->id);

            if (! $payment) {
                return;
            }

            $order = $payment->order;

            $payment->refund_payload = $raw;

            if ($payment->refund_status === 'chargeback' && $payment->status === 'succeeded') {
                $payment->refund_status = 'none';
            }

            $payment->save();

            if ($order && $order->refund_status === 'chargeback') {
                $order->refund_status = 'none';
                $order->save();
            }

            NotifyStakeholders::dispute(
                payment: $payment,
                order: $order,
                provider: $provider,
                stage: 'won',
                reason: 'The dispute was resolved in your favor.'
            );

            Log::info('Dispute won', ['payment_id' => $payment->id]);
        });
    }

    private function chargebackTrackingAllowed(Payment $payment): bool
    {
        $tenantId = (int) ($payment->tenant_id ?? 0);

        if (! $tenantId) {
            return true;
        }

        if ($this->tenantFeatures->enabled('chargeback_tracking', $tenantId)) {
            return true;
        }

        Log::info('Chargeback/dispute ignored — plan excludes chargeback tracking', [
            'payment_id' => $payment->id,
            'tenant_id'  => $tenantId,
        ]);

        return false;
    }
}
