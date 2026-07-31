<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\PaymentLink;
use App\Notifications\PaymentFailedNotification;
use App\Services\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class WebhookController extends Controller
{
    public function showPaymentPage(Request $request, string $token)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired link.');
        }

        $link = PaymentLink::withoutGlobalScopes()
            ->with(['brand', 'lead', 'order', 'client'])
            ->where('token', $token)
            ->firstOrFail();

        if (! $link->isActiveLink()) {
            return response()->view('errors.payLink-error', [
                'message' => 'This payment link has expired or is no longer active.',
            ], 410);
        }

        $p = $request->query('p');

        try {
            $data = $p ? json_decode(Crypt::decryptString($p), true, 512, JSON_THROW_ON_ERROR) : [];
        } catch (\Throwable $e) {
            abort(404, 'Invalid payload.');
        }

        if (($data['t'] ?? null) !== $link->token
            || (int) ($data['a'] ?? -1) !== (int) $link->unit_amount
            || strtoupper($data['c'] ?? '') !== $link->currency
        ) {
            abort(400, 'Payload mismatch.');
        }

        return view('generated-link-page', [
            'brand'    => $link->brand,
            'service'  => $link->service_name,
            'amount'   => $link->unit_amount,
            'currency' => $link->currency,
            'order'    => $link->order,
            'lead'     => $link->lead,
            'token'    => $link->token,
            'link'     => $link,
            'client'   => $link->client,
        ]);
    }

    public function createCheckout(Request $request, string $token, PaymentGatewayFactory $factory)
    {
        $buyer = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = DB::transaction(function () use ($token, $buyer, $factory) {
                $link = PaymentLink::withoutGlobalScopes()
                    ->with(['order.brand', 'lead', 'brand', 'client'])
                    ->where('token', $token)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $link->isActiveLink()) {
                    return ['type' => 'inactive', 'link' => $link];
                }

                $brand = $link->brand ?? $link->order?->brand;

                if (! $brand) {
                    throw new \RuntimeException('Missing brand information for payment link.');
                }

                $provider = strtolower((string) $link->provider);

                if (! in_array($provider, ['stripe', 'paypal'], true)) {
                    throw new \InvalidArgumentException('Unsupported or missing payment provider.');
                }

                $gateway  = $factory->forProviderWithBrand($provider, $brand);
                $checkout = $gateway->createCheckout($link, $buyer);

                if (empty($checkout['url'])) {
                    throw new \RuntimeException('Gateway did not return a checkout URL.');
                }

                if (! empty($checkout['id'])) {
                    $link->provider_session_id = $checkout['id'];
                    $link->save();
                    $link->order?->update(['provider_session_id' => $checkout['id']]);
                }

                return ['type' => 'redirect', 'url' => $checkout['url']];
            });

            if ($result['type'] === 'inactive') {
                return response()->view('errors.payLink-error', [
                    'message' => 'This payment link has expired or is no longer active.',
                ], 410);
            }

            return redirect()->away($result['url']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->view('errors.payLink-error', [
                'message' => 'Payment link not found.',
            ], 404);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe createCheckout failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->view('errors.payLink-error', [
                'message' => 'Payment provider is temporarily unavailable. Please try again.',
            ], 502);
        } catch (\Throwable $e) {
            Log::error('createCheckout failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->view('errors.payLink-error', [
                'message' => 'Unable to start checkout right now. Please try again.',
            ], 500);
        }
    }

    public function checkoutSuccess(Request $request, string $token, PaymentGatewayFactory $factory)
    {
        $link = PaymentLink::withoutGlobalScopes()
            ->with(['order', 'brand'])
            ->where('token', $token)
            ->firstOrFail();

        $brand = $link->brand ?? $link->order?->brand;
        abort_if(! $brand, 500, 'Missing brand info');

        $sessionId = $link->provider === 'stripe'
            ? $request->query('session_id')
            : $request->query('token');

        $gateway = $factory->forProviderWithBrand($link->provider, $brand);
        $gateway->handleCheckoutSuccess($link, $sessionId);

        $link->refresh()->load(['order', 'brand']);

        return view('paid-success', [
            'link'  => $link,
            'order' => $link->order,
            'brand' => $brand,
        ]);
    }

    public function checkoutCancel(Request $request, string $token)
    {
        $link = PaymentLink::withoutGlobalScopes()
            ->with(['order.client', 'brand'])
            ->where('token', $token)
            ->firstOrFail();

        $order = $link->order;

        if ($request->query('canceled') == 1 && $order?->client?->email) {
            $provider = $link->provider ?: 'unknown';

            Notification::route('mail', $order->client->email)
                ->notify(new PaymentFailedNotification(
                    order: $order,
                    provider: $provider,
                    reason: $provider === 'paypal'
                        ? 'You cancelled the PayPal payment.'
                        : 'You cancelled the checkout before completing payment.',
                    retryUrl: $link->last_issued_url,
                ));
        }

        return view('paid-cancel', [
            'link'  => $link,
            'order' => $order,
            'brand' => $link->brand,
        ]);
    }

    public function checkoutError(string $token)
    {
        $link = PaymentLink::withoutGlobalScopes()
            ->with(['order', 'brand'])
            ->where('token', $token)
            ->first();

        return view('paid-error', [
            'link'    => $link,
            'order'   => $link?->order,
            'brand'   => $link?->brand,
            'message' => 'Something went wrong while processing your payment.',
        ]);
    }

    public function handleWebhook(Request $request, PaymentGatewayFactory $factory, string $provider)
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();

        try {
            $brand = $this->resolveBrandFromWebhook($provider, $payload);

            if ($brand) {
                if ($brand->tenant_id && ! app(\App\Services\Tenant\TenantFeatureService::class)->enabled('webhooks', (int) $brand->tenant_id)) {
                    Log::info('Webhook ignored — plan excludes payment webhooks', [
                        'provider'  => $provider,
                        'brand_id'  => $brand->id,
                        'tenant_id' => $brand->tenant_id,
                    ]);

                    return response()->json(['ok' => true, 'skipped' => 'plan'], 200);
                }

                try {
                    $gateway = $factory->forProviderWithBrand($provider, $brand);
                } catch (\Throwable $e) {
                    Log::warning('Webhook: brand keys unavailable, falling back to env', [
                        'provider' => $provider,
                        'brand_id' => $brand->id,
                        'error'    => $e->getMessage(),
                    ]);
                    $gateway = $factory->forProvider($provider);
                }
            } else {
                Log::info('Webhook: brand not resolved, using env keys', ['provider' => $provider]);
                $gateway = $factory->forProvider($provider);
            }

            Log::info("Incoming webhook for provider: {$provider}");

            $ok = $gateway->handleWebhook($payload, $headers);
        } catch (\Throwable $e) {
            Log::error('Webhook handler crashed', [
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);
            $ok = false;
        }

        return response()->json(['ok' => (bool) $ok], $ok ? 200 : 500);
    }

    private function resolveBrandFromWebhook(string $provider, string $payload): ?Brand
    {
        $data = json_decode($payload, true);

        if (! is_array($data)) {
            return null;
        }

        $brandId = null;

        if ($provider === 'stripe') {
            $brandId = $data['data']['object']['metadata']['brand_id'] ?? null;
        }

        if ($provider === 'paypal') {
            $resource = $data['resource'] ?? [];
            $custom   = $resource['custom_id'] ?? null;

            if ($custom) {
                $meta = json_decode((string) $custom, true);
                $brandId = $meta['brand_id'] ?? null;

                if (! $brandId && isset($meta['payment_link_id'])) {
                    $brandId = PaymentLink::withoutGlobalScopes()
                        ->where('id', (int) $meta['payment_link_id'])
                        ->value('brand_id');
                }
            }

            if (! $brandId) {
                $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id']
                    ?? $resource['id']
                    ?? null;

                if ($paypalOrderId) {
                    $brandId = PaymentLink::withoutGlobalScopes()
                        ->where('provider_session_id', $paypalOrderId)
                        ->value('brand_id');
                }
            }
        }

        if (! $brandId) {
            return null;
        }

        return Brand::withoutGlobalScopes()->find((int) $brandId);
    }
}
