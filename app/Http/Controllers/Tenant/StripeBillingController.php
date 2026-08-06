<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\TenantPayment;
use App\Services\Billing\ActivateTenantSubscriptionService;
use App\Services\Billing\CreateSubscriptionInvoiceService;
use App\Services\Billing\PlatformWebhookRecorder;
use App\Services\Billing\TenantBillingRegion;
use App\Services\Billing\TenantStripeCheckoutService;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Http\Request;
use RuntimeException;

class StripeBillingController extends Controller
{
    use ResolvesOrganizationTenant;

    public function checkout(
        CreateSubscriptionInvoiceService $invoiceService,
        TenantStripeCheckoutService $stripeCheckout,
        SubscriptionAccessService $accessService,
    ) {
        $tenant = $this->organizationTenant();
        if (! $accessService->canPayOnBilling($tenant)) {
            return $this->organizationRedirect('billing', [], 'success', 'Your subscription is already active.');
        }

        if (TenantBillingRegion::isPakistanBuyer($tenant)) {
            return $this->organizationRedirect(
                'billing',
                [],
                'error',
                'Stripe is for international (USD) billing. Switch region or use Meezan / PayFast.'
            );
        }

        try {
            $orderType = $accessService->paymentOrderType($tenant);
            $result = $invoiceService->createForTenant($tenant, 'stripe', 'USD', $orderType);
            $url = $stripeCheckout->createCheckoutUrl(
                $tenant,
                $result['payment'],
                successUrl: route('tenant.billing.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
                cancelUrl: route($this->organizationRouteName('billing')) . '?cancelled=1',
            );
        } catch (RuntimeException $e) {
            return $this->organizationRedirect('billing', [], 'error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    public function success(
        Request $request,
        TenantStripeCheckoutService $stripeCheckout,
        ActivateTenantSubscriptionService $activationService,
        PlatformWebhookRecorder $recorder,
    ) {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return $this->billingHomeRedirect('error', 'Invalid Stripe session.');
        }

        $paymentId = $stripeCheckout->verifyAndGetPaymentId($sessionId);

        if (! $paymentId) {
            return $this->billingHomeRedirect('error', 'Payment was not completed.');
        }

        $payment = TenantPayment::query()
            ->with(['tenant', 'membership', 'invoice'])
            ->where('gateway', 'stripe')
            ->find($paymentId);

        if (! $payment) {
            return $this->billingHomeRedirect('error', 'Payment record not found.');
        }

        if ($payment->status === 'paid') {
            return $this->billingHomeRedirect('success', 'Your subscription is already active.');
        }

        try {
            $recorder->recordAndProcess(
                'stripe',
                'stripe_return_' . $sessionId,
                'checkout.session.return',
                ['session_id' => $sessionId, 'tenant_payment_id' => $payment->id],
                (int) $payment->tenant_id,
                function () use ($activationService, $payment, $sessionId) {
                    $fresh = $payment->fresh(['tenant', 'membership', 'invoice']);
                    if ($fresh?->status === 'paid') {
                        return;
                    }

                    $activationService->activate(
                        $fresh ?? $payment,
                        renewedBy: 'stripe',
                        payloadMerge: ['stripe_session_id' => $sessionId],
                    );
                },
            );
        } catch (RuntimeException $e) {
            return $this->billingHomeRedirect('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return $this->billingHomeRedirect(
                'error',
                'Payment received but activation failed. Please contact support — do not pay again.'
            );
        }

        return $this->billingHomeRedirect('success', 'Payment successful! Your subscription is now active.');
    }
}
