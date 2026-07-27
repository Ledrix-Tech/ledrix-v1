<?php

// app/Services/PayPalGateway.php
namespace App\Services\Upwork;

use App\Models\Admin;
use App\Models\Upwork\UpworkOrder;
use Illuminate\Support\Facades\DB;
use App\Services\CommissionDecider;
use Illuminate\Support\Facades\Log;
use App\Models\Upwork\UpworkPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Upwork\UpworkPaymentLink;
use Illuminate\Support\Facades\Notification;
use App\Services\Upwork\UpworkPaymentGateway;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\InitialPaymentNotification;

class UpworkPayPalPayment implements UpworkPaymentGateway
{

    protected string $clientId;
    protected string $secret;
    protected string $base;
    protected ?string $webhookId;

    public function __construct(array $config)
    {
        $this->clientId  = $config['client_id'];
        $this->secret    = $config['secret'];
        $this->base      = rtrim($config['base'] ?? 'https://api.paypal.com', '/');
        $this->webhookId = $config['webhook_id'] ?? null;
    }

    public function createCheckout(UpworkPaymentLink $link, array $buyer): array
    {
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => strtoupper($link->currency),
                    'value'         => number_format($link->unit_amount / 100, 2, '.', ''),
                ],
                'custom_id' => json_encode([
                    'order_id'        => (int)$link->order_id,
                    'payment_link_id' => (int)$link->id,
                    'token'           => $link->token,
                ]),
                'description' => $link->service_name,
            ]],
            'application_context' => [
                'brand_name'          => optional($link->brand)->brand_name ?: config('app.name'),
                'return_url'          => route('upwork.paylinks.success', $link->token),
                'cancel_url'  => route('upwork.paylinks.cancel', $link->token) . '?canceled=1',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action'         => 'PAY_NOW',
            ],
        ];

        Log::debug('PayPal createCheckout payload', [
            'payload' => $payload,
            'base_url' => $this->base,
        ]);

        $res = $this->api('POST', '/v2/checkout/orders', $payload);
        Log::debug('PayPal API response', ['response' => $res]);

        $paypalOrderId = $res['id'] ?? null;

        // Find approve link
        $approve = null;
        if (isset($res['links']) && is_array($res['links'])) {
            foreach ($res['links'] as $linkObj) {
                if (isset($linkObj['rel']) && $linkObj['rel'] === 'approve') {
                    $approve = $linkObj;
                    break;
                }
            }
        }

        $url = $approve['href'] ?? null;

        if (!$paypalOrderId || !$url) {
            Log::error('PayPal createCheckout failed: missing id or approve link', [
                'res' => $res,
                'order_id' => $paypalOrderId,
                'approve' => $approve,
            ]);
            // Optionally throw an exception or return an error
            return ['id' => null, 'url' => null];

            Log::error('PayPal capture detailed failure', [
                'paypalOrderId' => $paypalOrderId,
                'response' => $e->getMessage(),
            ]);
        }

        // Save session id
        $link->update(['provider_session_id' => $paypalOrderId]);
        $link->order?->update(['provider_session_id' => $paypalOrderId]);

        Log::info('PayPal create checkout success', [
            'link_id'         => $link->id,
            'order_id'        => $link->order_id,
            'paypal_order_id' => $paypalOrderId,
            'url'              => $url,
        ]);

        return ['id' => $paypalOrderId, 'url' => $url];
    }

    public function handleCheckoutSuccess(UpworkPaymentLink $link, ?string $sessionId): void
    {
        $paypalOrderId = $sessionId ?: $link->provider_session_id;
        if (!$paypalOrderId) return;

        Log::debug('Calling capture', [
            'order_id' => $paypalOrderId,
            'link_details' => $link,
        ]);


        try {
            $cap = $this->api('POST', "/v2/checkout/orders/{$paypalOrderId}/capture");
            Log::info('PayPal: capture response', ['order' => $paypalOrderId, 'status' => $cap['status'] ?? null]);
        } catch (\Throwable $e) {
            Log::warning('PayPal capture failed, retrying in 3 seconds...');
            sleep(3);

            try {
                $cap = $this->api('POST', "/v2/checkout/orders/{$paypalOrderId}/capture");
            } catch (\Throwable $e2) {
                Log::error('Second PayPal capture attempt failed', [
                    'order_id' => $paypalOrderId,
                    'error' => $e2->getMessage(),
                ]);
                // >>> SEND FAILURE NOTIFICATION <<<
                $order = $link->order;
                Notification::route('mail', $order->client->email)
                    ->notify(new PaymentFailedNotification(
                        order: $order,
                        provider: 'paypal',
                        reason: 'Your PayPal payment was declined or could not be processed.',
                        retryUrl: $link->last_issued_url
                    ));
                return;
            }
        }

        // ✅ Capture object -> payments.captures[0]
        $purchaseUnit = $cap['purchase_units'][0] ?? [];
        $capture      = $purchaseUnit['payments']['captures'][0] ?? [];

        $captureId   = $capture['id'] ?? null;
        $amountValue = $capture['amount']['value'] ?? null;
        $currency    = $capture['amount']['currency_code'] ?? null;

        // ✅ READ custom_id FROM THE CAPTURE
        $custom_id   = $capture['custom_id'] ?? null;
        // fallback (rare): some responses include it at the unit level
        if (!$custom_id) {
            $custom_id = $purchaseUnit['custom_id'] ?? null;
        }
        $meta = $custom_id ? json_decode($custom_id, true) : null;

        if (!$meta || !isset($meta['order_id'], $meta['payment_link_id'])) {
            Log::warning('PayPal success missing custom_id metadata.', ['cap' => $cap]);
            return;
        }

        $orderId = (int)$meta['order_id'];
        $linkId  = (int)$meta['payment_link_id'];
        $cents   = (int) round(((float)$amountValue) * 100);

        [$payment, $order] = $this->recordPayment(
            provider: 'paypal',
            providerTxnId: $captureId ?: $paypalOrderId,
            orderId: $orderId,
            linkId: $linkId,
            cents: $cents,
            reportedAmount: $cents,
            currency: $currency ?: 'USD',
            rawPayload: $cap
        );

        // $this->sendInitialPaymentNotifications($payment, $order);
    }

    /** Webhook (optional but recommended). Safe to call even if return handled it. */
    public function handleWebhook(string $payload, array $headers): bool
    {
        $event = json_decode($payload, true);

        if (! is_array($event)) {
            Log::warning('Upwork PayPal webhook invalid JSON');

            return false;
        }

        if ($this->webhookId && ! $this->verifySignature($payload, $headers)) {
            Log::warning('Upwork PayPal webhook signature invalid');

            return false;
        }

        Log::info('Upwork PayPal webhook received', [
            'event' => $event['event_type'] ?? 'unknown',
            'id'    => $event['id'] ?? 'n/a',
        ]);

        $type = $event['event_type'] ?? '';
        Log::info('PayPal webhook', ['type' => $type]);

        if ($type === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource   = $event['resource'] ?? [];
            $amount     = $resource['amount']['value'] ?? null;
            $currency   = $resource['amount']['currency_code'] ?? null;
            $captureId  = $resource['id'] ?? null;

            // ✅ try reading custom_id directly off the capture resource
            $custom_id  = $resource['custom_id'] ?? null;
            $meta       = $custom_id ? json_decode($custom_id, true) : null;

            Log::info('PayPal webhook received', ['headers' => $headers, 'payload' => $payload, $event['resource']]);

            // dd($meta,'Link');

            // fallback: fetch the PP order and read purchase_units[0].custom_id
            if (!$meta || !isset($meta['order_id'], $meta['payment_link_id'])) {
                $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
                if ($paypalOrderId) {
                    $ord        = $this->api('GET', "/v2/checkout/orders/{$paypalOrderId}");
                    $unitCustom = $ord['purchase_units'][0]['custom_id'] ?? null;
                    $meta       = $unitCustom ? json_decode($unitCustom, true) : null;
                }
            }

            if (!$meta || !isset($meta['order_id'], $meta['payment_link_id'])) {
                Log::warning('PayPal missing custom_id metadata; skipping.');
                return true;
            }

            $orderId = (int)$meta['order_id'];
            $linkId  = (int)$meta['payment_link_id'];
            $cents   = (int) round(((float)$amount) * 100);

            [$payment, $order] = $this->recordPayment(
                provider: 'paypal',
                providerTxnId: $captureId ?: ($event['id'] ?? 'n/a'),
                orderId: $orderId,
                linkId: $linkId,
                cents: $cents,
                reportedAmount: $cents,
                currency: $currency ?: 'USD',
                rawPayload: $event
            );

            $this->sendInitialPaymentNotifications($payment, $order);

            return true;
        }

        if ($type === 'CHECKOUT.ORDER.APPROVED') {
            // usually followed by CAPTURE; nothing to do here
            return true;
        }

        return true;
    }

    protected function recordPayment(
        string $provider,
        string $providerTxnId,
        int $orderId,
        int $linkId,
        int $cents,
        int $reportedAmount,
        string $currency,
        array $rawPayload
    ): array {

        return DB::transaction(function () use (
            $provider,
            $providerTxnId,
            $orderId,
            $linkId,
            $cents,
            $reportedAmount,
            $currency,
            $rawPayload
        ) {

            $link  = UpworkPaymentLink::lockForUpdate()->findOrFail($linkId);
            $order = UpworkOrder::lockForUpdate()->findOrFail($orderId);

            if (!$link) {
                Log::error('Payment link not found.', ['payment_link_id' => $link->id]);
                return response()->json(['error' => 'Payment link not found.'], 404);
            }
            // Idempotency
            $exists = UpworkPayment::where('provider', $provider)
                ->where('provider_payment_intent_id', $providerTxnId)
                ->exists();

            if ($exists) {
                Log::info('PayPal payment already recorded; skipping', [
                    'txn' => $providerTxnId
                ]);

                // ⭐ Return existing payment + order so notifications don't break
                $p = UpworkPayment::where('provider_payment_intent_id', $providerTxnId)->first();
                return [$p, $order];
            }

            // compute remaining
            $remaining   = max(0, (int) $order->unit_amount - (int) $order->amount_paid);
            $creditCents = min((int)$reportedAmount, $remaining);

            // Create payment
            $payment = UpworkPayment::create([
                'order_id'        => $order->id,
                'payment_link_id' => $link->id,
                'amount'          => $creditCents,
                'currency'        => strtoupper($currency),
                'status'          => 'succeeded',
                'provider'        => $provider,
                'provider_payment_intent_id' => $providerTxnId,
                'payload'         => $rawPayload,
            ]);

            // Update order
            $order->amount_paid += $creditCents;
            if (!$order->first_paid_at) {
                $order->first_paid_at = now();
            }
            $order->provider_payment_intent_id = $providerTxnId;
            $order->save();

            if ($link->status !== 'paid') {
                $link->update([
                    'status'                     => 'paid',
                    'expires_at'                 => now(),
                    'is_active_link'             => false,
                    'paid_at'                    => now(),
                    'provider_session_id'        => $link->provider_session_id, // we stored PP Order ID here
                    'provider_payment_intent_id' => $providerTxnId,           // store PP Capture ID here
                ]);
            }

            // Return correctly!
            return [$payment, $order];
        });
    }

    protected function verifySignature(string $payload, array $headers): bool
    {
        try {
            $body = [
                'auth_algo'         => $headers['paypal-auth-algo'][0]       ?? $headers['PayPal-Auth-Algo'][0]       ?? '',
                'cert_url'          => $headers['paypal-cert-url'][0]        ?? $headers['PayPal-Cert-Url'][0]        ?? '',
                'transmission_id'   => $headers['paypal-transmission-id'][0] ?? $headers['PayPal-Transmission-Id'][0] ?? '',
                'transmission_sig'  => $headers['paypal-transmission-sig'][0] ?? $headers['PayPal-Transmission-Sig'][0] ?? '',
                'transmission_time' => $headers['paypal-transmission-time'][0] ?? $headers['PayPal-Transmission-Time'][0] ?? '',
                'webhook_id'        => $this->webhookId,
                'webhook_event'     => json_decode($payload, true),
            ];
            $res = $this->api('POST', '/v1/notifications/verify-webhook-signature', $body);
            return ($res['verification_status'] ?? '') === 'SUCCESS';
        } catch (\Throwable $e) {
            Log::warning('PayPal webhook verification failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function api(string $method, string $path, ?array $json = null): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            Log::error('PayPal getAccessToken failed or returned null');
            throw new \RuntimeException('PayPal access token is missing');
        }

        $opts = [
            CURLOPT_URL            => $this->base . $path,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: ' . "Bearer {$token}",
            ],
        ];
        if ($json !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($json);
        }

        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err || $code < 200 || $code >= 300) {
            Log::error("PayPal API error — HTTP {$code}", [
                'error'    => $err,
                'response' => $resp,
                'method'   => $method,
                'path'     => $path,
                'body'     => $json,
            ]);
            throw new \RuntimeException("PayPal API Error ({$code}): " . ($err ?: $resp));
        }

        $decoded = json_decode($resp, true);
        if (!is_array($decoded)) {
            Log::error('PayPal API returned non-array JSON', ['resp' => $resp]);
            throw new \RuntimeException("Invalid PayPal API response");
        }

        return $decoded;
    }

    protected function getAccessToken(): string
    {
        return Cache::remember("paypal_token_{$this->clientId}", 300, function () {
            $auth = base64_encode("{$this->clientId}:{$this->secret}");

            $opts = [
                CURLOPT_URL            => $this->base . '/v1/oauth2/token',
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Basic ' . $auth,
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            ];

            $ch = curl_init();
            curl_setopt_array($ch, $opts);
            $resp = curl_exec($ch);
            $err  = curl_error($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($err || $code < 200 || $code >= 300) {
                Log::error("PayPal token fetch error — HTTP {$code}", ['error' => $err, 'resp' => $resp]);
                throw new \RuntimeException("PayPal token error ({$code}): " . ($err ?: $resp));
            }

            $json = json_decode($resp, true);
            return $json['access_token'] ?? '';
        });
    }

    /* ================= Email Notification helpers ================= */
    private function sendInitialPaymentNotifications(UpworkPayment $payment, UpworkOrder $order): void
    {
        // Ensure 'client' relation is loaded
        $order->load('client');

        // 1) Client notification
        if ($order->client && $order->client->email) {
            Notification::route('mail', $order->client->email)
                ->notify(
                    (new InitialPaymentNotification($payment, $order, $order->client))
                        ->delay(now()->addSeconds(3))
                );
        }

        // 2) Notify BOTH up_admin and admin roles
        $admins = Admin::whereIn('role', ['up_admin', 'admin'])
            ->whereNotNull('email')
            ->get(['id', 'email', 'role']);

        foreach ($admins as $i => $admin) {
            Notification::route('mail', $admin->email)
                ->notify(
                    (new InitialPaymentNotification($payment, $order, $order->client))
                        ->delay(now()->addSeconds(6 + ($i * 2))) // Delay with increasing time
                );
        }
    }
}
