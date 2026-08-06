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
        private readonly ReferralRewardService $referralRewards,
        private readonly ActivateTenantSubscriptionService $activationService,
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

        // Reuse the latest pending payment for this tenant + gateway (avoid duplicates on retry),
        // unless unapplied referral rewards exist — then reissue so credits/discounts apply.
        $existingPending = TenantPayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('gateway', $gateway)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($existingPending) {
            $invoice = TenantInvoice::where('payment_id', $existingPending->id)->first();

            if ($invoice) {
                TenantPayment::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('gateway', $gateway)
                    ->where('status', 'pending')
                    ->where('id', '!=', $existingPending->id)
                    ->each(fn (TenantPayment $p) => $this->cancelStalePayments->cancelPayment($p, 'duplicate_checkout'));

                $tenant->refresh();
                if ($this->hasUnappliedReferralRewards($tenant, $currency)) {
                    $this->cancelStalePayments->cancelPayment($existingPending, 'reissue_with_referral_rewards');
                } else {
                    return ['payment' => $existingPending, 'invoice' => $invoice];
                }
            }
        }

        $billingCycle = $membership->billing_cycle ?? 'monthly';
        $plan = $tenant->plan ?? $membership->plan;
        $baseAmount = $plan
            ? $this->pricingService->resolveAmount($plan, $billingCycle, $currency)
            : (float) $membership->amount;

        if ($baseAmount <= 0) {
            throw new RuntimeException('Subscription amount is not configured for this plan.');
        }

        if ($gateway === 'jazzcash' && ! $this->pricingService->jazzCashConfigured()) {
            throw new RuntimeException('JazzCash is not configured. Contact support.');
        }

        if ($gateway === 'bank_transfer' && ! $this->pricingService->bankTransferConfigured($currency)) {
            throw new RuntimeException('Bank transfer is not available. Contact support.');
        }

        if ($gateway === 'payfast' && ! app(PlatformBillingSettingsService::class)->isReady('payfast')) {
            throw new RuntimeException('PayFast is not available. Contact support.');
        }

        if ($gateway === 'stripe' && ! app(PlatformBillingSettingsService::class)->isReady('stripe')) {
            throw new RuntimeException('Stripe is not available. Contact support.');
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
            $baseAmount,
            $currency,
            $reference,
            $graceDays,
            $orderType,
            $gateway,
            $renewedBy,
        ) {
            $tenant->refresh();
            $adjustment = $this->referralRewards->applyToInvoiceAmount($tenant, $baseAmount, $currency);
            $amount = $adjustment['amount'];

            // Fully covered by credits/discount — keep a payable minimum of 0 but still issue record.
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
                    'original_amount'   => $adjustment['original_amount'],
                    'discount_applied'  => $adjustment['discount_applied'],
                    'credit_applied'    => $adjustment['credit_applied'],
                ],
            ]);

            $invoiceNotes = match ($gateway) {
                'jazzcash'      => 'Pay via JazzCash using reference ' . $reference,
                'payfast'       => 'Pay via PayFast using reference ' . $reference,
                'stripe'        => 'Pay via Stripe using reference ' . $reference,
                'bank_transfer' => 'Pay via bank transfer using reference ' . $reference,
                default         => 'Pay using reference ' . $reference,
            };

            if ($adjustment['notes'] !== []) {
                $invoiceNotes .= ' · ' . implode(' · ', $adjustment['notes']);
            }

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

            // Fully covered by referral credit/discount — activate immediately (no gateway checkout).
            if ($amount <= 0.0 && (($adjustment['credit_applied'] ?? 0) > 0 || ($adjustment['discount_applied'] ?? 0) > 0)) {
                $invoice->update([
                    'notes' => trim($invoiceNotes . ' · Covered by referral credit/discount — auto-activated.'),
                ]);

                $payment = $this->activationService->activate(
                    payment: $payment->fresh(['tenant.plan', 'membership', 'invoice']),
                    renewedBy: 'referral_credit',
                    note: 'Auto-activated via referral credit/discount.',
                    payloadMerge: [
                        'covered_by_referral' => true,
                        'charged_amount'      => 0,
                        'original_amount'     => $adjustment['original_amount'],
                    ],
                    actorType: 'system',
                    actorId: null,
                    actorName: 'System',
                );

                // Keep membership priced at plan amount, not $0 charged.
                $payment->membership?->update([
                    'amount' => $adjustment['original_amount'],
                ]);

                return [
                    'payment' => $payment,
                    'invoice' => $payment->invoice ?? $invoice->fresh(),
                ];
            }

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
                        'reference'        => $reference,
                        'amount'           => $amount,
                        'original_amount'  => $adjustment['original_amount'],
                        'discount_applied' => $adjustment['discount_applied'],
                        'credit_applied'   => $adjustment['credit_applied'],
                        'currency'         => $currency,
                        'gateway'          => $gateway,
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

    private function hasUnappliedReferralRewards(Tenant $tenant, string $currency): bool
    {
        $currency = strtoupper($currency);
        $meta = is_array($tenant->meta) ? $tenant->meta : [];

        $credits = $meta['billing_credits'] ?? [];
        if (is_array($credits) && (float) ($credits[$currency] ?? 0) > 0) {
            return true;
        }

        $discount = $meta['referral_discount'] ?? null;

        return is_array($discount)
            && strtoupper((string) ($discount['currency'] ?? '')) === $currency
            && (float) ($discount['value'] ?? 0) > 0;
    }
}
