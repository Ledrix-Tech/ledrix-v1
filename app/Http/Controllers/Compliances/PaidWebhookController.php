<?php

namespace App\Http\Controllers\Compliances;

use App\Models\AccountKey;
use App\Models\PaymentLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use App\Services\PaymentGatewayFactory;

class PaidWebhookController extends Controller
{
    public function showPaymentPage(Request $request, string $token)
    {
        // Signature / expiry
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired link.');
        }
        // Load DB row (revocation/expiry lives here)
        $link = PaymentLink::with(['brand', 'lead', 'order'])
            ->where('token', $token)->firstOrFail();
        if (! $link->isActiveLink()) {
            return response()->view('errors.payLink-error', [
                'message' => 'This payment link is not active.'
            ], 410);
            // abort(410, 'This payment link is not active.');
        }
        // Decrypt + validate payload (optional but nice for prefill)
        $p = $request->query('p');
        try {
            $data = $p ? json_decode(Crypt::decryptString($p), true, 512, JSON_THROW_ON_ERROR) : [];
        } catch (\Throwable $e) {
            abort(404, 'Invalid payload.');
        }
        // Cross-check critical fields so URL can’t lie
        if (($data['t'] ?? null) !== $link->token ||
            (int)($data['a'] ?? -1) !== (int)$link->unit_amount ||
            strtoupper($data['c'] ?? '') !== $link->currency
        ) {
            abort(400, 'Payload mismatch.');
        }
        // Render page (or create Stripe checkout here)
        // dd([
        //     'brand'   => $link->brand,
        //     'service' => $link->service_name,
        //     'amount'  => $link->unit_amount,
        //     'currency' => $link->currency,
        //     'order'   => $link->order,
        //     'lead'    => $link->lead,
        //     'token'   => $link->token,
        //     'link'   => $link,
        //     'client' =>$link->client
        // ]);
        return view('generated-link-page', [
            'brand'   => $link->brand,
            'service' => $link->service_name,
            'amount'  => $link->unit_amount,
            'currency' => $link->currency,
            'order'   => $link->order,
            'lead'    => $link->lead,
            'token'   => $link->token,
            'link'   => $link,
            'client' => $link->client
        ]);
    }

    public function createCheckout(Request $request, string $token, PaymentGatewayFactory $factory)
    {
        $link = PaymentLink::with(['order', 'lead'])->where('token', $token)->firstOrFail();
        if (! $link->isActiveLink()) {
            return response()->view('errors.payLink-error', [
                'message' => 'This payment link is not active.'
            ], 410);
            // abort(410, 'This payment link is not active.');
        }

        $buyer = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string', 'max:255'],
            // 'city'       => ['nullable', 'string', 'max:255'],
            // 'state'      => ['nullable', 'string', 'max:255'],
            // 'zip'        => ['nullable', 'string', 'max:30'],
            // 'country'    => ['nullable', 'string', 'max:255'],
        ]);
        // dd($request->all(), $link);

        $brand = $link->brand ?? $link->order->brand ?? null;
        abort_if(!$brand, 500, 'Missing brand information.');

        // ✅ Load the keys from DB
        $keys = AccountKey::where('brand_id', $brand->id)
            ->where('status', 'active')
            ->first();
        // dd($keys);
        if (!$keys) {
            return response()->json(['error' => 'Stripe or PayPal keys are missing for this brand'], 500);
        }
        // ✅ Optional: log or preview keys
        Log::info('Stripe and PayPal keys loaded for brand', [
            'brand_id' => $brand->id,
            'stripe_key' => substr($keys->stripe_secret_key, 0, 6) . '****',
            'paypal_secret' => substr($keys->paypal_secret, 0, 6) . '****'
        ]);
        $gateway = $factory->forProviderWithBrand($link->provider, $brand);   // 'stripe' or 'paypal'
        $checkout = $gateway->createCheckout($link, $buyer); // returns ['id'=>..., 'url'=>...]
        // dd($gateway,$checkout);
        // (optional) persist gateway session ID if you want:
        if (!empty($checkout['id'])) {
            if ($link->provider === 'stripe') {
                $link->update(['provider_session_id' => $checkout['id']]);
                $link->order?->update(['provider_session_id' => $checkout['id']]);
            } else {
                $link->update(['provider_session_id' => $checkout['id']]); // add column if you want
            }
        }

        return redirect()->away($checkout['url']);
        // $session = $stripe->createCheckout($link, $buyer);
        // return redirect()->away($session['url']);
    }

    public function checkoutSuccess(Request $request, string $token, PaymentGatewayFactory $factory)
    {
        $link = PaymentLink::with(['order', 'brand'])->where('token', $token)->firstOrFail();
        $brand = $link->brand ?? $link->order?->brand;

        abort_if(!$brand, 500, 'Missing brand info');

        // Get the session id depending on the provider
        $sessionId = null;
        if ($link->provider === 'stripe') {
            $sessionId = $request->query('session_id');
        } elseif ($link->provider === 'paypal') {
            $sessionId = $request->query('token'); // PayPal Order ID
        }

        // ✅ Always use the factory to inject correct keys from DB
        $gateway = $factory->forProviderWithBrand($link->provider, $brand);
        $gateway->handleCheckoutSuccess($link, $sessionId);

        // Refresh for view
        $link->refresh()->load('order');
        return view('paid-success', ['link' => $link, 'order' => $link->order]);
    }

    public function checkoutCancel(string $token)
    {
        $link = PaymentLink::where('token', $token)->firstOrFail();
        return view('paid-cancel', ['link' => $link, 'order' => $link->order]);
    }

    public function checkoutError(string $token)
    {
        $link = PaymentLink::where('token', $token)->first();
        return view('paid-error', ['link' => $link, 'order' => $link->order]);
    }

    // without refund logic
    public function handleWebhook(Request $request, PaymentGatewayFactory $factory, string $provider)
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();
        // Try decode brand id
        $temp = json_decode($payload, true);
        $brandId = $temp['data']['object']['metadata']['brand_id'] ?? null;

        if ($brandId) {
            $accountKey = AccountKey::where('brand_id', $brandId)->first();
            if ($accountKey) {
                $gateway = $factory->forProviderWithBrand($provider, $accountKey->brand);
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
