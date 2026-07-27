<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\TenantPayment;
use App\Services\Billing\ActivateTenantSubscriptionService;
use App\Services\Billing\CreateSubscriptionInvoiceService;
use App\Services\Billing\TenantStripeCheckoutService;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class StripeBillingController extends Controller
{
    public function checkout(
        CreateSubscriptionInvoiceService $invoiceService,
        TenantStripeCheckoutService $stripeCheckout,
        SubscriptionAccessService $accessService,
    ) {
        $tenant = Auth::guard('tenant')->user();
        if (! $accessService->canPayOnBilling($tenant)) {
            return redirect()->route('tenant.billing')->with('success', 'Your subscription is already active.');
        }

        try {
            $orderType = $accessService->paymentOrderType($tenant);
            $result = $invoiceService->createForTenant($tenant, 'stripe', 'PKR', $orderType);
            $url = $stripeCheckout->createCheckoutUrl($tenant, $result['payment']);
        } catch (RuntimeException $e) {
            return redirect()->route('tenant.billing')->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    public function success(
        Request $request,
        TenantStripeCheckoutService $stripeCheckout,
        ActivateTenantSubscriptionService $activationService,
    ) {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('tenant.billing')->with('error', 'Invalid Stripe session.');
        }

        $paymentId = $stripeCheckout->verifyAndGetPaymentId($sessionId);

        if (! $paymentId) {
            return redirect()->route('tenant.billing')->with('error', 'Payment was not completed.');
        }

        $payment = TenantPayment::query()
            ->with(['tenant', 'membership', 'invoice'])
            ->where('gateway', 'stripe')
            ->find($paymentId);

        if (! $payment) {
            return redirect()->route('tenant.billing')->with('error', 'Payment record not found.');
        }

        if ($payment->status === 'paid') {
            return redirect()->route('tenant.billing')->with('success', 'Your subscription is already active.');
        }

        try {
            $activationService->activate(
                $payment,
                renewedBy: 'stripe',
                payloadMerge: ['stripe_session_id' => $sessionId],
            );
        } catch (RuntimeException $e) {
            return redirect()->route('tenant.billing')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('tenant.billing')->with(
                'error',
                'Payment received but activation failed. Please contact support — do not pay again.'
            );
        }

        return redirect()->route('tenant.billing')->with('success', 'Payment successful! Your subscription is now active.');
    }
}
