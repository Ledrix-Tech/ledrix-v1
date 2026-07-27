<?php

namespace App\Services;

use App\Models\AccountKey;
use App\Models\Order;
use App\Models\PaymentLink;
use App\Notifications\PaymentFailedNotification;
use App\Services\Payments\PaymentRecordingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;

class StripeGateway implements PaymentGateway
{
    protected string $secret;

    public function __construct(
        ?string $secret = null,
        private ?PaymentRecordingService $recorder = null,
    ) {
        $this->secret = $secret ?? config('services.stripe.secret');

        if (! $this->secret) {
            throw new \InvalidArgumentException('Stripe secret key is missing.');
        }

        $this->recorder ??= app(PaymentRecordingService::class);

        \Stripe\Stripe::setApiKey($this->secret);
    }

    public function createCheckout(PaymentLink $link, array $buyer): array
    {
        $link->loadMissing('brand');

        Log::info('Stripe: create checkout', [
            'link_id'  => $link->id,
            'order_id' => $link->order_id,
            'brand_id' => $link->brand_id,
        ]);

        $session = Session::create([
            'mode'        => 'payment',
            'success_url' => route('paylinks.success', $link->token) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('paylinks.cancel', $link->token) . '?canceled=1',
            'line_items'  => [[
                'price_data' => [
                    'currency'     => strtolower($link->currency),
                    'product_data' => ['name' => $link->service_name],
                    'unit_amount'  => $link->unit_amount,
                ],
                'quantity' => 1,
            ]],
            'customer_email' => $buyer['email'] ?? null,
            'metadata' => [
                'brand_id'           => (string) $link->brand_id,
                'payment_link_id'    => (string) $link->id,
                'order_id'           => (string) $link->order_id,
                'lead_id'            => (string) $link->lead_id,
                'payment_link_token' => $link->token,
            ],
            'billing_address_collection' => 'required',
        ], [
            'idempotency_key' => 'paylink_' . $link->token,
        ]);

        $link->update(['provider_session_id' => $session->id]);
        $link->order?->update(['provider_session_id' => $session->id]);

        return ['id' => $session->id, 'url' => $session->url];
    }

    public function handleCheckoutSuccess(PaymentLink $link, ?string $sessionId): void
    {
        if (! $sessionId) {
            return;
        }

        $link->loadMissing(['order.client', 'brand']);

        try {
            $session = Session::retrieve($sessionId);
        } catch (\Throwable $e) {
            Log::error('Stripe return: failed to retrieve session', [
                'link_id'    => $link->id,
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ]);

            return;
        }

        if (($session->payment_status ?? null) !== 'paid') {
            $this->notifyPaymentFailed(
                $link,
                'Your payment attempt was declined or not completed.'
            );

            Log::warning('Stripe payment not completed on return', [
                'order_id' => $link->order_id,
                'session'  => $session->id ?? null,
                'status'   => $session->payment_status ?? null,
            ]);

            return;
        }

        $txnId = (string) ($session->payment_intent ?? '');

        if ($txnId === '') {
            Log::warning('Stripe return: session paid but missing payment_intent', [
                'link_id'    => $link->id,
                'session_id' => $session->id,
            ]);

            return;
        }

        try {
            [$payment, $order, $isNew] = $this->recorder->record(
                link: $link,
                provider: 'stripe',
                providerTxnId: $txnId,
                reportedAmountCents: (int) ($session->amount_total ?? 0),
                reportedCurrency: (string) ($session->currency ?? $link->currency),
                payload: $session->toArray(),
                sessionId: $session->id,
            );

            if ($isNew && $payment && $order) {
                DB::afterCommit(fn () => $this->recorder->sendInitialPaymentNotifications($payment, $order));
            }
        } catch (\Throwable $e) {
            Log::error('Stripe return: record failed (webhook may still process)', [
                'link_id' => $link->id,
                'txn_id'  => $txnId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function handleWebhook(string $payload, array $headers): bool
    {
        $sigHeader = $headers['stripe-signature'][0]
            ?? $headers['Stripe-Signature'][0]
            ?? null;

        if (! $sigHeader) {
            Log::error('Stripe webhook missing signature header');

            return false;
        }

        $temp = json_decode($payload, true);
        $brandId = $temp['data']['object']['metadata']['brand_id'] ?? null;
        $eventType = $temp['type'] ?? 'unknown';

        if (! $brandId) {
            $linkId = $temp['data']['object']['metadata']['payment_link_id'] ?? null;

            if ($linkId) {
                $brandId = PaymentLink::withoutGlobalScopes()
                    ->where('id', (int) $linkId)
                    ->value('brand_id');
            }
        }

        Log::info('Stripe webhook received', [
            'event_type' => $eventType,
            'brand_id'   => $brandId,
        ]);

        if (! $brandId) {
            Log::error('Stripe webhook missing brand_id — cannot verify or process');

            return false;
        }

        $webhookSecret = AccountKey::stripeWebhookSecretForBrand((int) $brandId, 'ppc');

        if (! $webhookSecret) {
            Log::error("No Stripe webhook secret for brand {$brandId}");

            return false;
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $shouldRetry = false;

        if (in_array($event->type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
        ], true)) {
            $session = $event->data->object;

            if (! $this->stripeSessionIsPaid($session, $event->type)) {
                Log::info('Stripe webhook: session not paid yet — waiting for async event', [
                    'event_type'     => $event->type,
                    'session_id'     => $session->id ?? null,
                    'payment_status' => $session->payment_status ?? null,
                ]);
            } else {
                $retry = $this->recordStripeSession($session);

                if ($retry) {
                    $shouldRetry = true;
                }
            }
        }

        if ($event->type === 'checkout.session.async_payment_failed') {
            $session = $event->data->object;
            $linkId  = (int) ($session->metadata->payment_link_id ?? 0);
            $link    = PaymentLink::withoutGlobalScopes()->with('order.client')->find($linkId);

            if ($link) {
                $this->notifyPaymentFailed($link, 'Your bank payment could not be completed.');
            }
        }

        if ($event->type === 'payment_intent.payment_failed') {
            $this->handlePaymentIntentFailed($event->data->object);
        }

        return ! $shouldRetry;
    }

    private function stripeSessionIsPaid(object $session, string $eventType): bool
    {
        if ($eventType === 'checkout.session.async_payment_succeeded') {
            return true;
        }

        return ($session->payment_status ?? null) === 'paid';
    }

    private function recordStripeSession(object $session): bool
    {
        $linkId = (int) ($session->metadata->payment_link_id ?? 0);
        $link   = PaymentLink::withoutGlobalScopes()->find($linkId);

        if (! $link) {
            Log::warning('Stripe webhook: payment link not found', ['link_id' => $linkId]);

            return false;
        }

        $txnId = (string) ($session->payment_intent ?? '');

        if ($txnId === '') {
            Log::warning('Stripe webhook: missing payment_intent on paid session', [
                'link_id'    => $linkId,
                'session_id' => $session->id ?? null,
            ]);

            return false;
        }

        $outcome = $this->recorder->recordFromWebhook(
            link: $link,
            provider: 'stripe',
            providerTxnId: $txnId,
            reportedAmountCents: (int) ($session->amount_total ?? 0),
            reportedCurrency: (string) ($session->currency ?? $link->currency),
            payload: (array) $session,
            sessionId: $session->id,
        );

        if ($outcome->isNew && $outcome->payment && $outcome->order) {
            DB::afterCommit(fn () => $this->recorder->sendInitialPaymentNotifications(
                $outcome->payment,
                $outcome->order,
            ));
        }

        return $outcome->shouldRetryWebhook();
    }

    private function handlePaymentIntentFailed(object $pi): void
    {
        try {
            $order = Order::withoutGlobalScopes()
                ->with('client')
                ->where('provider_payment_intent_id', $pi->id)
                ->first();

            if (! $order?->client?->email) {
                return;
            }

            Notification::route('mail', $order->client->email)
                ->notify(new PaymentFailedNotification(
                    order: $order,
                    provider: 'stripe',
                    reason: $pi->last_payment_error->message ?? 'Your card was declined.',
                    retryUrl: $order->latestPaymentLink?->last_issued_url,
                ));
        } catch (\Throwable $e) {
            Log::warning('Stripe payment_intent.payment_failed handler error', [
                'pi_id' => $pi->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getRevenue($from = null, $to = null)
    {
        $params = ['limit' => 100];

        if ($from && $to) {
            $params['created'] = [
                'gte' => strtotime($from),
                'lte' => strtotime($to),
            ];
        }

        $payments = PaymentIntent::all($params);
        $total    = 0;

        foreach ($payments->data as $payment) {
            if ($payment->status === 'succeeded') {
                $total += $payment->amount_received;
            }
        }

        return $total / 100;
    }

    private function notifyPaymentFailed(PaymentLink $link, string $reason): void
    {
        try {
            $email = $link->order?->client?->email;

            if (! $email) {
                return;
            }

            Notification::route('mail', $email)
                ->notify(new PaymentFailedNotification(
                    order: $link->order,
                    provider: 'stripe',
                    reason: $reason,
                    retryUrl: $link->last_issued_url,
                ));
        } catch (\Throwable $e) {
            Log::warning('Stripe failure notification error', [
                'link_id' => $link->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
