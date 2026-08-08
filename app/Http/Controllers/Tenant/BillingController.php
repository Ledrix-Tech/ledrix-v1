<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantPayment;
use App\Models\Central\AuditLog;
use App\Services\Billing\BankTransferQrService;
use App\Services\Billing\CancelStaleSubscriptionPaymentsService;
use App\Services\Billing\JazzCashService;
use App\Services\Billing\PayFastService;
use App\Services\Billing\ReferralRewardService;
use App\Services\Billing\SubscriptionPricingService;
use App\Services\Billing\TenantBillingRegion;
use App\Services\Billing\TenantStripeCheckoutService;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    use ResolvesOrganizationTenant;

    public function index(
        SubscriptionAccessService $accessService,
        SubscriptionPricingService $pricingService,
        PayFastService $payFast,
        JazzCashService $jazzCash,
        TenantStripeCheckoutService $stripeCheckout,
        CancelStaleSubscriptionPaymentsService $cancelStalePayments,
        BankTransferQrService $qrService,
        ReferralRewardService $referralRewards,
    ) {
        $tenant = $this->organizationTenant();
        $tenant->load(['plan', 'activeMembership']);

        $billingCurrency = TenantBillingRegion::syncPreferredCurrency($tenant);
        $billingCredits = $referralRewards->creditBalances($tenant);
        $billingCredit = (float) ($billingCredits[$billingCurrency] ?? 0);
        $pendingReferralDiscount = is_array($tenant->meta['referral_discount'] ?? null)
            ? $tenant->meta['referral_discount']
            : null;
        $isPakistanBuyer = $billingCurrency === TenantBillingRegion::CURRENCY_PKR;

        $membership = $accessService->currentMembership($tenant);
        $pricing = $pricingService->displayAmount($tenant, $membership);
        $needsPayment = $accessService->needsPayment($tenant);
        $canPayNow = $accessService->canPayOnBilling($tenant);
        $expiresSoon = $accessService->expiresSoon($tenant);
        $daysUntilRenewal = $membership?->daysUntilExpiry() ?? 0;

        $hasActiveSubscription = $membership
            && $membership->status === 'active'
            && ! $membership->isExpired()
            && ! $canPayNow;

        if ($hasActiveSubscription) {
            $cancelStalePayments->cancelForTenant($tenant->id);
        }

        $pendingBankTransfer = TenantPayment::query()
            ->with('invoice')
            ->where('tenant_id', $tenant->id)
            ->where('gateway', 'bank_transfer')
            ->where('status', 'pending')
            ->latest()
            ->first();

        $pendingPayment = $canPayNow
            ? TenantPayment::query()
                ->with('invoice')
                ->where('tenant_id', $tenant->id)
                ->whereIn('gateway', ['stripe', 'payfast', 'jazzcash'])
                ->where('status', 'pending')
                ->latest()
                ->first()
            : null;

        $invoices = TenantInvoice::query()
            ->with('payment')
            ->where('tenant_id', $tenant->id)
            ->latest('issued_at')
            ->get();

        $stripeConfigured = $stripeCheckout->isConfigured();
        $payfastConfigured = $payFast->isConfigured();
        $jazzcashConfigured = $jazzCash->isConfigured();
        $meezanConfigured = $pricingService->bankTransferConfigured('PKR');

        $stripeReady = $stripeConfigured && ! $isPakistanBuyer;
        $payfastReady = $payfastConfigured && $isPakistanBuyer;
        $jazzcashReady = $jazzcashConfigured && $isPakistanBuyer;
        $bankTransferReady = $meezanConfigured && $isPakistanBuyer;
        $hasPaymentOptions = $stripeReady || $payfastReady || $bankTransferReady || $jazzcashReady;
        $cancelAtPeriodEnd = $membership
            && $membership->status === 'active'
            && $membership->cancelled_at !== null;

        $displayAmount = $isPakistanBuyer ? $pricing['pkr'] : $pricing['usd'];
        $billingRegionLabel = TenantBillingRegion::regionLabel($tenant);

        $bankTransferQr = null;
        $bankTransferQrError = null;
        if ($pendingBankTransfer && $bankTransferReady) {
            try {
                $generated = $qrService->generate(
                    $pendingBankTransfer,
                    config('services.bank_transfer.pkr', [])
                );
                $bankTransferQr = $generated['data_uri'];
            } catch (\RuntimeException $e) {
                $bankTransferQrError = $e->getMessage();
            }
        }

        return $this->organizationView('billing', compact(
            'tenant',
            'membership',
            'pendingPayment',
            'pendingBankTransfer',
            'invoices',
            'pricing',
            'displayAmount',
            'billingCurrency',
            'billingRegionLabel',
            'isPakistanBuyer',
            'stripeReady',
            'stripeConfigured',
            'payfastReady',
            'jazzcashReady',
            'jazzcashConfigured',
            'bankTransferReady',
            'meezanConfigured',
            'payfastConfigured',
            'hasPaymentOptions',
            'needsPayment',
            'canPayNow',
            'hasActiveSubscription',
            'expiresSoon',
            'daysUntilRenewal',
            'bankTransferQr',
            'bankTransferQrError',
            'billingCredit',
            'pendingReferralDiscount',
            'cancelAtPeriodEnd',
        ));
    }

    public function updateAutoRenew(Request $request, SubscriptionAccessService $accessService)
    {
        $tenant = $this->organizationTenant();
        $validated = $request->validate([
            'auto_renew' => ['required', 'boolean'],
        ]);

        $tenant->forceFill(['auto_renew' => (bool) $validated['auto_renew']])->save();

        $membership = $accessService->currentMembership($tenant);
        if ($membership && $validated['auto_renew'] && $membership->cancelled_at) {
            $membership->forceFill([
                'cancelled_at'  => null,
                'cancel_reason' => null,
            ])->save();
        }

        $this->auditBilling('tenant.auto_renew_updated', $tenant, [
            'description' => 'Auto-renew set to '.($validated['auto_renew'] ? 'on' : 'off'),
            'after'       => ['auto_renew' => (bool) $validated['auto_renew']],
        ]);

        return $this->organizationRedirect(
            'billing',
            [],
            'success',
            $validated['auto_renew']
                ? 'Auto-renew turned on.'
                : 'Auto-renew turned off. You keep access until the current period ends.'
        );
    }

    public function cancelAtPeriodEnd(Request $request, SubscriptionAccessService $accessService)
    {
        $tenant = $this->organizationTenant();
        $membership = $accessService->currentMembership($tenant);

        abort_unless(
            $membership && $membership->status === 'active' && ! $membership->isExpired(),
            403,
            'No active subscription to cancel.'
        );

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant->forceFill(['auto_renew' => false])->save();
        $membership->forceFill([
            'cancelled_at'  => now(),
            'cancel_reason' => $validated['reason'] ?? 'Cancelled at period end by organization admin',
        ])->save();

        $this->auditBilling('tenant.subscription_cancel_scheduled', $tenant, [
            'subject_type' => 'tenant_membership',
            'subject_id'   => $membership->id,
            'description'  => 'Subscription set to end at period end',
        ]);

        return $this->organizationRedirect(
            'billing',
            [],
            'success',
            'Auto-renew cancelled. CRM access continues until '.$membership->end_date?->format('M d, Y').'.'
        );
    }

    public function updateBillingCurrency(Request $request)
    {
        $tenant = $this->organizationTenant();

        $validated = $request->validate([
            'preferred_billing_currency' => ['required', Rule::in(['PKR', 'USD'])],
        ]);

        $tenant->forceFill([
            'preferred_billing_currency' => $validated['preferred_billing_currency'],
        ])->save();

        $label = $validated['preferred_billing_currency'] === 'PKR'
            ? 'Pakistan (PKR) — Meezan / PayFast'
            : 'International (USD) — Stripe';

        return $this->organizationRedirect('billing', [], 'success', "Billing region updated to {$label}.");
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function auditBilling(string $action, $tenant, array $context = []): void
    {
        $actor = Auth::guard('admin')->user() ?? Auth::guard('tenant')->user();

        AuditLog::record(
            $action,
            (int) $tenant->id,
            Auth::guard('admin')->check() ? 'admin' : 'tenant',
            $actor?->id,
            $actor?->name ?? $tenant->name,
            array_merge([
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
            ], $context)
        );
    }
}
