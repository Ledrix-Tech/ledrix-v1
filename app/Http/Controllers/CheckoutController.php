<?php

namespace App\Http\Controllers;

use App\Models\AccountKey;
use App\Models\Brand;
use App\Models\Lead;
use App\Models\Order;
use App\Models\PaymentLink;
use App\Models\Seller;
use App\Notifications\PaymentLinkNotification;
use App\Services\PaymentGatewayFactory;
use App\Services\PaymentLinkService;
use App\Services\Tenant\TenantFeatureService;
use App\Services\PayPalGateway;
use App\Services\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function generateLinkForm(Request $request, Brand $brand, Lead $lead, ?Order $order = null)
    {
        $seller = auth('seller')->user();
        $admin  = auth('admin')->user();
        $actor  = $admin ?: $seller;

        abort_unless($actor, 403, 'Unauthorized.');
        Gate::forUser($actor)->authorize('createPaymentLink', $lead);

        if ($seller) {
            app(TenantFeatureService::class)->assertAnyEnabled(['stripe', 'paypal']);
        }

        abort_unless((int) $lead->brand_id === (int) $brand->id, 404, 'Lead/brand mismatch.');

        $orderTypeParam = $request->get('type');
        $isRenewalFlow = $orderTypeParam === 'renewal' || ($order && $order->order_type === 'renewal');

        if ($order) {
            if (
                (int) $order->brand_id !== (int) $brand->id
                || (int) $order->client_id !== (int) $lead->client_id
            ) {
                $redirectRoute = $seller ? 'seller.orders.get' : 'admin.orders.get';

                return redirect()
                    ->route($redirectRoute)
                    ->with('error', 'Order does not belong to this lead/brand.');
            }

            // Paid original → dedicated renewal form (creates/reuses renewal order).
            if (
                $order->order_type === 'original'
                && (int) $order->balance_due === 0
                && $orderTypeParam === 'renewal'
            ) {
                return redirect()->route('renew-order-link', [
                    'brand' => $brand->id,
                    'lead'  => $lead->id,
                    'order' => $order->id,
                    'type'  => 'renewal',
                ]);
            }

            if ((int) $order->balance_due <= 0 && ! $isRenewalFlow) {
                $redirectRoute = $seller ? 'seller.orders.get' : 'admin.orders.get';

                return redirect()
                    ->route($redirectRoute)
                    ->with('info', 'Order is already fully paid.');
            }
        }

        $providerAvailability = $this->providerAvailabilityForBrand($brand);

        if ($seller) {
            return view('sellers.pages.generate-payment-link', array_merge(
                compact('brand', 'lead', 'order'),
                $providerAvailability
            ));
        }

        return view('admin.pages.generate-payment-link', array_merge(
            compact('brand', 'lead', 'order'),
            $providerAvailability
        ));
    }

    public function renewOrderLink(Request $request, Brand $brand, Lead $lead, ?Order $order = null)
    {
        $seller = auth('seller')->user();
        $admin  = auth('admin')->user();
        $actor  = $admin ?: $seller;

        abort_unless($actor, 403, 'Unauthorized.');
        Gate::forUser($actor)->authorize('createPaymentLink', $lead);

        if ($seller) {
            app(TenantFeatureService::class)->assertAnyEnabled(['stripe', 'paypal']);
        }

        $orderType = $request->get('type', 'renewal');

        abort_unless((int) $lead->brand_id === (int) $brand->id, 404, 'Lead/brand mismatch.');

        if ($order) {
            if (
                (int) $order->brand_id !== (int) $brand->id
                || (int) $order->client_id !== (int) $lead->client_id
            ) {
                $redirectRoute = $seller ? 'seller.orders.get' : 'admin.orders.get';

                return redirect()
                    ->route($redirectRoute)
                    ->with('error', 'Order does not belong to this lead/brand.');
            }

            if ($order->order_type === 'original' && (int) $order->balance_due > 0) {
                return redirect()
                    ->back()
                    ->with('error', 'Original order must be fully paid before creating a renewal.');
            }

            // Milestone on an existing renewal order → standard generate form.
            if ($order->order_type === 'renewal' && (int) $order->balance_due > 0) {
                return redirect()->route('generate-link-form', [
                    'brand' => $brand->id,
                    'lead'  => $lead->id,
                    'order' => $order->id,
                    'type'  => 'renewal',
                ]);
            }
        }

        $providerAvailability = $this->providerAvailabilityForBrand($brand);

        if ($seller) {
            return view('sellers.pages.renew-payment-link', array_merge(
                compact('brand', 'lead', 'order', 'orderType'),
                $providerAvailability
            ));
        }

        return view('admin.pages.renew-payment-link', array_merge(
            compact('brand', 'lead', 'order', 'orderType'),
            $providerAvailability
        ));
    }

    /**
     * Plan feature + brand merchant keys. Both required before offering a provider.
     *
     * @return array{
     *     tenantHasStripe: bool,
     *     tenantHasPayPal: bool,
     *     brandHasMerchant: bool,
     *     planHasStripe: bool,
     *     planHasPayPal: bool
     * }
     */
    protected function providerAvailabilityForBrand(Brand $brand): array
    {
        $tenantId = (int) ($brand->tenant_id ?? 0) ?: null;
        $features = app(TenantFeatureService::class);
        $gateways = app(PaymentGatewayFactory::class);

        $planHasStripe = $features->enabled('stripe', $tenantId);
        $planHasPayPal = $features->enabled('paypal', $tenantId);
        $brandHasStripe = $gateways->brandHasProvider($brand, 'stripe', 'ppc');
        $brandHasPayPal = $gateways->brandHasProvider($brand, 'paypal', 'ppc');

        return [
            'planHasStripe'    => $planHasStripe,
            'planHasPayPal'    => $planHasPayPal,
            'brandHasMerchant' => $brandHasStripe || $brandHasPayPal,
            'tenantHasStripe'  => $planHasStripe && $brandHasStripe,
            'tenantHasPayPal'  => $planHasPayPal && $brandHasPayPal,
        ];
    }

    protected function sellerOwnsLead(Seller $seller, Lead $lead, Brand $brand): bool
    {
        return (int) $seller->id === (int) $lead->seller_id
            && (int) $seller->brand_id === (int) $brand->id;
    }

    public function generatePayLink(Request $request, Brand $brand, Lead $lead, PaymentLinkService $links)
    {
        $seller = auth('seller')->user();
        $admin  = auth('admin')->user();
        $actor  = $admin ?: $seller;

        abort_unless($actor, 403, 'Unauthorized.');
        Gate::forUser($actor)->authorize('createPaymentLink', $lead);

        if ($seller) {
            app(TenantFeatureService::class)->assertAnyEnabled(['stripe', 'paypal']);
        }

        abort_unless((int) $lead->brand_id === (int) $brand->id, 404, 'Lead/brand mismatch.');

        $data = $request->validate([
            'service_name'     => ['required', 'string', 'max:255'],
            'currency'         => ['required', 'string', 'size:3'],
            'total_amount'     => ['required', 'numeric', 'gt:0'],
            'payable_amount'   => ['required', 'numeric', 'gt:0'],
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'provider'         => ['required', Rule::in(['stripe', 'paypal'])],
            'order_type'       => ['nullable', Rule::in(['original', 'renewal'])],
            'base_order_id'    => ['nullable', 'integer', 'exists:orders,id'],
        ]);

        if (! app(PaymentGatewayFactory::class)->brandHasProvider($brand, $data['provider'], 'ppc')) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'No active '.$data['provider'].' merchant is configured for this brand. Add Payment Accounts before generating a link.'
                );
        }

        if ((float) $data['payable_amount'] > (float) $data['total_amount']) {
            return back()->withInput()->with('error', 'Payable amount cannot exceed total amount.');
        }

        $payNowCents = (int) round(((float) $data['payable_amount']) * 100);
        $totalCents  = (int) round(((float) $data['total_amount']) * 100);

        if ($payNowCents < $totalCents) {
            $tenantId = (int) ($brand->tenant_id ?? 0);

            if (! app(TenantFeatureService::class)->enabled('milestone_payments', $tenantId ?: null)) {
                return back()
                    ->withInput()
                    ->with('error', 'Milestone payments are not enabled. Pay now amount must equal the total amount.');
            }
        }

        $actor = $admin ?: $seller;
        abort_unless($actor, 403, 'Unauthorized actor.');

        $orderType = $data['order_type'] ?? 'original';

        // ✅ baseOrderId resolution (ONLY for renewal)
        $baseOrderId = null;
        if ($orderType === 'renewal') {
            // Priority: form field -> route param -> request->order
            $baseOrderId = (int)($data['base_order_id'] ?? 0);

            if ($baseOrderId <= 0) {
                $baseOrderId = (int)($request->route('order') ?? 0);
            }
            if ($baseOrderId <= 0) {
                $baseOrderId = (int)($request->order ?? 0);
            }

            abort_unless($baseOrderId > 0, 422, 'Order id is required for renewal.');

            // ✅ Sanity check: belongs to this lead + brand
            $base = Order::query()->select(['id', 'lead_id', 'brand_id'])->findOrFail($baseOrderId);
            abort_unless((int)$base->lead_id === (int)$lead->id, 422, 'Order does not belong to this lead.');
            abort_unless((int)$base->brand_id === (int)$brand->id, 422, 'Order does not belong to this brand.');
        }

        try {
            [$link, $url] = DB::transaction(function () use ($links, $brand, $lead, $seller, $actor, $data, $orderType, $baseOrderId) {

                $link = $links->createInstallmentLink(
                    brand: $brand,
                    lead: $lead,
                    sellerIdWhoGenerated: (int)($seller->id ?? $lead->seller_id),
                    serviceName: $data['service_name'],
                    currency: strtoupper($data['currency']),
                    totalCents: (int) round(((float)$data['total_amount']) * 100),
                    payNowCents: (int) round(((float)$data['payable_amount']) * 100),
                    expiresInHours: $data['expires_in_hours'] ?? null,
                    provider: $data['provider'],
                    orderType: $orderType,
                    baseOrderId: $baseOrderId, // ✅ IMPORTANT
                    meta: [
                        'generated_by_id'   => $actor->id,
                        'generated_by_type' => $actor instanceof \App\Models\Admin ? 'admin' : 'seller',
                    ],
                );

                $url = $link->signedUrl();

                $link->update([
                    'last_issued_url'        => $url,
                    'last_issued_at'         => now(),
                    'last_issued_expires_at' => $link->expires_at ?? now()->addDays(7),
                    'generated_by_id'        => $actor->id,
                    'generated_by_type'      => $actor instanceof \App\Models\Admin ? 'admin' : 'seller',
                ]);

                // ✅ Only run side effects AFTER COMMIT
                DB::afterCommit(function () use ($lead, $link, $url) {
                    if (! $lead->email) {
                        return;
                    }

                    try {
                        Notification::route('mail', $lead->email)
                            ->notify(new PaymentLinkNotification($link->load(['brand', 'lead']), $url, 'ppc'));
                    } catch (\Throwable $e) {
                        Log::error('Payment link email failed after commit', [
                            'lead_id' => $lead->id,
                            'link_id' => $link->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                });

                return [$link, $url];
            });

            return back()
                ->with('success', 'Payment link created.')
                ->with('payment_link_url', $url);
        } catch (\Throwable $e) {
            Log::error('Error creating payment link', [
                'error'        => $e->getMessage(),
                'brand_id'     => $brand->id ?? null,
                'lead_id'      => $lead->id ?? null,
                'order_type'   => $orderType ?? null,
                'base_order_id' => $baseOrderId ?? null,
                'actor_type'   => $actor instanceof \App\Models\Admin ? 'admin' : 'seller',
                'actor_id'     => $actor->id ?? null,
            ]);

            $message = $e->getMessage();
            $safeToShow = str_contains($message, 'active payment link')
                || str_contains($message, 'subscription plan')
                || str_contains($message, 'not included in your')
                || str_contains($message, 'merchant is configured')
                || str_contains($message, 'Payment Accounts');

            return back()->with('error', $safeToShow
                ? $message
                : 'There was an issue generating the payment link. Please try again or contact support.');
        }
    }
    // public function generatePayLink(Request $request, Brand $brand, Lead $lead, PaymentLinkService $links)
    // {
    //     $seller = auth('seller')->user();
    //     $admin  = auth('admin')->user();

    //     // Admin can always generate; seller must own lead and be in the same brand
    //     $canGenerate = $admin ? true : ($seller && $this->sellerOwnsLead($seller, $lead, $brand));

    //     abort_unless($canGenerate, 403, 'Seller must belong to and own the lead.');

    //     $data = $request->validate([
    //         'service_name'     => ['required', 'string', 'max:255'],
    //         'currency'         => ['required', 'string', 'size:3'],
    //         'total_amount'     => ['required', 'numeric', 'gt:0'],
    //         'payable_amount'   => ['required', 'numeric', 'gt:0'],
    //         'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
    //         'provider'         => ['nullable', 'in:stripe,paypal'],
    //         'order_type'       => ['nullable', 'in:original,renewal'],
    //         'parent_order_id'  => ['nullable', 'integer', 'exists:orders,id'], // for renewals
    //     ]);

    //     // Ensure payable amount doesn't exceed the total amount
    //     if ((float)$data['payable_amount'] > (float)$data['total_amount']) {
    //         return back()->with('error', 'Payable amount cannot exceed total amount.');
    //     }

    //     // Determine the actor (admin or seller)
    //     $actor = $admin ?: $seller;
    //     abort_unless($actor, 403);

    //     try {
    //         // Create payment link
    //         $link = $links->createInstallmentLink(
    //             brand: $brand,
    //             lead: $lead,
    //             sellerIdWhoGenerated: $seller->id ?? $lead->seller_id,
    //             serviceName: $data['service_name'],
    //             currency: strtoupper($data['currency']),
    //             totalCents: (int) round($data['total_amount'] * 100),
    //             payNowCents: (int) round($data['payable_amount'] * 100),
    //             expiresInHours: $data['expires_in_hours'] ?? null,
    //             provider: $data['provider'] ?? null,
    //             orderType: $data['order_type'] ?? 'original',
    //             parentOrderId: (int) ($data['parent_order_id'] ?? $request->order ?? 0) ?: null,
    //             meta: [
    //                 'generated_by_id'   => $actor->id,
    //                 'generated_by_type' => $actor instanceof \App\Models\Admin ? 'admin' : 'seller',
    //             ]
    //         );

    //         // Generate URL
    //         $url = $link->signedUrl();

    //         // Update payment link details in DB
    //         $link->update([
    //             'last_issued_url'        => $url,
    //             'last_issued_at'         => now(),
    //             'last_issued_expires_at' => $link->expires_at ?? now()->addDays(7),
    //             'generated_by_id'        => $actor->id,
    //             'generated_by_type'      => $actor instanceof \App\Models\Admin ? 'admin' : 'seller',
    //         ]);

    //         // Send notification email after data commit to DB
    //         // Notification::route('mail', $lead->email)
    //         //     ->notify(new PaymentLinkNotification($link, $url, 'ppc'));

    //         return back()->with('success', 'Payment link created.')->with('payment_link_url', $url);
    //     } catch (\Exception $e) {
    //         // Log the error and provide a generic error message
    //         Log::error('Error creating payment link', ['error' => $e->getMessage()]);
    //         return back()->with('error', 'There was an issue generating the payment link.');
    //     }
    // }

    // Create checkout with single payment account
    public function createCheckout(Request $request, string $token, PaymentGatewayFactory $factory)
    {
        // 1. Fetch link
        $link = PaymentLink::with(['order', 'lead', 'brand'])->where('token', $token)->first();
        if (!$link) {
            return back()->with('error', 'Link not found', $token);
        }
        // 1.5 Dump link & relationships
        // dd('Link loaded', [
        //     'link_id' => $link->id,
        //     'link->order' => $link->order ? $link->order->toArray() : null,
        //     'link->brand' => $link->brand ? $link->brand->toArray() : null,
        // ]);

        abort_if(! $link->isActiveLink(), 410, 'This payment link is no longer active.');
        // dd($link->order, $link->order->brand);

        $buyer = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string', 'max:255'],
            'city'       => ['nullable', 'string', 'max:255'],
            'state'      => ['nullable', 'string', 'max:255'],
            'zip'        => ['nullable', 'string', 'max:30'],
            'country'    => ['nullable', 'string', 'max:255'],
        ]);
        $brand = $link->brand ?? $link->order->brand ?? null;
        abort_if(!$brand, 500, 'Missing brand information.');

        // ✅ Load the keys from DB
        $keys = AccountKey::where('brand_id', $brand->id)
            ->where('status', 'active')
            ->first();
        if (!$keys || !$keys->stripe_secret_key) {
            return response()->json(['error' => 'Stripe keys are missing for this brand'], 500);
        }
        // ✅ Optional: log or preview keys
        Log::info('Stripe keys loaded for brand', [
            'brand_id' => $brand->id,
            'stripe_key' => substr($keys->stripe_secret_key, 0, 6) . '****'
        ]);

        $gateway = $factory->forProviderWithBrand($link->provider, $brand);   // 'stripe' or 'paypal'
        $checkout = $gateway->createCheckout($link, $buyer); // returns ['id'=>..., 'url'=>...]

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

    public function checkoutSuccess(Request $request, string $token)
    {
        $link = PaymentLink::with('order')->where('token', $token)->firstOrFail();

        $provider = $link->provider ?? 'stripe';
        $gateway = $provider === 'paypal'
            ? app(PayPalGateway::class)
            : app(StripeGateway::class);

        // Stripe: pass session_id; PayPal: service will read ?token=
        $gateway->handleCheckoutSuccess($link, $request->query('session_id'));

        return view('paid-success', ['link' => $link->fresh('order'), 'order' => $link->order]);
    }

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
        // dd($link);
        return view('generated-link-page', [
            'brand'   => $link->brand,
            'service' => $link->service_name,
            'amount'  => $link->unit_amount,
            'currency' => $link->currency,
            'order'   => $link->order,
            'lead'    => $link->lead,
            'token'   => $link->token,
            'link'   => $link,
        ]);
    }
}
