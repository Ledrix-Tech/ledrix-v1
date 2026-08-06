<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\TenantPayment;
use App\Services\Billing\BankTransferQrService;
use App\Services\Billing\CreateSubscriptionInvoiceService;
use App\Services\Billing\ReportBankTransferPaymentService;
use App\Services\Billing\SubscriptionPricingService;
use App\Services\Billing\TenantBillingRegion;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Http\Request;
use RuntimeException;

class BankTransferBillingController extends Controller
{
    use ResolvesOrganizationTenant;

    public function checkout(
        CreateSubscriptionInvoiceService $invoiceService,
        SubscriptionAccessService $accessService,
        SubscriptionPricingService $pricingService,
    ) {
        if (! $pricingService->bankTransferConfigured('PKR')) {
            return $this->organizationRedirect(
                'billing',
                [],
                'error',
                'Bank transfer is not configured yet. Contact support or use Stripe.'
            );
        }

        $tenant = $this->organizationTenant();

        if (! TenantBillingRegion::isPakistanBuyer($tenant)) {
            return $this->organizationRedirect(
                'billing',
                [],
                'error',
                'Meezan is for Pakistan (PKR) billing. Switch region or use Stripe (USD).'
            );
        }

        if (! $accessService->canPayOnBilling($tenant)) {
            return $this->organizationRedirect('billing', [], 'success', 'Your subscription is already active.');
        }

        $existing = TenantPayment::query()
            ->with('invoice')
            ->where('tenant_id', $tenant->id)
            ->where('gateway', 'bank_transfer')
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($existing?->invoice) {
            return $this->organizationRedirect(
                'billing.bank-transfer.show',
                $existing,
                'success',
                'You already have a pending transfer — scan the QR or use the reference below.'
            );
        }

        try {
            $orderType = $accessService->paymentOrderType($tenant);
            $result = $invoiceService->createAndNotify($tenant, 'bank_transfer', 'PKR', $orderType);
        } catch (RuntimeException $e) {
            return $this->organizationRedirect('billing', [], 'error', $e->getMessage());
        }

        return $this->organizationRedirect(
            'billing.bank-transfer.show',
            $result['payment'],
            'success',
            'Scan the QR code to pay, then submit your bank transaction ID.'
        );
    }

    public function show(
        TenantPayment $payment,
        BankTransferQrService $qrService,
    ) {
        $tenant = $this->organizationTenant();

        if ($payment->tenant_id !== $tenant->id || $payment->gateway !== 'bank_transfer') {
            abort(404);
        }

        $payment->load('invoice');

        if ($payment->status === 'paid') {
            return $this->organizationRedirect('billing', [], 'success', 'This payment is already confirmed.');
        }

        $bank = config('services.bank_transfer.pkr', []);
        $qr = $this->resolveQr($qrService, $payment, $bank);

        return $this->organizationView('bank-transfer-instructions', [
            'tenant'      => $tenant,
            'payment'     => $payment,
            'invoice'     => $payment->invoice,
            'bank'        => $bank,
            'qrDataUri'   => $qr['data_uri'],
            'qrError'     => $qr['error'] ?? null,
            'raastQr'     => $qr['raast'],
            'raastQrMode' => config('services.bank_transfer.raast_qr_mode', 'dynamic'),
        ]);
    }

    /**
     * @return array{data_uri: ?string, payload: ?string, raast: bool, error?: string}
     */
    private function resolveQr(BankTransferQrService $qrService, TenantPayment $payment, array $bank): array
    {
        try {
            return $qrService->generate($payment, $bank);
        } catch (RuntimeException $e) {
            return [
                'data_uri' => null,
                'payload'  => null,
                'raast'    => false,
                'error'    => $e->getMessage(),
            ];
        }
    }

    public function report(
        Request $request,
        TenantPayment $payment,
        ReportBankTransferPaymentService $reportService,
    ) {
        $tenant = $this->organizationTenant();

        if ($payment->tenant_id !== $tenant->id || $payment->gateway !== 'bank_transfer') {
            abort(404);
        }

        if ($payment->status !== 'pending') {
            return $this->organizationRedirect('billing', [], 'error', 'This payment is no longer pending.');
        }

        $validated = $request->validate([
            'bank_txn_id' => 'required|string|min:4|max:64',
            'note'        => 'nullable|string|max:500',
        ]);

        try {
            $reportService->report(
                $payment,
                $validated['bank_txn_id'],
                $validated['note'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with(
            'success',
            'Thank you! We received your transaction ID. Subscription activates after we verify the transfer on our bank statement (usually within 1 business day).'
        );
    }
}
