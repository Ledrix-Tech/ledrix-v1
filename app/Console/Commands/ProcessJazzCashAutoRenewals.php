<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Central\TenantPayment;
use App\Services\Billing\ActivateTenantSubscriptionService;
use App\Services\Billing\CreateSubscriptionInvoiceService;
use App\Services\Billing\JazzCashService;
use App\Services\Billing\SubscriptionPricingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessJazzCashAutoRenewals extends Command
{
    protected $signature = 'tenants:process-jazzcash-renewals';

    protected $description = 'Charge JazzCash recurring tokens for tenants with auto-renew enabled';

    public function handle(
        JazzCashService $jazzCash,
        SubscriptionPricingService $pricing,
        CreateSubscriptionInvoiceService $invoiceService,
        ActivateTenantSubscriptionService $activationService,
    ): int {
        if (! $pricing->jazzCashConfigured()) {
            $this->warn('JazzCash is not configured. Skipping auto-renewals.');

            return self::SUCCESS;
        }

        $tenants = Tenant::query()
            ->where('auto_renew', true)
            ->whereNotNull('jazzcash_payment_token')
            ->whereHas('activeMembership', function ($q) {
                $q->where('status', 'active')
                    ->whereDate('end_date', '<=', now()->addDay());
            })
            ->with(['plan', 'activeMembership'])
            ->get();

        $processed = 0;

        foreach ($tenants as $tenant) {
            if ($tenant->jazzcash_token_expires_at && $tenant->jazzcash_token_expires_at->isPast()) {
                $this->line("Tenant {$tenant->id}: token expired, skipping.");
                continue;
            }

            $membership = $tenant->activeMembership;
            $plan = $tenant->plan;

            if (! $membership || ! $plan) {
                continue;
            }

            $cycle = $membership->billing_cycle ?? 'monthly';
            $amount = $pricing->resolveAmount($plan, $cycle, 'PKR');

            if ($amount <= 0) {
                continue;
            }

            try {
                $result = $invoiceService->createForTenant($tenant, 'jazzcash', 'PKR', 'renewal');
            } catch (\Throwable $e) {
                Log::error('JazzCash auto-renew invoice failed', [
                    'tenant_id' => $tenant->id,
                    'message'   => $e->getMessage(),
                ]);
                continue;
            }

            $payment = $result['payment'];
            $txnRef = $payment->transaction_id;

            $charge = $jazzCash->chargeViaToken(
                token: (string) $tenant->jazzcash_payment_token,
                txnRefNo: $txnRef,
                amountPkr: $amount,
                description: 'Ledrix subscription renewal — ' . ($plan->name ?? 'CRM'),
            );

            $payment->update([
                'payload' => array_merge($payment->payload ?? [], [
                    'auto_renew'          => true,
                    'jazzcash_token_resp' => $charge['response'],
                ]),
            ]);

            if ($charge['success']) {
                $activationService->activate(
                    $payment,
                    renewedBy: 'jazzcash',
                    payloadMerge: ['auto_renewed' => true],
                );
                $processed++;
                $this->info("Tenant {$tenant->id}: renewed successfully.");
            } else {
                $payment->update(['status' => 'failed']);
                $tenant->update(['auto_renew' => false]);
                Log::warning('JazzCash auto-renew charge failed', [
                    'tenant_id' => $tenant->id,
                    'message'   => $charge['message'],
                ]);
                $this->warn("Tenant {$tenant->id}: charge failed — {$charge['message']}");
            }
        }

        $this->info("Processed {$processed} auto-renewal(s).");

        return self::SUCCESS;
    }
}
