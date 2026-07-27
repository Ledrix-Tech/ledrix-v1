<?php

namespace App\Services\Billing;

use App\Mail\TenantSubscriptionActivatedMail;
use App\Models\Central\AuditLog;
use App\Models\Central\PackagePricing;
use App\Models\Central\TenantLimit;
use App\Models\Central\TenantMembership;
use App\Models\Central\TenantPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class ActivateTenantSubscriptionService
{
    public function __construct(
        private readonly CancelStaleSubscriptionPaymentsService $cancelStalePayments,
    ) {}

    /**
     * @param  array<string, mixed>  $payloadMerge
     */
    public function activate(
        TenantPayment $payment,
        string $renewedBy = 'tenant',
        ?string $note = null,
        array $payloadMerge = [],
        ?string $actorType = 'system',
        ?int $actorId = null,
        ?string $actorName = 'System',
    ): TenantPayment {
        if ($payment->status === 'paid') {
            return $payment;
        }

        $payment->load(['tenant.plan', 'membership', 'invoice']);

        return DB::connection('central')->transaction(function () use (
            $payment,
            $renewedBy,
            $note,
            $payloadMerge,
            $actorType,
            $actorId,
            $actorName,
        ) {
            $tenant = $payment->tenant;
            $membership = $payment->membership;
            $plan = $payment->plan ?? $tenant?->plan;

            if (! $tenant || ! $membership || ! $plan) {
                throw new RuntimeException('Payment is missing tenant, membership, or plan data.');
            }

            $cycle = $payment->billing_cycle ?? $membership->billing_cycle ?? 'monthly';
            [$startDate, $endDate] = $this->resolvePeriodDates($membership, $cycle);

            $payment->update([
                'status'     => 'paid',
                'paid_at'    => now(),
                'renewed_by' => $renewedBy,
                'payload'    => array_merge($payment->payload ?? [], $payloadMerge, array_filter([
                    'confirmed_at' => now()->toDateTimeString(),
                    'admin_note'   => $note,
                ])),
            ]);

            if ($payment->invoice) {
                $payment->invoice->update([
                    'status'  => 'paid',
                    'paid_at' => now(),
                    'notes'   => trim(($payment->invoice->notes ?? '') . ' Payment confirmed.'),
                ]);
            }

            $membership->update([
                'status'        => 'active',
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'amount'        => $payment->amount,
                'currency'      => $payment->currency,
                'billing_cycle' => $cycle,
                'renewed_by'    => $renewedBy,
                'trial_start'   => null,
                'trial_end'     => null,
            ]);

            $membership->clearRenewalReminderTimestamps();

            $tenant->update([
                'status'        => 'active',
                'trial_ends_at' => null,
            ]);

            TenantLimit::updateOrCreate(
                ['tenant_id' => $tenant->id],
                $this->limitsFromPackage($plan)
            );

            $this->cancelStalePayments->cancelForTenant($tenant->id, $payment->id);

            AuditLog::record(
                action: 'subscription.payment_confirmed',
                tenantId: $tenant->id,
                actorType: $actorType,
                actorId: $actorId,
                actorName: $actorName,
                context: [
                    'subject_type' => 'tenant_payment',
                    'subject_id'   => $payment->id,
                    'description'  => 'Subscription payment confirmed.',
                    'after'        => [
                        'gateway'    => $payment->gateway,
                        'reference'  => $payment->transaction_id,
                        'membership' => $membership->id,
                        'end_date'   => $endDate,
                    ],
                ]
            );

            $this->sendActivatedEmail($tenant, $payment);

            return $payment->fresh(['tenant', 'membership', 'invoice']);
        });
    }

    private function limitsFromPackage(PackagePricing $package): array
    {
        return [
            'package_id'        => $package->id,
            'max_admins'        => $package->max_admins,
            'max_users'         => $package->max_sellers,
            'max_brands'        => $package->max_brands,
            'max_sellers'       => $package->max_sellers,
            'max_clients'       => $package->max_clients,
            'max_leads'         => $package->max_leads_per_month,
            'max_orders'        => $package->max_orders,
            'max_payment_links' => $package->max_payment_links,
            'max_projects'      => $package->max_projects,
            'max_storage_mb'    => $package->max_storage_mb,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolvePeriodDates(TenantMembership $membership, string $cycle): array
    {
        $renewingEarly = $membership->status === 'active'
            && $membership->end_date
            && $membership->end_date->isFuture();

        if ($renewingEarly) {
            $startDate = $membership->start_date?->toDateString() ?? now()->toDateString();
            $base = $membership->end_date->copy();
        } else {
            $startDate = now()->toDateString();
            $base = now();
        }

        $endDate = $cycle === 'yearly'
            ? $base->copy()->addYear()->toDateString()
            : $base->copy()->addMonth()->toDateString();

        return [$startDate, $endDate];
    }

    private function sendActivatedEmail($tenant, TenantPayment $payment): void
    {
        try {
            Mail::to($tenant->email)->send(new TenantSubscriptionActivatedMail($tenant, $payment));
        } catch (Throwable $e) {
            Log::warning('Subscription activated email failed', [
                'tenant_id' => $tenant->id,
                'message'   => $e->getMessage(),
            ]);
        }
    }
}
