<?php

namespace App\Services\Billing;

use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;

class SubscriptionPricingService
{
    public function resolveAmount(
        PackagePricing $plan,
        string $billingCycle,
        string $currency,
    ): float {
        $currency = strtoupper($currency);

        if ($currency === 'PKR') {
            $pkr = $billingCycle === 'yearly'
                ? $plan->yearly_price_pkr
                : $plan->monthly_price_pkr;

            if ($pkr !== null && (float) $pkr > 0) {
                return (float) $pkr;
            }

            $usd = $billingCycle === 'yearly'
                ? (float) $plan->yearly_price
                : (float) $plan->monthly_price;

            return round($usd * $this->usdToPkrRate(), 2);
        }

        return $billingCycle === 'yearly'
            ? (float) $plan->yearly_price
            : (float) $plan->monthly_price;
    }

    public function usdToPkrRate(): float
    {
        return (float) config('services.jazzcash.usd_to_pkr_rate', 280);
    }

    public function displayAmount(Tenant $tenant, ?TenantMembership $membership = null): array
    {
        $tenant->loadMissing('plan');
        $membership ??= $tenant->activeMembership;

        $cycle = $membership?->billing_cycle ?? 'monthly';
        $plan = $tenant->plan;

        if (! $plan) {
            return ['pkr' => 0.0, 'usd' => 0.0, 'cycle' => $cycle];
        }

        return [
            'pkr'    => $this->resolveAmount($plan, $cycle, 'PKR'),
            'usd'    => $this->resolveAmount($plan, $cycle, 'USD'),
            'cycle'  => $cycle,
        ];
    }

    public function jazzCashConfigured(): bool
    {
        return (bool) (
            config('services.jazzcash.merchant_id')
            && config('services.jazzcash.password')
            && config('services.jazzcash.integrity_salt')
        );
    }

    public function bankTransferConfigured(string $currency): bool
    {
        $currency = strtolower($currency);

        if ($currency === 'pkr') {
            return app(PlatformBillingSettingsService::class)->isReady('meezan');
        }

        $bank = config("services.bank_transfer.{$currency}", []);

        return ! empty($bank['bank_name'])
            && ! empty($bank['account_title'])
            && ! empty($bank['account_number']);
    }
}
