<?php

namespace App\Services;

use App\Models\PaymentLink;
use App\Notifications\PaymentFailedNotification;
use App\Services\Payments\PaymentRecordingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PayPalGateway implements PaymentGateway
{
    protected string $clientId;
    protected string $secret;
    protected string $base;
    protected ?string $webhookId;

    public function __construct(
        array $config,
        private ?PaymentRecordingService $recorder = null,
    ) {
        $this->clientId  = (string) ($config['client_id'] ?? '');
        $this->secret    = (string) ($config['secret'] ?? '');
        $this->base      = rtrim((string) ($config['base'] ?? config('services.paypal.base', 'https://api-m.paypal.com')), '/');
        $this->webhookId = $config['webhook_id'] ?? null;

        if (! $this->clientId || ! $this->secret) {
            throw new \InvalidArgumentException('PayPal client_id/secret missing.');
        }

        $this->recorder ??= app(PaymentRecordingService::class);
    }

    public function createCheckout(PaymentLink $link, array $buyer): array
    {
        $link->loadMissing('brand');

        if (! $link->order_id) {
            throw new \RuntimeException('PaymentLink missing order_id.');
        }

        if (! $link->currency || $link->unit_amount <= 0) {
            throw new \RuntimeException('PaymentLink currency/amount invalid.');
        }

        $paypalAmount = $this->formatPayPalAmount($link->unit_amount);

        $customId = json_encode([
            'order_id'        => (int) $link->order_id,
            'payment_link_id' => (int) $link->id,
            'token'           => (string) $link->token,
            'brand_id'        => (int) $link->brand_id,
        ], JSON_UNESCAPED_SLASHES);

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => strtoupper($link->currency),
                    'value'         => $paypalAmount,
                ],
                'custom_id'   => $customId,
                'description' => (string) $link->service_name,
            ]],
            'application_context' => [
                'brand_name'          => optional($link->brand)->brand_name ?: config('app.name'),
                'return_url'          => route('paylinks.success', $link->token),
                'cancel_url'          => route('paylinks.cancel', $link->token) . '?canceled=1',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action'         => 'PAY_NOW',
            ],
        ];

        Log::info('PayPal createCheckout', [
            'link_id'  => $link->id,
            'order_id' => $link->order_id,
            'brand_id' => $link->brand_id,
            'amount'   => $paypalAmount,
            'currency' => strtoupper($link->currency),
        ]);

        $res = $this->api('POST', '/v2/checkout/orders', $payload, [
            'PayPal-Request-Id' => 'pp_create_' . $link->token,
        ]);

        $paypalOrderId = $res['id'] ?? null;
        $approveUrl    = $this->extractApproveUrl($res);

        if (! $paypalOrderId || ! $approveUrl) {
            Log::error('PayPal createCheckout missing id/approve', ['response' => $res]);
            throw new \RuntimeException('PayPal createCheckout failed (missing approve link).');
        }

        $link->update(['provider_session_id' => $paypalOrderId]);
        $link->order?->update(['provider_session_id' => $paypalOrderId]);

        return ['id' => $paypalOrderId, 'url' => $approveUrl];
    }

    public function handleCheckoutSuccess(PaymentLink $link, ?string $sessionId): void
    {
        $paypalOrderId = $sessionId ?: $link->provider_session_id;

        if (! $paypalOrderId) {
            Log::warning('PayPal success called without paypalOrderId', ['link_id' => $link->id]);

            return;
        }

        $link->loadMissing(['order.client', 'brand']);
        $link->refresh();

        // Webhook may have already recorded this payment.
        if ($link->status === 'paid') {
            Log::info('PayPal return: link already paid', ['link_id' => $link->id]);

            return;
        }

        try {
            $cap = $this->api('POST', "/v2/checkout/orders/{$paypalOrderId}/capture", null, [
                'PayPal-Request-Id' => 'pp_capture_' . $paypalOrderId,
            ]);

            $this->recordFromCapturePayload($cap, $paypalOrderId);
        } catch (\Throwable $e) {
            if ($this->isAlreadyCapturedError($e)) {
                Log::info('PayPal capture skipped: order already captured', [
                    'paypal_order_id' => $paypalOrderId,
                    'link_id'         => $link->id,
                ]);
                $this->recordFromPayPalOrderId($paypalOrderId);

                return;
            }

            Log::error('PayPal capture failed on return', [
                'paypal_order_id' => $paypalOrderId,
                'link_id'         => $link->id,
                'error'           => $e->getMessage(),
            ]);

            $this->notifyCaptureFailed($link, 'Your PayPal payment could not be confirmed. Please contact support if you were charged.');
        }
    }

    public function handleWebhook(string $payload, array $headers): bool
    {
        $event = json_decode($payload, true);

        if (! is_array($event)) {
            Log::warning('PayPal webhook invalid JSON');

            return false;
        }

        if ($this->webhookId) {
            if (! $this->verifySignature($payload, $headers)) {
                Log::warning('PayPal webhook signature invalid', [
                    'event_type' => $event['event_type'] ?? null,
                    'event_id'   => $event['id'] ?? null,
                ]);

                return false;
            }
        } elseif (app()->environment('production')) {
            Log::error('PayPal webhook_id not configured in production — rejecting webhook');

            return false;
        } else {
            Log::warning('PayPal webhook_id not configured — skipping signature verification (non-production)');
        }

        $type = (string) ($event['event_type'] ?? '');

        Log::info('PayPal webhook received', [
            'type'     => $type,
            'event_id' => $event['id'] ?? null,
        ]);

        if ($type === 'PAYMENT.CAPTURE.COMPLETED') {
            return ! $this->handleCaptureCompletedWebhook($event);
        }

        if ($type === 'CHECKOUT.ORDER.COMPLETED') {
            $paypalOrderId = $event['resource']['id'] ?? null;

            if ($paypalOrderId) {
                $retry = $this->recordFromPayPalOrderIdWebhook((string) $paypalOrderId, $event);

                return ! $retry;
            }

            return true;
        }

        if (in_array($type, [
            'PAYMENT.CAPTURE.DENIED',
            'PAYMENT.CAPTURE.DECLINED',
            'PAYMENT.CAPTURE.REVERSED',
            'CHECKOUT.ORDER.VOIDED',
            'CHECKOUT.PAYMENT-APPROVAL.REVERSED',
        ], true)) {
            try {
                $this->handleFailureWebhook($event);
            } catch (\Throwable $e) {
                Log::warning('PayPal failure webhook handler error', ['error' => $e->getMessage()]);
            }
        }

        return true;
    }

    /** @return bool True if webhook should be retried (HTTP 500). */
    private function handleCaptureCompletedWebhook(array $event): bool
    {
        $resource  = $event['resource'] ?? [];
        $amountVal = $resource['amount']['value'] ?? null;
        $currency  = $resource['amount']['currency_code'] ?? null;
        $captureId = $resource['id'] ?? null;

        if (! $captureId || ! $amountVal || ! $currency) {
            Log::warning('PayPal webhook missing capture fields');

            return false;
        }

        $meta = $this->extractMetaFromResource($resource);

        if (! $meta) {
            Log::warning('PayPal webhook missing metadata', ['capture' => $captureId]);

            return false;
        }

        $link = PaymentLink::withoutGlobalScopes()->find((int) $meta['payment_link_id']);

        if (! $link) {
            Log::warning('PayPal webhook: payment link not found', ['link_id' => $meta['payment_link_id']]);

            return false;
        }

        return $this->recordAndNotifyFromWebhook(
            link: $link,
            captureId: (string) $captureId,
            amountVal: (string) $amountVal,
            currency: (string) $currency,
            payload: $event,
            sessionId: $link->provider_session_id,
        );
    }

    private function recordFromCapturePayload(array $cap, string $paypalOrderId): void
    {
        $purchaseUnit = $cap['purchase_units'][0] ?? [];
        $capture      = $purchaseUnit['payments']['captures'][0] ?? null;

        if (! $capture || ($capture['status'] ?? '') !== 'COMPLETED') {
            Log::warning('PayPal capture payload incomplete; fetching order', [
                'paypal_order_id' => $paypalOrderId,
            ]);
            $this->recordFromPayPalOrderId($paypalOrderId, $cap);

            return;
        }

        $this->recordCapture(
            capture: $capture,
            purchaseUnit: $purchaseUnit,
            paypalOrderId: $paypalOrderId,
            payload: $cap,
        );
    }

    private function recordFromPayPalOrderId(string $paypalOrderId, array $payload = []): void
    {
        try {
            $ord = $this->api('GET', "/v2/checkout/orders/{$paypalOrderId}");
        } catch (\Throwable $e) {
            Log::error('PayPal GET order failed', [
                'paypal_order_id' => $paypalOrderId,
                'error'           => $e->getMessage(),
            ]);

            return;
        }

        $purchaseUnit = $ord['purchase_units'][0] ?? [];
        $capture      = $purchaseUnit['payments']['captures'][0] ?? null;

        if (! $capture || ($capture['status'] ?? '') !== 'COMPLETED') {
            Log::warning('PayPal order has no completed capture', [
                'paypal_order_id' => $paypalOrderId,
                'status'          => $ord['status'] ?? null,
            ]);

            return;
        }

        $this->recordCapture(
            capture: $capture,
            purchaseUnit: $purchaseUnit,
            paypalOrderId: $paypalOrderId,
            payload: $payload ?: $ord,
        );
    }

    /** @return bool True if webhook should be retried. */
    private function recordFromPayPalOrderIdWebhook(string $paypalOrderId, array $payload = []): bool
    {
        try {
            $ord = $this->api('GET', "/v2/checkout/orders/{$paypalOrderId}");
        } catch (\Throwable $e) {
            Log::error('PayPal webhook GET order failed', [
                'paypal_order_id' => $paypalOrderId,
                'error'           => $e->getMessage(),
            ]);

            return $this->isRetryableWebhookError($e);
        }

        $purchaseUnit = $ord['purchase_units'][0] ?? [];
        $capture      = $purchaseUnit['payments']['captures'][0] ?? null;

        if (! $capture || ($capture['status'] ?? '') !== 'COMPLETED') {
            Log::info('PayPal webhook: order has no completed capture yet', [
                'paypal_order_id' => $paypalOrderId,
                'status'          => $ord['status'] ?? null,
            ]);

            return false;
        }

        return $this->recordCaptureWebhook(
            capture: $capture,
            purchaseUnit: $purchaseUnit,
            paypalOrderId: $paypalOrderId,
            payload: $payload ?: $ord,
        );
    }

    private function recordCapture(array $capture, array $purchaseUnit, string $paypalOrderId, array $payload): void
    {
        $captureId = $capture['id'] ?? null;
        $amountVal = $capture['amount']['value'] ?? null;
        $currency  = $capture['amount']['currency_code'] ?? null;

        if (! $captureId || ! $amountVal || ! $currency) {
            Log::error('PayPal capture missing amount/currency');

            return;
        }

        $meta = $this->extractMetaFromResource(array_merge($capture, [
            'custom_id' => $capture['custom_id'] ?? ($purchaseUnit['custom_id'] ?? null),
        ]));

        if (! $meta) {
            $meta = $this->extractMetaFromPayPalOrder($paypalOrderId);
        }

        if (! $meta) {
            Log::warning('PayPal capture missing metadata; skipping record', [
                'paypal_order_id' => $paypalOrderId,
            ]);

            return;
        }

        $link = PaymentLink::withoutGlobalScopes()->find((int) $meta['payment_link_id']);

        if (! $link) {
            return;
        }

        $this->recordAndNotify(
            link: $link,
            captureId: (string) $captureId,
            amountVal: (string) $amountVal,
            currency: (string) $currency,
            payload: $payload,
            sessionId: $paypalOrderId,
        );
    }

    /** @return bool True if webhook should be retried. */
    private function recordCaptureWebhook(array $capture, array $purchaseUnit, string $paypalOrderId, array $payload): bool
    {
        $captureId = $capture['id'] ?? null;
        $amountVal = $capture['amount']['value'] ?? null;
        $currency  = $capture['amount']['currency_code'] ?? null;

        if (! $captureId || ! $amountVal || ! $currency) {
            Log::error('PayPal webhook capture missing amount/currency');

            return false;
        }

        $meta = $this->extractMetaFromResource(array_merge($capture, [
            'custom_id' => $capture['custom_id'] ?? ($purchaseUnit['custom_id'] ?? null),
        ]));

        if (! $meta) {
            $meta = $this->extractMetaFromPayPalOrder($paypalOrderId);
        }

        if (! $meta) {
            Log::warning('PayPal webhook capture missing metadata', [
                'paypal_order_id' => $paypalOrderId,
            ]);

            return false;
        }

        $link = PaymentLink::withoutGlobalScopes()->find((int) $meta['payment_link_id']);

        if (! $link) {
            return false;
        }

        return $this->recordAndNotifyFromWebhook(
            link: $link,
            captureId: (string) $captureId,
            amountVal: (string) $amountVal,
            currency: (string) $currency,
            payload: $payload,
            sessionId: $paypalOrderId,
        );
    }

    private function recordAndNotify(
        PaymentLink $link,
        string $captureId,
        string $amountVal,
        string $currency,
        array $payload,
        ?string $sessionId,
    ): void {
        try {
            [$payment, $order, $isNew] = $this->recorder->record(
                link: $link,
                provider: 'paypal',
                providerTxnId: $captureId,
                reportedAmountCents: $this->amountToCents($amountVal),
                reportedCurrency: $currency,
                payload: $payload,
                sessionId: $sessionId,
            );

            if ($isNew && $payment && $order) {
                DB::afterCommit(fn () => $this->recorder->sendInitialPaymentNotifications($payment, $order));
            }
        } catch (\Throwable $e) {
            Log::error('PayPal return: record failed (webhook may still process)', [
                'link_id' => $link->id,
                'txn_id'  => $captureId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /** @return bool True if webhook should be retried (HTTP 500). */
    private function recordAndNotifyFromWebhook(
        PaymentLink $link,
        string $captureId,
        string $amountVal,
        string $currency,
        array $payload,
        ?string $sessionId,
    ): bool {
        $outcome = $this->recorder->recordFromWebhook(
            link: $link,
            provider: 'paypal',
            providerTxnId: $captureId,
            reportedAmountCents: $this->amountToCents($amountVal),
            reportedCurrency: $currency,
            payload: $payload,
            sessionId: $sessionId,
        );

        if ($outcome->isNew && $outcome->payment && $outcome->order) {
            DB::afterCommit(fn () => $this->recorder->sendInitialPaymentNotifications(
                $outcome->payment,
                $outcome->order,
            ));
        }

        return $outcome->shouldRetryWebhook();
    }

    private function isRetryableWebhookError(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());

        return str_contains($msg, 'deadlock')
            || str_contains($msg, 'lock wait timeout')
            || str_contains($msg, 'timeout')
            || str_contains($msg, 'try again');
    }

    protected function api(string $method, string $path, ?array $json = null, array $extraHeaders = []): array
    {
        $token = $this->getAccessToken();

        $headers = array_merge([
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ], $this->formatHeaders($extraHeaders));

        $opts = [
            CURLOPT_URL            => $this->base . $path,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
        ];

        if ($json !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($json, JSON_UNESCAPED_SLASHES);
        }

        $ch = curl_init();
        curl_setopt_array($ch, $opts);

        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($err || $code < 200 || $code >= 300) {
            Log::error("PayPal API error HTTP {$code}", [
                'error'  => $err,
                'method' => $method,
                'path'   => $path,
                'body'   => is_string($resp) ? substr($resp, 0, 500) : null,
            ]);
            throw new \RuntimeException("PayPal API Error ({$code}): " . ($err ?: (string) $resp));
        }

        $decoded = json_decode((string) $resp, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('Invalid PayPal API response JSON.');
        }

        return $decoded;
    }

    protected function getAccessToken(): string
    {
        $cacheKey = 'paypal_token_' . sha1($this->clientId . '|' . $this->base);

        return Cache::remember($cacheKey, 300, function () {
            $auth = base64_encode("{$this->clientId}:{$this->secret}");

            $opts = [
                CURLOPT_URL            => $this->base . '/v1/oauth2/token',
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Basic ' . $auth,
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT        => 30,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, $opts);

            $resp = curl_exec($ch);
            $err  = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($err || $code < 200 || $code >= 300) {
                throw new \RuntimeException("PayPal token error ({$code}): " . ($err ?: (string) $resp));
            }

            $json = json_decode((string) $resp, true);
            $tok  = $json['access_token'] ?? null;

            if (! $tok) {
                throw new \RuntimeException('PayPal token missing.');
            }

            return (string) $tok;
        });
    }

    public function verifyWebhookSignature(string $payload, array $headers): bool
    {
        if (! $this->webhookId) {
            return false;
        }

        return $this->verifySignature($payload, $headers);
    }

    protected function verifySignature(string $payload, array $headers): bool
    {
        try {
            $h = $this->normalizeHeaders($headers);

            $body = [
                'auth_algo'         => $h['paypal-auth-algo'] ?? '',
                'cert_url'          => $h['paypal-cert-url'] ?? '',
                'transmission_id'   => $h['paypal-transmission-id'] ?? '',
                'transmission_sig'  => $h['paypal-transmission-sig'] ?? '',
                'transmission_time' => $h['paypal-transmission-time'] ?? '',
                'webhook_id'        => $this->webhookId,
                'webhook_event'     => json_decode($payload, true),
            ];

            $res = $this->api('POST', '/v1/notifications/verify-webhook-signature', $body);

            return (($res['verification_status'] ?? '') === 'SUCCESS');
        } catch (\Throwable $e) {
            Log::warning('PayPal webhook verification failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function extractApproveUrl(array $res): ?string
    {
        foreach ($res['links'] ?? [] as $l) {
            if (($l['rel'] ?? '') === 'approve' && ! empty($l['href'])) {
                return (string) $l['href'];
            }
        }

        return null;
    }

    private function extractMetaFromResource(array $resource): ?array
    {
        $custom = $resource['custom_id'] ?? null;

        if ($custom) {
            $meta = json_decode((string) $custom, true);
            if (is_array($meta) && isset($meta['order_id'], $meta['payment_link_id'])) {
                return $meta;
            }
        }

        $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;

        if ($paypalOrderId) {
            return $this->extractMetaFromPayPalOrder((string) $paypalOrderId);
        }

        return null;
    }

    private function extractMetaFromPayPalOrder(string $paypalOrderId): ?array
    {
        try {
            $ord    = $this->api('GET', "/v2/checkout/orders/{$paypalOrderId}");
            $custom = $ord['purchase_units'][0]['custom_id'] ?? null;

            if (! $custom) {
                return null;
            }

            $meta = json_decode((string) $custom, true);

            if (! is_array($meta) || ! isset($meta['order_id'], $meta['payment_link_id'])) {
                return null;
            }

            return $meta;
        } catch (\Throwable $e) {
            Log::warning('PayPal GET order failed for metadata', [
                'paypal_order_id' => $paypalOrderId,
                'error'           => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function handleFailureWebhook(array $event): void
    {
        $resource = $event['resource'] ?? [];
        $meta     = $this->extractMetaFromResource($resource);

        if (! $meta && isset($resource['id'])) {
            $meta = $this->extractMetaFromPayPalOrder((string) $resource['id']);
        }

        if (! $meta) {
            return;
        }

        $link = PaymentLink::withoutGlobalScopes()
            ->with('order.client')
            ->find((int) $meta['payment_link_id']);

        if (! $link?->order?->client?->email) {
            return;
        }

        $reason = $resource['status_details']['reason']
            ?? $event['summary']
            ?? 'Your PayPal payment could not be completed.';

        Notification::route('mail', $link->order->client->email)
            ->notify(new PaymentFailedNotification(
                order: $link->order,
                provider: 'paypal',
                reason: $reason,
                retryUrl: $link->last_issued_url,
            ));
    }

    private function notifyCaptureFailed(PaymentLink $link, string $reason): void
    {
        $email = $link->order?->client?->email;

        if (! $email) {
            return;
        }

        Notification::route('mail', $email)
            ->notify(new PaymentFailedNotification(
                order: $link->order,
                provider: 'paypal',
                reason: $reason,
                retryUrl: $link->last_issued_url,
            ));
    }

    private function isAlreadyCapturedError(\Throwable $e): bool
    {
        $message = strtoupper($e->getMessage());

        return str_contains($message, 'ORDER_ALREADY_CAPTURED')
            || str_contains($message, 'CAPTURE_ALREADY_COMPLETED');
    }

    private function formatPayPalAmount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function amountToCents(string $amount): int
    {
        if (function_exists('bcmul')) {
            return (int) bcmul(trim($amount), '100', 0);
        }

        return (int) round(((float) $amount) * 100);
    }

    private function normalizeHeaders(array $headers): array
    {
        $flat = [];

        foreach ($headers as $k => $v) {
            $key        = strtolower((string) $k);
            $flat[$key] = is_array($v) ? (string) ($v[0] ?? '') : (string) $v;
        }

        return $flat;
    }

    private function formatHeaders(array $headers): array
    {
        $out = [];

        foreach ($headers as $k => $v) {
            $out[] = $k . ': ' . $v;
        }

        return $out;
    }
}

// }

// }
