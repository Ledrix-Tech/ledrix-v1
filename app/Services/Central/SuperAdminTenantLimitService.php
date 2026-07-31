<?php

namespace App\Services\Central;

use App\Models\Central\Tenant;
use App\Models\Central\TenantLimitOverride;
use App\Services\Tenant\TenantUsageService;
use App\Support\TenantLimitCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminTenantLimitService
{
    public function __construct(
        private TenantUsageService $usage,
    ) {}

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     group: string,
     *     description: string,
     *     plan_default: int,
     *     override: int|null,
     *     effective: int,
     *     used: int,
     *     remaining: int|null,
     *     at_limit: bool
     * }>
     */
    public function matrixForTenant(Tenant $tenant): array
    {
        $tenant->loadMissing(['plan', 'limitOverride']);
        $override = $tenant->limitOverride;
        $overrideActive = $override instanceof TenantLimitOverride && ! $override->isExpired();

        $rows = [];

        foreach (TenantLimitCatalog::LIMITS as $key => $definition) {
            $planDefault = (int) ($tenant->plan?->$key ?? 0);
            $overrideValue = null;

            if ($overrideActive && array_key_exists($key, $override->getAttributes()) && $override->{$key} !== null) {
                $overrideValue = (int) $override->{$key};
            }

            $effective = (int) $tenant->limit($key);
            $used = $this->usage->countForLimit($key, (int) $tenant->id);
            $unlimited = $effective === -1;

            $rows[] = array_merge($definition, [
                'key'          => $key,
                'plan_default' => $planDefault,
                'override'     => $overrideValue,
                'effective'    => $effective,
                'used'         => $used,
                'remaining'    => $unlimited ? null : max(0, $effective - $used),
                'at_limit'     => ! $unlimited && $effective > 0 && $used >= $effective,
            ]);
        }

        return $rows;
    }

    /**
     * @return array<string, int|null>
     */
    public function overridesFromRequest(Request $request): array
    {
        $payload = [];
        $input = $request->input('limits', []);

        if (! is_array($input)) {
            return $payload;
        }

        foreach (TenantLimitCatalog::keys() as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }

            $value = $input[$key];

            if ($value === '' || $value === null) {
                $payload[$key] = null;
            } else {
                $payload[$key] = (int) $value;
            }
        }

        return $payload;
    }

    public function saveOverrides(
        Tenant $tenant,
        array $overrides,
        ?string $reason = null,
        ?\DateTimeInterface $expiresAt = null,
    ): ?TenantLimitOverride {
        $hasAny = collect($overrides)->contains(fn ($v) => $v !== null);

        if (! $hasAny && ! filled($reason) && $expiresAt === null) {
            TenantLimitOverride::where('tenant_id', $tenant->id)->delete();

            return null;
        }

        $superAdminId = Auth::guard('super_admin')->id();

        $data = array_merge(
            array_fill_keys(TenantLimitCatalog::keys(), null),
            $overrides,
            [
                'override_reason' => $reason,
                'overridden_by'   => $superAdminId,
                'expires_at'      => $expiresAt,
            ]
        );

        return TenantLimitOverride::updateOrCreate(
            ['tenant_id' => $tenant->id],
            $data
        );
    }

    public function resetOverrides(Tenant $tenant): void
    {
        TenantLimitOverride::where('tenant_id', $tenant->id)->delete();
    }

    public function hasAnyOverride(Tenant $tenant): bool
    {
        $override = $tenant->limitOverride;

        if (! $override || $override->isExpired()) {
            return false;
        }

        foreach (TenantLimitCatalog::keys() as $key) {
            if ($override->{$key} !== null) {
                return true;
            }
        }

        return filled($override->override_reason) || $override->expires_at !== null;
    }
}
