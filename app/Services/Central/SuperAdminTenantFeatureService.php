<?php

namespace App\Services\Central;

use App\Models\Central\Tenant;
use App\Models\Central\TenantFeatureOverride;
use App\Support\TenantFeatureCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminTenantFeatureService
{
    /**
     * @return array<string, bool|null>
     */
    public function overridesFromRequest(Request $request): array
    {
        $payload = [];
        $input = $request->input('overrides', []);

        if (! is_array($input)) {
            return $payload;
        }

        foreach (TenantFeatureCatalog::columns() as $column) {
            if (! array_key_exists($column, $input)) {
                continue;
            }

            $value = $input[$column];

            if ($value === '' || $value === null) {
                $payload[$column] = null;
            } else {
                $payload[$column] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $payload;
    }

    public function saveOverrides(Tenant $tenant, array $overrides, ?string $reason = null, ?\DateTimeInterface $expiresAt = null): TenantFeatureOverride
    {
        $superAdminId = Auth::guard('super_admin')->id();

        $data = array_merge(
            array_fill_keys(TenantFeatureCatalog::columns(), null),
            $overrides,
            [
                'override_reason' => $reason,
                'overridden_by'   => $superAdminId,
                'expires_at'      => $expiresAt,
            ]
        );

        return TenantFeatureOverride::updateOrCreate(
            ['tenant_id' => $tenant->id],
            $data
        );
    }

    public function resetOverrides(Tenant $tenant): void
    {
        TenantFeatureOverride::where('tenant_id', $tenant->id)->delete();
    }

    public function hasAnyOverride(Tenant $tenant): bool
    {
        $override = $tenant->featureOverride;

        if (! $override || $override->isExpired()) {
            return false;
        }

        foreach (TenantFeatureCatalog::columns() as $column) {
            if ($override->{$column} !== null) {
                return true;
            }
        }

        return filled($override->override_reason) || $override->expires_at !== null;
    }
}
