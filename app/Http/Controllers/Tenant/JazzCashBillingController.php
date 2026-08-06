<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\TenantPayment;
use App\Services\Billing\ActivateTenantSubscriptionService;
use App\Services\Billing\CreateSubscriptionInvoiceService;
use App\Services\Billing\JazzCashService;
use App\Services\Billing\PlatformWebhookRecorder;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class JazzCashBillingController extends Controller
{
    public function checkout(
        Request $request,
        CreateSubscriptionInvoiceService $invoiceService,
        JazzCashService $jazzCash,
        SubscriptionAccessService $accessService,
    ) {
        $tenant = Auth::guard('tenant')->user();
        $membership = $accessService->currentMembership($tenant);

        if ($membership && $membership->status === 'active' && ! $membership->isExpired()) {
            return redirect()->route('tenant.billing')->with('success', 'Your subscription is already active.');
        }

        $validated = $request->validate([
            'auto_renew' => 'nullable|boolean',
        ]);

        $autoRenew = (bool) ($validated['auto_renew'] ?? false);

        $tenant->update([
            'preferred_billing_currency' => 'PKR',
            'auto_renew'                 => $autoRenew,
        ]);

        try {
            $result = $invoiceService->createForTenant($tenant, 'jazzcash', 'PKR', 'new');
        } catch (RuntimeException $e) {
            return redirect()->route('tenant.billing')->with('error', $e->getMessage());
        }

        $payment = $result['payment'];
        $planName = $tenant->plan?->name ?? 'Ledrix CRM';

        $fields = $jazzCash->buildCheckoutFields(
            txnRefNo: $payment->transaction_id,
            amountPkr: (float) $payment->amount,
            description: "Ledrix subscription — {$planName}",
            billReference: (string) $payment->id,
            recurring: $autoRenew,
        );

        $payment->update([
            'payload' => array_merge($payment->payload ?? [], [
                'jazzcash_checkout' => $fields,
                'auto_renew'        => $autoRenew,
            ]),
        ]);

        return view('front.pages.tenant.jazzcash-checkout', [
            'checkoutUrl' => $jazzCash->checkoutUrl(),
            'fields'      => $fields,
        ]);
    }

    public function returnCallback(
        Request $request,
        JazzCashService $jazzCash,
        ActivateTenantSubscriptionService $activationService,
        PlatformWebhookRecorder $recorder,
    ) {
        $response = $request->all();

        Log::info('JazzCash subscription callback', $response);

        if (! $jazzCash->verifyResponseHash($response)) {
            $redirect = Auth::guard('tenant')->check()
                ? redirect()->route('tenant.billing')
                : redirect()->route('tenant.login');

            return $redirect->with('error', 'Invalid JazzCash response signature.');
        }

        $paymentId = $response['ppmpf_1'] ?? $response['pp_BillReference'] ?? null;
        $payment = TenantPayment::query()
            ->with(['tenant', 'membership', 'invoice'])
            ->where('gateway', 'jazzcash')
            ->when($paymentId, fn ($q) => $q->where('id', $paymentId))
            ->when(! $paymentId && ! empty($response['pp_TxnRefNo']), fn ($q) => $q->where('transaction_id', $response['pp_TxnRefNo']))
            ->latest()
            ->first();

        if (! $payment) {
            return redirect()->route('tenant.login')->with('error', 'Payment record not found.');
        }

        $payment->update([
            'payload' => array_merge($payment->payload ?? [], [
                'jazzcash_response' => $response,
            ]),
        ]);

        $eventId = 'jazzcash_' . ($response['pp_TxnRefNo'] ?? ('payment_' . $payment->id));

        if (! $jazzCash->isSuccessfulResponse($response)) {
            $payment->update(['status' => 'failed']);
            $recorder->recordAndProcess(
                'jazzcash',
                $eventId . '_failed',
                'jazzcash.return.failed',
                $response,
                (int) $payment->tenant_id,
                fn ($row) => $row->markIgnored(),
            );

            return redirect()
                ->route('tenant.billing')
                ->with('error', $response['pp_ResponseMessage'] ?? 'JazzCash payment failed.');
        }

        $tenant = $payment->tenant;

        if (! empty($response['pp_PaymentToken'])) {
            $tenant->update([
                'jazzcash_payment_token'    => $response['pp_PaymentToken'],
                'jazzcash_token_expires_at' => now()->addYear(),
            ]);
        }

        try {
            $recorder->recordAndProcess(
                'jazzcash',
                $eventId,
                'jazzcash.return',
                $response,
                (int) $payment->tenant_id,
                function () use ($activationService, $payment, $response) {
                    $fresh = $payment->fresh(['tenant', 'membership', 'invoice']);
                    if ($fresh?->status === 'paid') {
                        return;
                    }

                    $activationService->activate(
                        $fresh ?? $payment,
                        renewedBy: 'jazzcash',
                        payloadMerge: [
                            'jazzcash_txn_ref'   => $response['pp_TxnRefNo'] ?? null,
                            'jazzcash_retrieval' => $response['pp_RetreivalReferenceNo'] ?? null,
                        ],
                    );
                },
            );
        } catch (RuntimeException $e) {
            return redirect()->route('tenant.billing')->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.billing')
            ->with('success', 'Payment successful! Your subscription is now active.');
    }
}
