<?php

namespace App\Http\Controllers\Upwork;

use App\Models\AccountKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use App\Models\Upwork\UpworkPaymentLink;
use Illuminate\Support\Facades\Notification;
use App\Services\Upwork\UpworkPaymentGatewayFactory;
use App\Notifications\PaymentFailedNotification;

class WebhookController extends Controller
{
    public function showUpworkPaymentPage(Request $request, string $token)
    {
        // 1) Signature / expiry (URL signature)
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired link.');
        }

        // 2) Load Upwork link + related order/client
        $link = UpworkPaymentLink::with(['client', 'order'])
            ->where('token', $token)
            ->firstOrFail();

        // 3) DB-level validity checks
        if (! $this->isUpworkLinkActive($link)) {
            return response()->view('errors.payLink-error', [
                'message' => 'This payment link is not active.'
            ], 410);
        }

        // 4) Decrypt + validate payload (optional but recommended)
        $p = $request->query('p');
        try {
            $data = $p ? json_decode(Crypt::decryptString($p), true, 512, JSON_THROW_ON_ERROR) : [];
        } catch (\Throwable $e) {
            abort(404, 'Invalid payload.');
        }

        // 5) Cross-check payload so URL can’t lie
        if (
            ($data['t'] ?? null) !== $link->token ||
            (int)($data['a'] ?? -1) !== (int)$link->unit_amount ||
            strtoupper($data['c'] ?? '') !== strtoupper($link->currency)
        ) {
            abort(400, 'Payload mismatch.');
        }

        // 6) Render Upwork payment page
        return view('up-payment-page', [
            'service'  => $link->service_name,
            'amount'   => $link->unit_amount,
            'brand'   => $link->brand,
            'currency' => $link->currency,
            'order'    => $link->order,
            'client'   => $link->client,
            'token'    => $link->token,
            'link'     => $link,
        ]);
    }

    private function isUpworkLinkActive(UpworkPaymentLink $link): bool
    {
        // Must be active flag + status not paid/canceled/expired
        if (! (bool) $link->is_active_link) return false;
        if (in_array($link->status, ['paid', 'completed', 'canceled', 'expired'], true)) return false;

        // Expiry check: your migration currently stores expires_at as string (bad)
        // This makes it tolerant: if it's parseable date/time, enforce it.
        if (!empty($link->expires_at)) {
            try {
                $expiresAt = \Carbon\Carbon::parse($link->expires_at);
                if (now()->greaterThanOrEqualTo($expiresAt)) return false;
            } catch (\Throwable $e) {
                // If expires_at is garbage, treat as expired (safer)
                return false;
            }
        }

        return true;
    }

    public function createCheckout(Request $request, string $token, UpworkPaymentGatewayFactory $factory)
    {
        $link = UpworkPaymentLink::with(['order.brand', 'client'])
            ->where('token', $token)
            ->firstOrFail();

        if (! $link->isActiveLink()) {
            return response()->view('errors.payLink-error', [
                'message' => 'This payment link is not active.'
            ], 410);
        }

        $buyer = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string', 'max:255'],
        ]);

        $order = $link->order;
        abort_if(!$order, 500, 'Missing order.');
        $brand = $order->brand;
        abort_if(!$brand, 500, 'Missing brand (merchant account).');

        // ✅ Now brand-based keys are valid
        $gateway  = $factory->forProviderWithBrand($link->provider, $brand, 'upwork');
        $checkout = $gateway->createCheckout($link, $buyer);

        if (!empty($checkout['id'])) {
            $link->update(['provider_session_id' => $checkout['id']]);
            $order->update(['provider_session_id' => $checkout['id']]);
        }

        return redirect()->away($checkout['url']);
    }

    public function checkoutSuccess(Request $request, string $token, UpworkPaymentGatewayFactory $factory)
    {
        $link = UpworkPaymentLink::with(['order.brand', 'client'])
            ->where('token', $token)
            ->firstOrFail();

        $order = $link->order;
        abort_if(!$order, 500, 'Missing order.');

        $brand = $order->brand;
        abort_if(!$brand, 500, 'Missing brand.');

        $sessionId = match ($link->provider) {
            'stripe' => $request->query('session_id'),
            'paypal' => $request->query('token'),
            default  => null,
        };

        $gateway = $factory->forProviderWithBrand($link->provider, $brand, 'upwork');
        $gateway->handleCheckoutSuccess($link, $sessionId);

        // $link->refresh()->load('order');
        $link->loadMissing('order.brand');
        $brand = $link->order?->brand;
        return view('paid-success', ['link' => $link, 'order' => $link->order, 'brand' => $brand]);
    }

    public function checkoutCancel(Request $request, string $token)
    {
        $link = UpworkPaymentLink::where('token', $token)->firstOrFail();
        $order = $link->order;

        if ($request->query('canceled') == 1) {

            // Detect provider
            $provider = null;

            if (str_starts_with($link->provider_session_id, 'cs_')) {
                $provider = 'stripe';
            } elseif (preg_match('/^[A-Z0-9]{17}$/', $link->provider_session_id)) {
                $provider = 'paypal';
            } else {
                $provider = 'unknown';
            }

            $reason = $provider === 'stripe'
                ? 'You cancelled the Stripe checkout.'
                : 'You cancelled the PayPal payment.';

            Notification::route('mail', $order->client->email)
                ->notify(
                    (new PaymentFailedNotification(
                        $order,
                        $provider,
                        $reason,
                        $link->last_issued_url
                    ))->delay(now()->addSeconds(3))
                );

            return view('paid-cancel', compact('link', 'order'));
        }

        return view('paid-cancel', compact('link', 'order'));
    }

    public function checkoutError(string $token)
    {
        $link = UpworkPaymentLink::where('token', $token)->first();
        return view('paid-error', ['link' => $link, 'order' => $link->order]);
    }

    // without refund logic
    public function handleWebhook(Request $request, UpworkPaymentGatewayFactory $factory, string $provider)
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();
        // Try decode brand id
        $temp = json_decode($payload, true);
        $brandId = $temp['data']['object']['metadata']['brand_id'] ?? null;

        if ($brandId) {
            $accountKey = AccountKey::where('brand_id', $brandId)->first();
            if ($accountKey) {
                $gateway = $factory->forProviderWithBrand($provider, $accountKey->brand, 'upwork');
            } else {
                // fallback
                $gateway = $factory->forProvider($provider);
            }
        } else {
            $gateway = $factory->forProvider($provider);
        }

        Log::info("Incoming webhook for provider: {$provider}");
        $ok = $gateway->handleWebhook($payload, $headers);
        return response()->json(['ok' => (bool)$ok]);
    }
}
