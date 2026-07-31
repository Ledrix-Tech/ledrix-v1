<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Lead;
use App\Notifications\PaymentLinkNotification;
use App\Services\Tenant\TenantFeatureService;
use App\Services\Tenant\TenantLimitService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DirectOrderService
{
    public function __construct(
        private LeadIntakeService $intake,
        private LeadAssigner $assigner,
        private LeadClassifier $classifier,
        private PaymentLinkService $links,
        private PaymentGatewayFactory $factory,
        private TenantLimitService $limits,
        private TenantFeatureService $tenantFeatures,
    ) {}

    /**
     * Website direct order: create lead + payment link, optionally email and/or redirect to checkout.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'phone'          => 'nullable|string|max:30',
            'service'        => 'required|string|max:255',
            'price'          => 'required|string|max:50',
            'provider'       => 'required|in:stripe,paypal',
            'message'        => 'nullable|string|max:4000',
            'url'            => 'nullable|url',
            'brand_key'      => 'required|string',
            'utm_source'     => 'nullable|string|max:100',
            'utm_medium'     => 'nullable|string|max:100',
            'utm_campaign'   => 'nullable|string|max:150',
            'referrer'       => 'nullable|string|max:2048',
            'session_id'     => 'nullable|string|max:64',
            'checkout_mode'  => 'nullable|in:redirect,link_only',
        ]);

        $brand = $this->intake->resolveBrand($data['url'] ?? null, $request);
        abort_unless($brand, 422, 'Unknown brand.');
        abort_unless($brand->tenant_id, 422, 'Brand tenant is not configured.');

        $this->intake->assertPublicIntakeAllowed($brand, $request);

        $data = $this->intake->applyFieldMapping(array_merge($request->all(), $data), $brand);

        $prediction = $this->tenantFeatures->hasLeadPrediction((int) $brand->tenant_id)
            ? $this->classifier->classify($data)
            : null;

        $totalCents = $this->toCents($data['price']);
        $currency   = 'USD';
        $idem       = $request->header('Idempotency-Key');
        $checkoutMode = $data['checkout_mode'] ?? 'redirect';

        TenantContext::set((int) $brand->tenant_id);

        try {
            $this->limits->assertCanCreateLead((int) $brand->tenant_id);
            $this->limits->assertCanCreatePaymentLink((int) $brand->tenant_id);

            return DB::transaction(function () use (
                $data,
                $brand,
                $prediction,
                $totalCents,
                $currency,
                $idem,
                $checkoutMode,
                $request,
            ) {
                $email = strtolower(trim($data['email']));
                $client = Client::withoutGlobalScopes()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->first();

                if (! $client) {
                    $this->limits->assertCanCreateClient((int) $brand->tenant_id);

                    $client = Client::create([
                        'tenant_id' => $brand->tenant_id,
                        'name'      => $data['name'],
                        'email'     => $email,
                        'phone'     => $data['phone'] ?? null,
                    ]);
                }

                if (! $client->tenant_id) {
                    $client->update(['tenant_id' => $brand->tenant_id]);
                }

                $seller = $this->assigner->assignNext($brand);

                $lead = Lead::create([
                    'tenant_id'    => $brand->tenant_id,
                    'brand_id'     => $brand->id,
                    'seller_id'    => $seller->id,
                    'client_id'    => $client->id,
                    'name'         => $client->name,
                    'email'        => $client->email,
                    'phone'        => $client->phone,
                    'message'      => $data['message'] ?? null,
                    'status'       => 'new',
                    'converted_at' => null,
                    'prediction'   => is_array($prediction) ? $prediction : null,
                    'domain_url'   => LeadIntakeService::normalizeHost($data['url'] ?? ''),
                    'meta'         => array_filter([
                        'source'       => 'direct_order',
                        'utm_source'   => $data['utm_source'] ?? null,
                        'utm_medium'   => $data['utm_medium'] ?? null,
                        'utm_campaign' => $data['utm_campaign'] ?? null,
                        'referrer'     => $data['referrer'] ?? null,
                        'session_id'   => $data['session_id'] ?? null,
                        'idem'         => $idem,
                        'ip'           => $request->ip(),
                        'ua'           => substr((string) $request->userAgent(), 0, 255),
                        'service'      => $data['service'],
                        'currency'     => $currency,
                        'price_cents'  => $totalCents,
                    ]),
                ]);

                $link = $this->links->createInstallmentLink(
                    brand: $brand,
                    lead: $lead,
                    sellerIdWhoGenerated: $seller->id,
                    serviceName: $data['service'],
                    currency: $currency,
                    totalCents: $totalCents,
                    payNowCents: $totalCents,
                    expiresInHours: 24 * 7,
                    description: 'Direct order checkout',
                    provider: $data['provider'],
                );

                $url = $link->signedUrl();
                $link->update([
                    'last_issued_url'        => $url,
                    'last_issued_at'         => now(),
                    'last_issued_expires_at' => $link->expires_at ?? now()->addDays(7),
                ]);

                DB::afterCommit(function () use ($client, $link, $url) {
                    if (! $client->email) {
                        return;
                    }

                    try {
                        Notification::route('mail', $client->email)
                            ->notify(new PaymentLinkNotification($link->load(['brand', 'lead']), $url, 'ppc'));
                    } catch (\Throwable $e) {
                        Log::error('Direct order payment link email failed', [
                            'link_id' => $link->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                });

                if ($checkoutMode === 'link_only') {
                    return response()->json([
                        'ok'      => true,
                        'url'     => $url,
                        'link_id' => $link->id,
                        'lead_id' => $lead->id,
                    ], 201);
                }

                $gateway  = $this->factory->forProviderWithBrand($link->provider, $brand);
                $checkout = $gateway->createCheckout($link->load(['brand', 'order']), [
                    'email' => $client->email,
                ]);

                if (! empty($checkout['id'])) {
                    $link->update(['provider_session_id' => $checkout['id']]);
                    $link->order?->update(['provider_session_id' => $checkout['id']]);
                }

                return redirect()->away($checkout['url']);
            });
        } finally {
            TenantContext::clear();
        }
    }

    private function toCents(string $amount): int
    {
        $norm = preg_replace('/[^\d.,]/', '', $amount);
        $norm = str_replace(',', '', $norm);

        return (int) round(((float) $norm) * 100);
    }
}
