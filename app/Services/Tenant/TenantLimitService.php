<?php

namespace App\Services\Tenant;

use App\Models\AccountKey;
use App\Models\Central\Tenant;
use App\Models\PaymentLink;
use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

class TenantLimitService
{
    public function assertCanCreatePaymentLink(?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? TenantContext::resolve();

        if (! $tenantId) {
            return;
        }

        $tenant = Tenant::query()->with('usageSnapshot')->find($tenantId);

        if (! $tenant) {
            return;
        }

        if ($tenant->hasHitLimit('max_payment_links')) {
            abort(422, 'Your plan payment link limit has been reached. Please upgrade or contact support.');
        }
    }

    public function assertCanCreateSeller(?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? TenantContext::resolve();

        if (! $tenantId) {
            return;
        }

        $tenant = Tenant::query()->with('usageSnapshot')->find($tenantId);

        if (! $tenant?->usageSnapshot) {
            return;
        }

        if ($tenant->usageSnapshot->hasHitLimit('max_sellers', $tenant)) {
            abort(422, 'Your plan seller limit has been reached. Please upgrade or contact support.');
        }
    }

    public function assertCanCreateLead(?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? TenantContext::resolve();

        if (! $tenantId) {
            return;
        }

        $tenant = Tenant::query()->with('usageSnapshot')->find($tenantId);

        if (! $tenant?->usageSnapshot) {
            return;
        }

        if ($tenant->usageSnapshot->hasHitLimit('max_leads_per_month', $tenant)) {
            abort(422, 'Your monthly lead limit has been reached. Please upgrade or contact support.');
        }
    }

    public function paymentLinksUsed(?int $tenantId = null): int
    {
        $tenantId = $tenantId ?? TenantContext::resolve();

        if (! $tenantId) {
            return 0;
        }

        return PaymentLink::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();
    }

    public function assertCanCreateAccountKey(?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? TenantContext::resolve();

        if (! $tenantId) {
            return;
        }

        $tenant = Tenant::query()->with('usageSnapshot')->find($tenantId);

        if (! $tenant) {
            return;
        }

        if ($tenant->hasHitLimit('max_account_keys')) {
            throw ValidationException::withMessages([
                'brand_id' => 'Your plan account key limit has been reached. Please upgrade or contact support.',
            ]);
        }
    }

    public function accountKeysUsed(?int $tenantId = null): int
    {
        $tenantId = $tenantId ?? TenantContext::resolve();

        if (! $tenantId) {
            return 0;
        }

        return AccountKey::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('brand_id')
            ->count();
    }
}
