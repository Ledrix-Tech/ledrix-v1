<?php

namespace App\Support;

use App\Models\AccountKey;
use App\Models\Payment;
use App\Services\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Webhook;

class PpcWebhookVerifier
{
    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
    ) {}

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function verifyStripeEvent(Request $request): Event
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');

        if (! $sig) {
            Log::error('PPC Stripe webhook missing Stripe-Signature header');
            abort(400, 'Missing Stripe-Signature header');
        }

        $secrets = AccountKey::withoutGlobalScopes()
            ->where('module', 'ppc')
            ->where('status', 'active')
            ->whereNotNull('stripe_webhook_secret')
            ->pluck('stripe_webhook_secret')
            ->unique()
            ->filter()
            ->values();

        $global = config('services.stripe.webhook_secret');
        if ($global) {
            $secrets->push($global);
        }

        if ($secrets->isEmpty()) {
            Log::error('PPC Stripe webhook: no webhook secrets configured');
            abort(500, 'Webhook secret missing');
        }

        foreach ($secrets as $secret) {
            try {
                return Webhook::constructEvent($payload, $sig, $secret);
            } catch (\Throwable) {
                continue;
            }
        }

        Log::error('PPC Stripe webhook signature verification failed for all configured secrets');
        abort(400, 'Invalid signature');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function verifyPayPalEvent(Request $request): array
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();
        $event   = json_decode($payload, true);

        if (! is_array($event)) {
            Log::warning('PPC PayPal webhook invalid JSON');
            abort(400, 'Invalid payload');
        }

        if (! app()->environment('production') && ! $this->hasAnyPaypalWebhookId()) {
            Log::warning('PPC PayPal webhook_id not configured — skipping verification (non-production)');

            return $event;
        }

        $candidates = AccountKey::withoutGlobalScopes()
            ->with('brand')
            ->where('module', 'ppc')
            ->where('status', 'active')
            ->whereNotNull('paypal_webhook_id')
            ->get();

        foreach ($candidates as $keys) {
            if (! $keys->brand) {
                continue;
            }

            try {
                $gateway = $this->gatewayFactory->forProviderWithBrand('paypal', $keys->brand);

                if ($gateway->verifyWebhookSignature($payload, $headers)) {
                    return $event;
                }
            } catch (\Throwable $e) {
                Log::debug('PPC PayPal webhook verify attempt failed', [
                    'brand_id' => $keys->brand_id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        if (config('services.paypal.webhook_id')) {
            try {
                $gateway = $this->gatewayFactory->forProvider('paypal');

                if ($gateway->verifyWebhookSignature($payload, $headers)) {
                    return $event;
                }
            } catch (\Throwable $e) {
                Log::debug('PPC PayPal env webhook verify failed', ['error' => $e->getMessage()]);
            }
        }

        Log::error('PPC PayPal webhook signature invalid', [
            'event_type' => $event['event_type'] ?? null,
            'event_id'   => $event['id'] ?? null,
        ]);
        abort(400, 'Invalid signature');
    }

    public function extractPaypalCaptureIdFromRefund(array $resource): ?string
    {
        foreach ($resource['links'] ?? [] as $link) {
            $href = $link['href'] ?? '';
            $rel  = $link['rel'] ?? '';

            if ($rel === 'up' && str_contains($href, '/captures/')) {
                $id = basename(parse_url($href, PHP_URL_PATH) ?: '');

                return $id !== '' ? $id : null;
            }
        }

        if (! empty($resource['sale_id'])) {
            return (string) $resource['sale_id'];
        }

        return null;
    }

    public function resolveBrandIdFromStripeEvent(Event $event): ?int
    {
        $paymentIntentId = $this->extractStripePaymentIntentId($event);

        if (! $paymentIntentId) {
            return null;
        }

        $brandId = Payment::withoutGlobalScopes()
            ->where('provider', 'stripe')
            ->where('provider_payment_intent_id', $paymentIntentId)
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->value('orders.brand_id');

        return $brandId ? (int) $brandId : null;
    }

    private function extractStripePaymentIntentId(Event $event): ?string
    {
        $object = $event->data->object ?? null;

        if (! $object) {
            return null;
        }

        if ($event->type === 'charge.refunded') {
            return isset($object->payment_intent) ? (string) $object->payment_intent : null;
        }

        if ($event->type === 'charge.refund.updated') {
            return isset($object->payment_intent) ? (string) $object->payment_intent : null;
        }

        if (str_starts_with($event->type, 'charge.dispute.')) {
            // Dispute payload does not include payment_intent — resolved via charge lookup in processor.
            return null;
        }

        return isset($object->payment_intent) ? (string) $object->payment_intent : null;
    }

    private function hasAnyPaypalWebhookId(): bool
    {
        if (config('services.paypal.webhook_id')) {
            return true;
        }

        return AccountKey::withoutGlobalScopes()
            ->where('module', 'ppc')
            ->where('status', 'active')
            ->whereNotNull('paypal_webhook_id')
            ->exists();
    }
    public static function isDuplicate(string $provider, string $eventId): bool
    {
        return ! Cache::add("webhook:ppc:{$provider}:{$eventId}", 1, now()->addDays(7));
    }

    public static function releaseDuplicateLock(string $provider, string $eventId): void
    {
        Cache::forget("webhook:ppc:{$provider}:{$eventId}");
    }
}
