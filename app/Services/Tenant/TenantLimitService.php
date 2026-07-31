<?php

namespace App\Services\Tenant;

use App\Models\Central\Tenant;
use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

class TenantLimitService
{
    public function __construct(
        private TenantUsageService $usage,
        private TenantFeatureService $features,
    ) {}

    public function assertCanCreatePaymentLink(?int $tenantId = null): void
    {
        $this->assertWithinLimit('max_payment_links', $tenantId, 'Your plan payment link limit has been reached. Please upgrade or contact support.');
    }

    public function assertCanCreateSeller(?int $tenantId = null): void
    {
        $this->assertWithinLimit('max_sellers', $tenantId, 'Your plan seller limit has been reached. Please upgrade or contact support.');
    }

    public function assertCanCreateLead(?int $tenantId = null): void
    {
        $this->assertWithinLimit('max_leads_per_month', $tenantId, 'Your monthly lead limit has been reached. Please upgrade or contact support.');
    }

    public function assertCanCreateAccountKey(?int $tenantId = null): void
    {
        $tenant = $this->resolveTenant($tenantId);

        if (! $tenant) {
            return;
        }

        if ($this->usage->hasHitLimit($tenant, 'max_account_keys')) {
            throw ValidationException::withMessages([
                'brand_id' => 'Your plan account key limit has been reached. Please upgrade or contact support.',
            ]);
        }
    }

    public function assertCanCreateBrand(string $module, ?int $tenantId = null): void
    {
        $feature = match ($module) {
            'upwork' => 'upwork_module',
            default  => 'ppc_module',
        };

        $this->features->assertEnabled($feature, $tenantId);
        $this->assertWithinLimit('max_brands', $tenantId, 'Your plan brand limit has been reached. Please upgrade or contact support.');
    }

    public function assertCanCreateClient(?int $tenantId = null): void
    {
        $this->assertWithinLimit('max_clients', $tenantId, 'Your plan client limit has been reached. Please upgrade or contact support.');
    }

    public function assertCanCreateOrder(?int $tenantId = null): void
    {
        $this->assertWithinLimit('max_orders', $tenantId, 'Your plan order limit has been reached. Please upgrade or contact support.');
    }

    public function assertCanCreateProject(?int $tenantId = null): void
    {
        $this->features->assertEnabled('projects', $tenantId);
        $this->assertWithinLimit('max_projects', $tenantId, 'Your plan project limit has been reached. Please upgrade or contact support.');
    }

    public function assertCanCreateAdmin(?int $tenantId = null): void
    {
        $this->assertWithinLimit('max_admins', $tenantId, 'Your plan admin limit has been reached. Please upgrade or contact support.');
    }

    public function paymentLinksUsed(?int $tenantId = null): int
    {
        $tenantId = $tenantId ?? TenantContext::resolve();

        if (! $tenantId) {
            return 0;
        }

        return $this->usage->countForLimit('max_payment_links', (int) $tenantId);
    }

    public function accountKeysUsed(?int $tenantId = null): int
    {
        $tenantId = $tenantId ?? TenantContext::resolve();

        if (! $tenantId) {
            return 0;
        }

        return $this->usage->countForLimit('max_account_keys', (int) $tenantId);
    }

    public function hasHitLimit(string $limitKey, ?int $tenantId = null): bool
    {
        $tenant = $this->resolveTenant($tenantId);

        return $tenant ? $this->usage->hasHitLimit($tenant, $limitKey) : false;
    }

    private function assertWithinLimit(string $limitKey, ?int $tenantId, string $message): void
    {
        $tenant = $this->resolveTenant($tenantId);

        if (! $tenant) {
            return;
        }

        if ($this->usage->hasHitLimit($tenant, $limitKey)) {
            abort(422, $message);
        }
    }

    private function resolveTenant(?int $tenantId): ?Tenant
    {
        $tenantId = $tenantId ?? TenantContext::resolve();

        if (! $tenantId) {
            return null;
        }

        return Tenant::query()->with(['plan', 'limitOverride'])->find($tenantId);
    }
}
