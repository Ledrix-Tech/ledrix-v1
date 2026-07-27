<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\TenantPayment;
use App\Services\Billing\ActivateTenantSubscriptionService;
use App\Services\Billing\CreateSubscriptionInvoiceService;
use App\Services\Billing\PayFastService;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class PayFastBillingController extends Controller
{
    public function checkout(
        CreateSubscriptionInvoiceService $invoiceService,
        PayFastService $payFast,
        SubscriptionAccessService $accessService,
    ) {
        $tenant = Auth::guard('tenant')->user();
        if (! $accessService->canPayOnBilling($tenant)) {
            return redirect()->route('tenant.billing')->with('success', 'Your subscription is already active.');
        }

        try {
            $orderType = $accessService->paymentOrderType($tenant);
            $result = $invoiceService->createForTenant($tenant, 'payfast', 'PKR', $orderType);
        } catch (RuntimeException $e) {
            return redirect()->route('tenant.billing')->with('error', $e->getMessage());
        }

        $token = $payFast->getAccessToken();

        if (! $token) {
            return redirect()->route('tenant.billing')->with('error', 'Could not connect to PayFast. Check your merchant credentials.');
        }

        $fields = $payFast->buildHostedCheckoutFields($tenant, $result['payment'], $token);

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
                return redirect()->route('tenant.billing')->with('error', 'Invalid PayFast response signature.');
            }
        }

        $payment->update([
            'payload' => array_merge($payment->payload ?? [], [
                'payfast_response' => $request->all(),
            ]),
        ]);

        if ($payment->status !== 'paid') {
            try {
                $activationService->activate(
                    $payment,
                    renewedBy: 'payfast',
                    payloadMerge: ['payfast_order_id' => $orderId],
                );
            } catch (RuntimeException $e) {
                return redirect()->route('tenant.billing')->with('error', $e->getMessage());
            }
        }

        return redirect()->route('tenant.billing')->with('success', 'Payment successful! Your subscription is now active.');
    }
}
