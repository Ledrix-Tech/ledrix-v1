<?php

namespace App\Services\Billing;

use App\Mail\TenantSubscriptionDueMail;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantPayment;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CreateSubscriptionInvoiceService
{
    public function __construct(
        private readonly SubscriptionAccessService $accessService,
        private readonly SubscriptionPricingService $pricingService,
        private readonly CancelStaleSubscriptionPaymentsService $cancelStalePayments,
    ) {}

    /**
     * @return array{payment: TenantPayment, invoice: TenantInvoice}
     */
    public function createForTenant(
        Tenant $tenant,
        string $gateway,
        string $currency,
        string $orderType = 'new',
    ): array {
        $tenant->load(['plan', 'activeMembership']);

        $membership = $this->accessService->currentMembership($tenant);

        if (! $membership) {
            throw new RuntimeException('No membership found for this tenant.');
        }

        $currency = strtoupper($currency);
        $gateway = strtolower($gateway);

        // Reuse the latest pending payment for this tenant + gateway (avoid duplicates on retry).
        $existingPending = TenantPayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('gateway', $gateway)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($existingPending) {
            $invoice = TenantInvoice::where('payment_id', $existingPending->id)->first();

            if ($invoice) {
                // Drop older pending attempts, keep only the one being reused.
                TenantPayment::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('gateway', $gateway)
                    ->where('status', 'pending')
                    ->where('id', '!=', $existingPending->id)
                    ->each(fn (TenantPayment $p) => $this->cancelStalePayments->cancelPayment($p, 'duplicate_checkout'));

                return ['payment' => $existingPending, 'invoice' => $invoice];
            }
        }

        $billingCycle = $membership->billing_cycle ?? 'monthly';
        $plan = $tenant->plan ?? $membership->plan;
        $amount = $plan
            ? $this->pricingService->resolveAmount($plan, $billingCycle, $currency)
            : (float) $membership->amount;

        if ($amount <= 0) {
            throw new RuntimeException('Subscription amount is not configured for this plan.');
        }

        if ($gateway === 'jazzcash' && ! $this->pricingService->jazzCashConfigured()) {
            throw new RuntimeException('JazzCash is not configured. Contact support.');
        }

        if ($gateway === 'bank_transfer' && ! $this->pricingService->bankTransferConfigured($currency)) {
            throw new RuntimeException('Bank transfer details are not configured. Contact support.');
        }

        if ($gateway === 'payfast' && ! config('services.payfast.merchant_id')) {
            throw new RuntimeException('PayFast is not configured. Contact support.');
        }

        if ($gateway === 'stripe' && ! config('services.stripe.secret')) {
            throw new RuntimeException('Stripe is not configured. Contact support.');
        }

        $reference = 'LDRX-' . $tenant->id . '-' . strtoupper(Str::random(8));
        $graceDays = match ($gateway) {
            'bank_transfer' => (int) config('services.bank_transfer.grace_days', 7),
            default         => (int) config('services.jazzcash.grace_days', 7),
        };

        $renewedBy = match ($gateway) {
            'jazzcash'      => 'jazzcash',
            'payfast'       => 'payfast',
            'stripe'        => 'stripe',
            'bank_transfer' => 'tenant',
            'payoneer'      => 'payoneer',
            default         => 'tenant',
        };

        return DB::connection('central')->transaction(function () use (
            $tenant,
            $membership,
            $plan,
            $billingCycle,
            $amount,
            $currency,
            $reference,
            $graceDays,
            $orderType,
            $gateway,
            $renewedBy,
        ) {
            $payment = TenantPayment::create([
                'tenant_id'      => $tenant->id,
                'membership_id'  => $membership->id,
                'plan_id'        => $plan?->id ?? $membership->plan_id,
                'transaction_id' => $reference,
                'gateway'        => $gateway,
                'order_type'     => $orderType,
                'renewed_by'     => $renewedBy,
                'billing_cycle'  => $billingCycle,
                'amount'         => $amount,
                'currency'       => $currency,
                'status'         => 'pending',
                'payload'        => [
                    'payment_reference' => $reference,
                ],
            ]);

            $invoiceNotes = match ($gateway) {
                'jazzcash'      => 'Pay via JazzCash using reference ' . $reference,
                'payfast'       => 'Pay via PayFast using reference ' . $reference,
                'stripe'        => 'Pay via Stripe using reference ' . $reference,
                'bank_transfer' => 'Pay via bank transfer using reference ' . $reference,
                default         => 'Pay using reference ' . $reference,
            };

            $invoice = TenantInvoice::create([
                'tenant_id'      => $tenant->id,
                'membership_id'  => $membership->id,
                'payment_id'     => $payment->id,
                'invoice_number' => TenantInvoice::nextNumber(),
                'plan_name'      => $plan?->name,
                'billing_cycle'  => $billingCycle,
                'amount'         => $amount,
                'currency'       => $currency,
                'tax_amount'     => 0,
                'total_amount'   => $amount,
                'status'         => 'issued',
                'issued_at'      => now(),
                'due_at'         => now()->addDays($graceDays),
                'notes'          => $invoiceNotes,
            ]);

            if (! $tenant->isOnTrial()) {
                $membership->update(['status' => 'past_due']);
            }

            AuditLog::record(
                action: 'subscription.invoice_issued',
                tenantId: $tenant->id,
                actorType: 'system',
                actorId: null,
                actorName: 'System',
                context: [
                    'subject_type' => 'tenant_payment',
                    'subject_id'   => $payment->id,
                    'description'  => ucfirst($gateway) . ' subscription invoice issued.',
                    'after'        => [
                        'reference' => $reference,
                        'amount'    => $amount,
                        'currency'  => $currency,
                        'gateway'   => $gateway,
                    ],
                ]
            );

            return ['payment' => $payment, 'invoice' => $invoice];
        });
    }

    public function createAndNotify(
        Tenant $tenant,
        string $gateway,
        string $currency,
        string $orderType = 'new',
    ): array {
        $result = $this->createForTenant($tenant, $gateway, $currency, $orderType);

        $this->sendDueEmail($tenant, $result['payment'], $result['invoice']);

        return $result;
    }

    public function sendDueEmail(Tenant $tenant, TenantPayment $payment, TenantInvoice $invoice): void
    {
        try {
            Mail::to($tenant->email)->send(new TenantSubscriptionDueMail($tenant, $payment, $invoice));
        } catch (Throwable $e) {
            Log::warning('Subscription due email failed', [
                'tenant_id' => $tenant->id,
                'message'   => $e->getMessage(),
            ]);
        }
    }
}
