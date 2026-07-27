<?php

namespace App\Services\Upwork;

use App\Models\Upwork\UpworkOrder;
use App\Models\Upwork\UpworkPayment;
use App\Models\Upwork\UpworkPaymentLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpworkPaymentRefundProcessor
{
    /**
     * Refund events:
     * - charge.refunded (object = Charge)
     * - charge.refund.updated (object = Refund)
     */
    public function processStripeRefundEvent(\Stripe\Event $event): void
    {
        try {
            if ($event->type === 'charge.refunded') {
                $charge = $event->data->object; // Charge
                $paymentIntentId = $charge->payment_intent ?? null;
                $refundCents     = (int) ($charge->amount_refunded ?? 0);

                if (!$paymentIntentId || $refundCents <= 0) return;

                $payment = UpworkPayment::where('provider', 'stripe')
                    ->where('provider_payment_intent_id', $paymentIntentId)
                    ->first();

                if (!$payment) return;

                $this->applyRefund($payment->id, $refundCents, 'stripe', $event->toArray());
                return;
            }

            if ($event->type === 'charge.refund.updated') {
                $refund = $event->data->object; // Refund
                $chargeId = $refund->charge ?? null;
                $refundCents = (int) ($refund->amount ?? 0);

                if (!$chargeId || $refundCents <= 0) return;

                // Retrieve charge to get payment_intent
                $charge = \Stripe\Charge::retrieve($chargeId);
                $paymentIntentId = $charge->payment_intent ?? null;

                if (!$paymentIntentId) return;

                $payment = UpworkPayment::where('provider', 'stripe')
                    ->where('provider_payment_intent_id', $paymentIntentId)
                    ->first();

                if (!$payment) return;

                // refund.updated is per-refund; you likely want total refunded on payment.
                // We'll use Charge amount_refunded as source of truth:
                $totalRefunded = (int) ($charge->amount_refunded ?? $refundCents);

                $this->applyRefund($payment->id, $totalRefunded, 'stripe', $event->toArray());
                return;
            }
        } catch (\Throwable $e) {
            Log::error('processStripeRefundEvent failed', [
                'error' => $e->getMessage(),
                'type'  => $event->type ?? null,
            ]);
        }
    }

    /**
     * Dispute events:
     * - charge.dispute.created/updated/closed (object = Dispute)
     */
    public function processStripeDisputeEvent(\Stripe\Event $event): void
    {
        try {
            $dispute = $event->data->object; // Dispute
            $chargeId = $dispute->charge ?? null;
            if (!$chargeId) return;

            // Get payment_intent from charge
            $charge = \Stripe\Charge::retrieve($chargeId);
            $paymentIntentId = $charge->payment_intent ?? null;
            if (!$paymentIntentId) return;

            $payment = UpworkPayment::where('provider', 'stripe')
                ->where('provider_payment_intent_id', $paymentIntentId)
                ->first();

            if (!$payment) return;

            $amount = (int) ($dispute->amount ?? 0);

            // Dispute status: needs_response, under_review, won, lost, warning_needs_response, warning_under_review
            $status = (string) ($dispute->status ?? '');

            if ($event->type === 'charge.dispute.closed') {
                // Final outcome
                if ($status === 'lost') {
                    $this->applyChargeback($payment->id, $amount, 'stripe', $event->toArray(), stage: 'lost');
                } elseif ($status === 'won') {
                    $this->markDisputeWon($payment->id, 'stripe', $event->toArray());
                } else {
                    // closed but unknown status, just log
                    Log::warning('Dispute closed with unexpected status', [
                        'status' => $status,
                        'payment_id' => $payment->id,
                    ]);
                }
                return;
            }

            // created/updated: mark as dispute_open (or chargeback if you want)
            $this->markDisputeOpen($payment->id, $amount, 'stripe', $event->toArray(), stage: $event->type);
        } catch (\Throwable $e) {
            Log::error('processStripeDisputeEvent failed', [
                'error' => $e->getMessage(),
                'type'  => $event->type ?? null,
            ]);
        }
    }

    /**
     * CORE REFUND LOGIC (idempotent, safe)
     * refundCents is TOTAL refunded so far for that payment.
     */
    private function applyRefund(int $paymentId, int $refundCents, string $provider, array $raw): void
    {
        DB::transaction(function () use ($paymentId, $refundCents, $provider, $raw) {
            /** @var UpworkPayment $payment */
            $payment = UpworkPayment::lockForUpdate()->find($paymentId);
            if (!$payment) return;

            /** @var UpworkOrder|null $order */
            $order = UpworkOrder::lockForUpdate()->find($payment->order_id);
            if (!$order) return;

            // delta = new refunded total - previous refunded total
            $delta = max(0, $refundCents - (int) $payment->refunded_amount);
            if ($delta <= 0) return;

            $isFull = $refundCents >= (int) $payment->amount;

            $payment->refunded_amount = $refundCents;
            $payment->refund_status   = $isFull ? 'full' : 'partial';
            $payment->status          = $isFull ? 'refunded' : 'partially_refunded';
            $payment->refund_payload  = $raw;
            $payment->save();

            // Order rollup
            $order->refunded_amount = (int) ($order->refunded_amount ?? 0) + $delta;
            $order->refund_status   = ($order->refunded_amount >= (int)$order->unit_amount) ? 'full' : 'partial';
            $order->status          = 'refunded'; // make sure your order status enum allows this
            $order->save();

            // Disable any active Upwork payment links for this order
            UpworkPaymentLink::where('order_id', $order->id)
                ->where('is_active_link', true)
                ->update(['is_active_link' => false]);

            Log::info('Refund applied', [
                'payment_id' => $payment->id,
                'order_id'   => $order->id,
                'refund_total' => $refundCents,
                'delta'      => $delta,
                'full'       => $isFull,
            ]);
        });
    }

    /**
     * Chargeback / dispute lost -> mark chargeback
     */
    private function applyChargeback(int $paymentId, int $disputeAmount, string $provider, array $raw, string $stage): void
    {
        DB::transaction(function () use ($paymentId, $disputeAmount, $provider, $raw, $stage) {
            $payment = UpworkPayment::lockForUpdate()->find($paymentId);
            if (!$payment) return;

            $order = UpworkOrder::lockForUpdate()->find($payment->order_id);
            if (!$order) return;

            $payment->refund_status  = 'chargeback';
            $payment->status         = 'refunded';
            $payment->refund_payload = $raw;
            $payment->save();

            $order->refund_status = 'chargeback';
            $order->status        = 'refunded';
            $order->save();

            UpworkPaymentLink::where('order_id', $order->id)
                ->where('is_active_link', true)
                ->update(['is_active_link' => false]);

            Log::warning('Chargeback applied', [
                'payment_id' => $payment->id,
                'order_id'   => $order->id,
                'stage'      => $stage,
                'amount'     => $disputeAmount,
            ]);
        });
    }

    /**
     * Optional: mark dispute open (created/updated)
     * If you don’t have statuses for dispute open, just log it.
     */
    private function markDisputeOpen(int $paymentId, int $amount, string $provider, array $raw, string $stage): void
    {
        DB::transaction(function () use ($paymentId, $amount, $provider, $raw, $stage) {
            $payment = UpworkPayment::lockForUpdate()->find($paymentId);
            if (!$payment) return;

            // If you have a field to store dispute payload/status, store it here.
            $payment->refund_payload = $raw;
            $payment->refund_status  = 'chargeback'; // or introduce 'dispute_open' in enum if you want
            $payment->save();

            Log::warning('Dispute open/updated', [
                'payment_id' => $payment->id,
                'stage'      => $stage,
                'amount'     => $amount,
            ]);
        });
    }

    /**
     * Optional: mark dispute won
     */
    private function markDisputeWon(int $paymentId, string $provider, array $raw): void
    {
        DB::transaction(function () use ($paymentId, $provider, $raw) {
            $payment = UpworkPayment::lockForUpdate()->find($paymentId);
            if (!$payment) return;

            // Keep payment succeeded; just record payload
            $payment->refund_payload = $raw;
            // If you have a separate dispute_status field, set it here.
            $payment->save();

            Log::info('Dispute won', [
                'payment_id' => $payment->id,
            ]);
        });
    }
}
