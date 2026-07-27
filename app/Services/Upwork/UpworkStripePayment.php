<?php

namespace App\Services\Upwork;

use App\Models\AccountKey;
use App\Models\Upwork\UpworkPaymentLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;

class UpworkStripePayment implements UpworkPaymentGateway
{
    protected string $secret;

    public function __construct(
        ?string $secret = null,
        protected UpworkPaymentRecorder $recorder
    ) {
        $this->secret = $secret ?? config('services.stripe.secret');

        if (empty($this->secret)) {
            throw new \InvalidArgumentException('Stripe secret key is missing.');
        }

        Stripe::setApiKey($this->secret);
    }

    public function createCheckout(UpworkPaymentLink $link, array $buyer): array
    {
        $link->loadMissing('order');
        $order = $link->order;
        if (!$order) {
            throw new \RuntimeException('Payment link missing order.');
        }

        $successUrl = route('upwork.paylinks.success', $link->token) . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = route('upwork.paylinks.cancel', $link->token) . '?canceled=1';

        $session = Session::create([
            'mode'        => 'payment',
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'line_items'  => [[
                'price_data' => [
                    'currency'     => strtolower($link->currency),
                    'product_data' => ['name' => $link->service_name],
                    'unit_amount'  => (int) $link->unit_amount,
                ],
                'quantity' => 1,
            ]],
            'customer_email' => $buyer['email'] ?? null,
            'metadata' => [
                'module'             => 'upwork',
                'upwork_link_id'     => (string) $link->id,
                'upwork_order_id'    => (string) $order->id,
                'brand_id'           => (string) $order->brand_id,
                'payment_link_token' => (string) $link->token,
            ],
            'billing_address_collection' => 'required',
        ], [
            'idempotency_key' => 'upwork_paylink_' . $link->token,
        ]);

        $link->update(['provider_session_id' => $session->id]);
        $order->update(['provider_session_id' => $session->id]);

        return ['id' => $session->id, 'url' => $session->url];
    }

    public function handleCheckoutSuccess(UpworkPaymentLink $link, ?string $sessionId): void
    {
        if (!$sessionId) return;

        $link->loadMissing('order', 'client');
        $order = $link->order;
        if (!$order) return;

        $session = Session::retrieve($sessionId);

        if (($session->payment_status ?? null) !== 'paid') {
            Log::warning('Upwork Stripe checkout returned not-paid', [
                'token' => $link->token,
                'session_id' => $sessionId
            ]);
            return;
        }

        $piId     = $session->payment_intent ?? null;
        $amount   = (int) ($session->amount_total ?? 0);
        $currency = strtoupper($session->currency ?? $link->currency);

        if (!$piId) return;

        [$payment, $order] = $this->recorder->recordSucceededPayment(
            link: $link,
            provider: 'stripe',
            providerPaymentIntentId: $piId,
            reportedAmountCents: $amount,
            reportedCurrency: $currency,
            payload: $session->toArray(),
            sessionId: $session->id
        );

        $client = $link->client ?? $order->client ?? null;

        if ($client?->email) {
            DB::afterCommit(function () use ($client, $payment, $order) {
                Notification::route('mail', $client->email)
                    ->notify((new \App\Notifications\InitialPaymentNotification($payment, $order, $client, 'upwork'))
                        ->delay(now()->addSeconds(3)));
            });
        }
    }

    public function handleWebhook(string $payload, array $headers): bool
    {
        $sigHeader = $headers['stripe-signature'][0]
            ?? $headers['Stripe-Signature'][0]
            ?? null;

        if (!$sigHeader) {
            Log::error('Upwork Stripe webhook missing signature header');
            return false;
        }

        $temp = json_decode($payload, true);
        $brandId = $temp['data']['object']['metadata']['brand_id'] ?? null;
        if (!$brandId) {
            Log::warning('Upwork Stripe webhook missing brand_id');
            return false;
        }

        // ✅ MUST filter by module + active
        $webhookSecret = AccountKey::stripeWebhookSecretForBrand((int) $brandId, 'upwork');

        if (!$webhookSecret) {
            Log::error("Upwork Stripe webhook secret missing for brand {$brandId}");
            return false;
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Throwable $e) {
            Log::error('Upwork Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return false;
        }

        if (!in_array($event->type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded'
        ], true)) {
            return true;
        }

        $session = $event->data->object;
        $linkId  = (int) ($session->metadata->upwork_link_id ?? 0);

        $link = UpworkPaymentLink::with(['order', 'client'])->find($linkId);
        if (!$link) {
            Log::warning('UpworkPaymentLink not found', ['upwork_link_id' => $linkId]);
            return true;
        }

        $piId     = $session->payment_intent ?? null;
        $amount   = (int) ($session->amount_total ?? 0);
        $currency = strtoupper($session->currency ?? $link->currency);

        if (!$piId) return true;

        [$payment, $order] = $this->recorder->recordSucceededPayment(
            link: $link,
            provider: 'stripe',
            providerPaymentIntentId: $piId,
            reportedAmountCents: $amount,
            reportedCurrency: $currency,
            payload: (array) $session,
            sessionId: $session->id
        );

        $client = $link->client ?? $order->client ?? null;

        if ($client?->email) {
            DB::afterCommit(function () use ($client, $payment, $order) {
                Notification::route('mail', $client->email)
                    ->notify((new \App\Notifications\InitialPaymentNotification($payment, $order, $client, 'upwork'))
                        ->delay(now()->addSeconds(3)));
            });
        }

        return true;
    }
}


// class UpworkStripePayment implements UpworkPaymentGateway
// {
//     protected string $secret;

//     public function __construct(?string $secret = null)
//     {
//         $this->secret = $secret ?? config('services.stripe.secret');

//         if (empty($this->secret)) {
//             throw new \InvalidArgumentException('Stripe secret key is missing.');
//         }

//         Stripe::setApiKey($this->secret);
//     }

//     public function createCheckout(UpworkPaymentLink $link, array $buyer): array
//     {
//         $link->loadMissing('order'); // order needed
//         $order = $link->order;
//         if (!$order) {
//             throw new \RuntimeException('Payment link missing order.');
//         }

//         // ✅ Upwork routes
//         $successUrl = route('paylinks.success', $link->token) . '?session_id={CHECKOUT_SESSION_ID}';
//         $cancelUrl  = route('paylinks.cancel', $link->token) . '?canceled=1';

//         $session = Session::create([
//             'mode'        => 'payment',
//             'success_url' => $successUrl,
//             'cancel_url'  => $cancelUrl,
//             'line_items'  => [[
//                 'price_data' => [
//                     'currency'     => strtolower($link->currency),
//                     'product_data' => ['name' => $link->service_name],
//                     'unit_amount'  => (int) $link->unit_amount,
//                 ],
//                 'quantity' => 1,
//             ]],
//             'customer_email' => $buyer['email'] ?? null,

//             // ✅ metadata minimal + Upwork specific
//             'metadata' => [
//                 'module'            => 'upwork',
//                 'upwork_link_id'    => (string) $link->id,
//                 'upwork_order_id'   => (string) $order->id,
//                 'brand_id'          => (string) $order->brand_id, // important for webhook secret
//                 'payment_link_token' => (string) $link->token,
//             ],

//             'billing_address_collection' => 'required',
//         ], [
//             // idempotent for re-tries
//             'idempotency_key' => 'upwork_paylink_' . $link->token,
//         ]);

//         $link->update(['provider_session_id' => $session->id]);
//         $order->update(['provider_session_id' => $session->id]);

//         return ['id' => $session->id, 'url' => $session->url];
//     }

//     public function handleCheckoutSuccess(UpworkPaymentLink $link, ?string $sessionId): void
//     {
//         if (!$sessionId) return;

//         $link->loadMissing('order');
//         $order = $link->order;
//         if (!$order) return;

//         $session = Session::retrieve($sessionId);

//         if (($session->payment_status ?? null) !== 'paid') {
//             Log::warning('Upwork Stripe checkout returned not-paid', [
//                 'token' => $link->token,
//                 'session_id' => $sessionId
//             ]);
//             return;
//         }

//         $piId     = $session->payment_intent ?? null;
//         $amount   = (int) ($session->amount_total ?? 0);
//         $currency = strtoupper($session->currency ?? $link->currency);

//         $this->recordUpworkPayment(
//             link: $link,
//             providerPaymentIntentId: $piId,
//             sessionId: $session->id,
//             amountCents: $amount,
//             currency: $currency,
//             payload: $session->toArray()
//         );
//     }

//     public function handleWebhook(string $payload, array $headers): bool
//     {
//         $sigHeader = $headers['stripe-signature'][0]
//             ?? $headers['Stripe-Signature'][0]
//             ?? null;

//         if (!$sigHeader) {
//             Log::error('Upwork Stripe webhook missing signature header');
//             return false;
//         }

//         // extract brand_id from payload metadata to select correct webhook secret
//         $temp = json_decode($payload, true);
//         $brandId = $temp['data']['object']['metadata']['brand_id'] ?? null;

//         if (!$brandId) {
//             Log::warning('Upwork Stripe webhook missing brand_id');
//             return false;
//         }

//         // $webhookSecret = AccountKey::where('brand_id', $brandId)->value('stripe_webhook_secret');
//         $webhookSecret = AccountKey::where('brand_id', $brandId)
//             ->where('module', 'upwork')
//             ->where('status', 'active')
//             ->value('stripe_webhook_secret');

//         if (!$webhookSecret) {
//             Log::error("Upwork Stripe webhook secret missing for brand {$brandId}");
//             return false;
//         }

//         try {
//             $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
//         } catch (\Throwable $e) {
//             Log::error('Upwork Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
//             return false;
//         }

//         if (!in_array($event->type, [
//             'checkout.session.completed',
//             'checkout.session.async_payment_succeeded'
//         ], true)) {
//             return true; // ignore other events
//         }

//         $session = $event->data->object;
//         $linkId  = (int) ($session->metadata->upwork_link_id ?? 0);

//         $link = UpworkPaymentLink::with('order')->find($linkId);
//         if (!$link) {
//             Log::warning('UpworkPaymentLink not found', ['upwork_link_id' => $linkId]);
//             return true;
//         }

//         $piId     = $session->payment_intent ?? null;
//         $amount   = (int) ($session->amount_total ?? 0);
//         $currency = strtoupper($session->currency ?? $link->currency);

//         $this->recordUpworkPayment(
//             link: $link,
//             providerPaymentIntentId: $piId,
//             sessionId: $session->id,
//             amountCents: $amount,
//             currency: $currency,
//             payload: (array) $session
//         );

//         return true;
//     }

//     private function recordUpworkPayment(
//         UpworkPaymentLink $link,
//         ?string $providerPaymentIntentId,
//         ?string $sessionId,
//         int $amountCents,
//         string $currency,
//         array $payload
//     ): void {
//         if (!$providerPaymentIntentId) return;

//         DB::transaction(function () use ($link, $providerPaymentIntentId, $sessionId, $amountCents, $currency, $payload) {

//             /** @var UpworkOrder $order */
//             $order = UpworkOrder::lockForUpdate()->findOrFail($link->order_id);

//             // ✅ Idempotent guard
//             $existing = UpworkPayment::where('provider', 'stripe')
//                 ->where('provider_payment_intent_id', $providerPaymentIntentId)
//                 ->first();

//             if ($existing) {
//                 return;
//             }

//             // cap payment by remaining due
//             $remaining = max(0, (int)$order->unit_amount - (int)$order->amount_paid);
//             $credit    = min($amountCents, $remaining);

//             // ✅ Capture payment model
//             $payment = UpworkPayment::create([
//                 'order_id'                   => $order->id,
//                 'payment_link_id'            => $link->id,
//                 'amount'                     => $credit,
//                 'currency'                   => $currency,
//                 'status'                     => 'succeeded',
//                 'provider'                   => 'stripe',
//                 'provider_payment_intent_id' => $providerPaymentIntentId,
//                 'payload'                    => $payload,
//             ]);

//             // roll-up order
//             $order->amount_paid += $credit;
//             $order->balance_due = max(0, (int)$order->unit_amount - (int)$order->amount_paid);

//             if ($order->balance_due === 0) {
//                 $order->status = 'paid';
//                 $order->paid_at = now();
//             } else {
//                 $order->status = 'pending';
//             }

//             $order->provider_session_id = $sessionId;
//             $order->provider_payment_intent_id = $providerPaymentIntentId;
//             $order->save();

//             // mark link used
//             $link->update([
//                 'status'                     => 'paid',
//                 'is_active_link'             => false,
//                 'paid_at'                    => now(),
//                 'expires_at'                 => now(),
//                 'provider_session_id'        => $sessionId,
//                 'provider_payment_intent_id' => $providerPaymentIntentId,
//             ]);

//             // ✅ Load client (use link->client if relation exists, else order->client)
//             $client = $link->client ?? $order->client ?? null;
//             DB::afterCommit(function () use ($client, $payment, $order) {
//                 if ($client?->email) {
//                     Notification::route('mail', $client->email)
//                         ->notify(
//                             (new InitialPaymentNotification($payment, $order, $client, 'upwork'))
//                                 ->delay(now()->addSeconds(3))
//                         );
//                 }
//             });

//             // if ($client?->email) {
//             //     Notification::route('mail', $client->email)
//             //         ->notify(
//             //             (new InitialPaymentNotification($payment, $order, $client, 'upwork'))
//             //                 ->delay(now()->addSeconds(3))
//             //         );
//             // }
//         });
//     }
// }
