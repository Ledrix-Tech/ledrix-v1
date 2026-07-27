<?php

namespace App\Services\Tenant;

use App\Mail\TenantSubscriptionDueMail;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CreatePayoneerSubscriptionInvoiceService
{
    public function __construct(
        private readonly SubscriptionAccessService $accessService,
    ) {}

    /**
     * @return array{payment: TenantPayment, invoice: TenantInvoice}
     */
    public function createForTenant(Tenant $tenant, string $orderType = 'new'): array
    {
        $tenant->load(['plan', 'activeMembership']);

        $membership = $this->accessService->currentMembership($tenant);

        if (! $membership) {
            throw new RuntimeException('No membership found for this tenant.');
        }

        $existingPending = TenantPayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('gateway', 'payoneer')
            ->where('status', 'pending')
            ->where('membership_id', $membership->id)
            ->first();

        if ($existingPending) {
            $invoice = TenantInvoice::where('payment_id', $existingPending->id)->first();

            if ($invoice) {
                return ['payment' => $existingPending, 'invoice' => $invoice];
            }
        }

        $billingCycle = $membership->billing_cycle ?? 'monthly';
        $plan = $tenant->plan ?? $membership->plan;
        $amount = $plan
            ? ($billingCycle === 'yearly' ? (float) $plan->yearly_price : (float) $plan->monthly_price)
            : (float) $membership->amount;

        if ($amount <= 0) {
            throw new RuntimeException('Subscription amount is not configured for this plan.');
        }

        if (! config('services.payoneer.receiver_email')) {
            throw new RuntimeException('Payoneer receiver email is not configured. Contact support.');
        }

        $currency = strtoupper($plan->currency ?? $membership->currency ?? config('services.payoneer.currency', 'USD'));
        $reference = 'LDRX-' . $tenant->id . '-' . strtoupper(Str::random(8));
        $graceDays = (int) config('services.payoneer.grace_days', 7);

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
        ) {
            $payment = TenantPayment::create([
                'tenant_id'      => $tenant->id,
                'membership_id'  => $membership->id,
                'plan_id'        => $plan?->id ?? $membership->plan_id,
                'transaction_id' => $reference,
                'gateway'        => 'payoneer',
                'order_type'     => $orderType,
                'renewed_by'     => 'payoneer',
                'billing_cycle'  => $billingCycle,
                'amount'         => $amount,
                'currency'       => $currency,
                'status'         => 'pending',
                'payload'        => [
                    'payment_reference' => $reference,
                    'receiver_email'    => config('services.payoneer.receiver_email'),
                    'receiver_name'     => config('services.payoneer.receiver_name'),
                ],
            ]);

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
                'notes'          => 'Pay via Payoneer using reference ' . $reference,
            ]);

            $membership->update([
                'status' => $tenant->isOnTrial() ? $membership->status : 'past_due',
            ]);

            AuditLog::record(
                action: 'subscription.invoice_issued',
                tenantId: $tenant->id,
                actorType: 'system',
                actorId: null,
                actorName: 'System',
                context: [
                    'subject_type' => 'tenant_payment',
                    'subject_id'   => $payment->id,
                    'description'  => 'Payoneer subscription invoice issued.',
                    'after'        => [
                        'reference' => $reference,
                        'amount'    => $amount,
                        'currency'  => $currency,
                    ],
                ]
            );

            return ['payment' => $payment, 'invoice' => $invoice];
        });
    }

    public function createAndNotify(Tenant $tenant, string $orderType = 'new'): array
    {
        $result = $this->createForTenant($tenant, $orderType);

        $this->sendDueEmail($tenant, $result['payment'], $result['invoice']);

        return $result;
    }

    public function sendDueEmail(Tenant $tenant, TenantPayment $payment, TenantInvoice $invoice): void
    {
        try {
            Mail::to($tenant->email)->send(new TenantSubscriptionDueMail($tenant, $payment, $invoice));
        } catch (Throwable $e) {
            Log::warning('Payoneer subscription due email failed', [
                'tenant_id' => $tenant->id,
                'message'   => $e->getMessage(),
            ]);
        }
    }
}
