<?php

namespace App\Services\Billing;

use App\Models\Central\Tenant;
use App\Models\Central\TenantPayment;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class TenantStripeCheckoutService
{
    public function isConfigured(): bool
    {
        return app(PlatformBillingSettingsService::class)->isReady('stripe');
    }

    public function createCheckoutUrl(
        Tenant $tenant,
        TenantPayment $payment,
        ?string $successUrl = null,
        ?string $cancelUrl = null,
    ): string {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Stripe is not configured.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $plan = $tenant->plan;
        $currency = strtolower($payment->currency ?: 'pkr');
        $amountMinor = (int) round((float) $payment->amount * 100);

        if ($amountMinor < 50) {
            throw new RuntimeException('Payment amount is too low for Stripe checkout.');
        }

        $session = Session::create([
            'mode'        => 'payment',
            'customer_email' => $tenant->email,
            'client_reference_id' => (string) $payment->id,
            'line_items'  => [[
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => $amountMinor,
                    'product_data' => [
                        'name'        => 'Ledrix CRM — ' . ($plan?->name ?? 'Subscription'),
                        'description' => ucfirst($payment->billing_cycle ?? 'monthly') . ' subscription',
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'tenant_id'         => (string) $tenant->id,
                'tenant_payment_id' => (string) $payment->id,
                'reference'         => $payment->transaction_id,
            ],
            'success_url' => $successUrl ?: (route('tenant.billing.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url'  => $cancelUrl ?: (route('tenant.billing') . '?cancelled=1'),
        ]);

        $payment->update([
            'payload' => array_merge($payment->payload ?? [], [
                'stripe_checkout_session_id' => $session->id,
            ]),
        ]);

        if (! $session->url) {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }

        return $session->url;
    }

    public function verifyAndGetPaymentId(string $sessionId): ?int
    {
        if (! $this->isConfigured()) {
            return null;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            return null;
        }

        return isset($session->metadata['tenant_payment_id'])
            ? (int) $session->metadata['tenant_payment_id']
            : null;
    }
}
