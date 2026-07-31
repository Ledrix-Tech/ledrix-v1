<?php

namespace App\Services\Payments;

use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Seller;
use App\Notifications\InitialPaymentNotification;
use App\Support\TenantContext;
use App\Services\BriefService;
use App\Services\CommissionDecider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PaymentRecordingService
{
    /**
     * Idempotently record a gateway payment against a payment link.
     *
     * @return array{0: Payment|null, 1: Order|null, 2: bool} [payment, order, isNew]
     */
    public function record(
        PaymentLink $link,
        string $provider,
        string $providerTxnId,
        int $reportedAmountCents,
        string $reportedCurrency,
        array $payload,
        ?string $sessionId = null,
    ): array {
        if ($providerTxnId === '') {
            return [null, null, false];
        }

        return DB::transaction(function () use (
            $link,
            $provider,
            $providerTxnId,
            $reportedAmountCents,
            $reportedCurrency,
            $payload,
            $sessionId,
        ) {
            /** @var PaymentLink $link */
            $link = PaymentLink::lockForUpdate()->findOrFail($link->id);
            /** @var Order $order */
            $order = Order::lockForUpdate()->findOrFail($link->order_id);

            abort_unless((int) $link->order_id === (int) $order->id, 422, 'Payment link does not match order.');

            $existing = Payment::withoutGlobalScopes()
                ->where('provider', $provider)
                ->where('provider_payment_intent_id', $providerTxnId)
                ->first();

            if ($existing) {
                return [$existing, $order, false];
            }

            abort_unless(
                strtoupper($reportedCurrency) === strtoupper($link->currency),
                422,
                'Currency mismatch from payment provider.'
            );

            abort_unless($reportedAmountCents > 0, 422, 'Invalid amount from payment provider.');

            $remaining = max(0, (int) $order->unit_amount - (int) $order->amount_paid);
            abort_unless($remaining > 0, 422, 'Order already fully paid.');

            // Link amount is the contract for this checkout session.
            abort_unless(
                $reportedAmountCents === (int) $link->unit_amount,
                422,
                'Amount mismatch from payment provider.'
            );

            $creditCents = min($reportedAmountCents, $remaining);

            $decider = app(CommissionDecider::class);
            $creditedToId = (int) ($link->credit_to_seller_id ?: $decider->creditSellerIdFor($order));

            $payment = Payment::create([
                'tenant_id'                  => $order->tenant_id ?? $link->tenant_id ?? TenantContext::resolve(),
                'order_id'                   => $order->id,
                'payment_link_id'            => $link->id,
                'amount'                     => $creditCents,
                'currency'                   => strtoupper($reportedCurrency),
                'status'                     => 'succeeded',
                'provider'                   => $provider,
                'provider_payment_intent_id' => $providerTxnId,
                'payload'                    => $payload,
                'seller_id'                  => (int) $order->seller_id,
                'owner_seller_id'            => (int) $order->owner_seller_id,
                'front_seller_id'            => (int) $order->front_seller_id,
                'credited_seller_id'         => $creditedToId,
                'credit_to_seller_id'        => $creditedToId,
            ]);

            $order->amount_paid = (int) $order->amount_paid + $creditCents;

            if (! $order->first_paid_at) {
                $order->first_paid_at = now();
            }

            if ($sessionId) {
                $order->provider_session_id = $sessionId;
            }
            $order->provider_payment_intent_id = $providerTxnId;
            $order->save();

            if ($link->status !== 'paid') {
                $link->update([
                    'status'                     => 'paid',
                    'expires_at'                 => now(),
                    'is_active_link'             => false,
                    'paid_at'                    => now(),
                    'provider_session_id'        => $sessionId ?: $link->provider_session_id,
                    'provider_payment_intent_id' => $providerTxnId,
                ]);
            }

            if ($creditedToId === (int) $order->front_seller_id) {
                $decider->updateCountersAfterCredit($order, $creditCents, $creditedToId);
            }

            $this->markLeadConvertedOnFirstPayment($order);

            DB::afterCommit(function () use ($order, $payment, $link) {
                if ($order->order_type === 'original' && (int) $order->balance_due === 0) {
                    app(BriefService::class)->dispatchBriefEmail($order->id);
                }
            });

            Log::info('Payment recorded', [
                'provider'  => $provider,
                'order_id'  => $order->id,
                'link_id'   => $link->id,
                'txn_id'    => $providerTxnId,
                'amount'    => $creditCents,
            ]);

            return [$payment, $order, true];
        });
    }

    /**
     * Webhook-safe wrapper: never throws; returns outcome for retry/ack decisions.
     */
    public function recordFromWebhook(
        PaymentLink $link,
        string $provider,
        string $providerTxnId,
        int $reportedAmountCents,
        string $reportedCurrency,
        array $payload,
        ?string $sessionId = null,
    ): PaymentRecordOutcome {
        if ($providerTxnId === '') {
            Log::warning('Webhook record skipped: empty transaction id', [
                'link_id'  => $link->id,
                'provider' => $provider,
            ]);

            return new PaymentRecordOutcome(PaymentRecordOutcome::SKIPPED, message: 'Empty transaction id');
        }

        try {
            [$payment, $order, $isNew] = $this->record(
                link: $link,
                provider: $provider,
                providerTxnId: $providerTxnId,
                reportedAmountCents: $reportedAmountCents,
                reportedCurrency: $reportedCurrency,
                payload: $payload,
                sessionId: $sessionId,
            );

            if ($isNew && $payment && $order) {
                return new PaymentRecordOutcome(
                    PaymentRecordOutcome::OK_NEW,
                    payment: $payment,
                    order: $order,
                    isNew: true,
                );
            }

            if ($payment) {
                return new PaymentRecordOutcome(
                    PaymentRecordOutcome::OK_DUPLICATE,
                    payment: $payment,
                    order: $order,
                    isNew: false,
                    message: 'Already recorded',
                );
            }

            return new PaymentRecordOutcome(PaymentRecordOutcome::SKIPPED, message: 'No payment row created');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            Log::warning('Webhook payment record rejected (validation)', [
                'link_id'   => $link->id,
                'provider'  => $provider,
                'txn_id'    => $providerTxnId,
                'status'    => $e->getStatusCode(),
                'message'   => $e->getMessage(),
            ]);

            return new PaymentRecordOutcome(
                PaymentRecordOutcome::SKIPPED,
                message: $e->getMessage(),
            );
        } catch (\Illuminate\Database\QueryException $e) {
            if ($this->isRetryableDatabaseError($e)) {
                Log::error('Webhook payment record DB error (retryable)', [
                    'link_id'  => $link->id,
                    'provider' => $provider,
                    'txn_id'   => $providerTxnId,
                    'error'    => $e->getMessage(),
                ]);

                return new PaymentRecordOutcome(
                    PaymentRecordOutcome::FAILED_RETRY,
                    message: $e->getMessage(),
                );
            }

            Log::error('Webhook payment record DB error (non-retryable)', [
                'link_id'  => $link->id,
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);

            return new PaymentRecordOutcome(PaymentRecordOutcome::SKIPPED, message: $e->getMessage());
        } catch (\Throwable $e) {
            if ($this->isRetryableError($e)) {
                Log::error('Webhook payment record error (retryable)', [
                    'link_id'  => $link->id,
                    'provider' => $provider,
                    'error'    => $e->getMessage(),
                ]);

                return new PaymentRecordOutcome(
                    PaymentRecordOutcome::FAILED_RETRY,
                    message: $e->getMessage(),
                );
            }

            Log::error('Webhook payment record error (non-retryable)', [
                'link_id'  => $link->id,
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);

            return new PaymentRecordOutcome(PaymentRecordOutcome::SKIPPED, message: $e->getMessage());
        }
    }

    private function isRetryableDatabaseError(\Illuminate\Database\QueryException $e): bool
    {
        $code = (string) ($e->errorInfo[1] ?? '');

        // MySQL deadlock / lock wait timeout
        if (in_array($code, ['1205', '1213'], true)) {
            return true;
        }

        return $this->isRetryableError($e);
    }

    private function isRetryableError(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());

        return str_contains($msg, 'deadlock')
            || str_contains($msg, 'lock wait timeout')
            || str_contains($msg, 'try again');
    }

    public function sendInitialPaymentNotifications(Payment $payment, Order $order): void
    {
        $order->loadMissing('client');
        $client = $order->client;

        if (! $client?->email) {
            return;
        }

        Notification::route('mail', $client->email)
            ->notify(
                (new InitialPaymentNotification($payment, $order, $client, 'ppc'))
                    ->delay(now()->addSeconds(3))
            );

        $creditedSeller = Seller::find($payment->credit_to_seller_id);
        if ($creditedSeller?->email) {
            Notification::route('mail', $creditedSeller->email)
                ->notify(
                    (new InitialPaymentNotification($payment, $order, $client, 'ppc'))
                        ->delay(now()->addSeconds(6))
                );
        }

        if ((int) $order->owner_seller_id !== (int) $order->front_seller_id) {
            $pm = Seller::find($order->owner_seller_id);
            if ($pm?->email) {
                Notification::route('mail', $pm->email)
                    ->notify(
                        (new InitialPaymentNotification($payment, $order, $client, 'ppc'))
                            ->delay(now()->addSeconds(9))
                    );
                }
        }
    }

    private function markLeadConvertedOnFirstPayment(Order $order): void
    {
        if (! $order->lead_id || (int) $order->amount_paid <= 0) {
            return;
        }

        Lead::withoutGlobalScopes()
            ->where('id', $order->lead_id)
            ->whereNotIn('status', ['first_paid', 'in_progress', 'completed', 'renewal_due'])
            ->update([
                'status'       => 'first_paid',
                'converted_at' => now(),
            ]);
    }
}
