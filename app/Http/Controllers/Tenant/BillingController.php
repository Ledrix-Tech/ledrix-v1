<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantPayment;
use App\Services\Billing\BankTransferQrService;
use App\Services\Billing\CancelStaleSubscriptionPaymentsService;
use App\Services\Billing\PayFastService;
use App\Services\Billing\SubscriptionPricingService;
use App\Services\Billing\TenantStripeCheckoutService;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    public function index(
        SubscriptionAccessService $accessService,
        SubscriptionPricingService $pricingService,
        PayFastService $payFast,
        TenantStripeCheckoutService $stripeCheckout,
        CancelStaleSubscriptionPaymentsService $cancelStalePayments,
        BankTransferQrService $qrService,
    ) {
        $tenant = Auth::guard('tenant')->user();
        $tenant->load(['plan', 'activeMembership']);

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

        $payfastReady = $payFast->isConfigured();
        $stripeReady = $stripeCheckout->isConfigured();
        $bankTransferReady = $pricingService->bankTransferConfigured('PKR');
        $hasPaymentOptions = $payfastReady || $stripeReady || $bankTransferReady;

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

        return view('front.pages.tenant.billing', compact(
            'tenant',
            'membership',
            'pendingPayment',
            'pendingBankTransfer',
            'invoices',
            'pricing',
            'payfastReady',
            'stripeReady',
            'bankTransferReady',
            'hasPaymentOptions',
            'needsPayment',
            'canPayNow',
            'hasActiveSubscription',
            'expiresSoon',
            'daysUntilRenewal',
            'bankTransferQr',
            'bankTransferQrError',
        ));
    }
}
