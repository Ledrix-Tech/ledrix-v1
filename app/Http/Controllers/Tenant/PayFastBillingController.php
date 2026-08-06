<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\TenantPayment;
use App\Services\Billing\ActivateTenantSubscriptionService;
use App\Services\Billing\CreateSubscriptionInvoiceService;
use App\Services\Billing\PayFastService;
use App\Services\Billing\PlatformWebhookRecorder;
use App\Services\Billing\TenantBillingRegion;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Http\Request;
use RuntimeException;

class PayFastBillingController extends Controller
{
    use ResolvesOrganizationTenant;

    public function checkout(
        CreateSubscriptionInvoiceService $invoiceService,
        PayFastService $payFast,
        SubscriptionAccessService $accessService,
    ) {
        $tenant = $this->organizationTenant();
        if (! $accessService->canPayOnBilling($tenant)) {
            return $this->organizationRedirect('billing', [], 'success', 'Your subscription is already active.');
        }

        if (! TenantBillingRegion::isPakistanBuyer($tenant)) {
            return $this->organizationRedirect(
                'billing',
                [],
                'error',
                'PayFast is for Pakistan (PKR) billing. Switch region or use Stripe (USD).'
            );
        }

        try {
            $orderType = $accessService->paymentOrderType($tenant);
            $result = $invoiceService->createForTenant($tenant, 'payfast', 'PKR', $orderType);
        } catch (RuntimeException $e) {
            return $this->organizationRedirect('billing', [], 'error', $e->getMessage());
        }

        $token = $payFast->getAccessToken();

        if (! $token) {
            return $this->organizationRedirect(
                'billing',
                [],
                'error',
                'Could not connect to PayFast. Check your merchant credentials.'
            );
        }

        $fields = $payFast->buildHostedCheckoutFields(
            $tenant,
            $result['payment'],
            $token,
            failUrl: route($this->organizationRouteName('billing')) . '?cancelled=1',
        );

        $result['payment']->update([
            'payload' => array_merge($result['payment']->payload ?? [], [
                'payfast_checkout' => $fields,
            ]),
        ]);

        return view('front.pages.tenant.payfast-checkout', [
            'checkoutUrl' => $payFast->checkoutUrl(),
            'fields'      => $fields,
        ]);
    }

    public function success(
        Request $request,
        PayFastService $payFast,
        ActivateTenantSubscriptionService $activationService,
        PlatformWebhookRecorder $recorder,
    ) {
        $orderId = $request->input('order_id') ?? $request->input('BASKET_ID');
        $signature = $request->input('signature') ?? $request->input('SIGNATURE');
        $amount = $request->input('TXNAMT') ?? $request->input('amount');

        $payment = TenantPayment::query()
            ->with(['tenant', 'membership', 'invoice'])
            ->where('gateway', 'payfast')
            ->when($orderId, fn ($q) => $q->where('transaction_id', $orderId))
            ->latest()
            ->first();

        if (! $payment) {
            return redirect()->route('tenant.login')->with('error', 'Payment record not found.');
        }

        if ($signature && $amount && $orderId) {
            $valid = $payFast->verifySignature(
                (string) config('services.payfast.merchant_id'),
                (string) config('services.payfast.merchant_name', config('app.name')),
                (string) $amount,
                (string) $orderId,
                (string) $signature,
            );

            if (! $valid) {
                return $this->billingHomeRedirect('error', 'Invalid PayFast response signature.');
            }
        }

        $payment->update([
            'payload' => array_merge($payment->payload ?? [], [
                'payfast_response' => $request->all(),
            ]),
        ]);

        $eventId = 'payfast_' . ($orderId ?: ('payment_' . $payment->id));

        try {
            $recorder->recordAndProcess(
                'payfast',
                $eventId,
                'payfast.return',
                $request->all(),
                (int) $payment->tenant_id,
                function () use ($activationService, $payment, $orderId) {
                    $fresh = $payment->fresh(['tenant', 'membership', 'invoice']);
                    if ($fresh?->status === 'paid') {
                        return;
                    }

                    $activationService->activate(
                        $fresh ?? $payment,
                        renewedBy: 'payfast',
                        payloadMerge: ['payfast_order_id' => $orderId],
                    );
                },
            );
        } catch (RuntimeException $e) {
            return $this->billingHomeRedirect('error', $e->getMessage());
        }

        return $this->billingHomeRedirect('success', 'Payment successful! Your subscription is now active.');
    }
}
