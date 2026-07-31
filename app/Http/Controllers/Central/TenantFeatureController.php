<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Services\Central\SuperAdminTenantFeatureService;
use App\Services\Central\SuperAdminTenantLimitService;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Http\Request;

class TenantFeatureController extends Controller
{
    public function edit(
        int $tenantId,
        TenantFeatureService $features,
        SuperAdminTenantFeatureService $featureOverrides,
        SuperAdminTenantLimitService $limitOverrides,
    ) {
        $tenant = Tenant::with(['plan', 'featureOverride.overriddenBy', 'limitOverride.overriddenBy'])
            ->findOrFail($tenantId);

        return view('central.pages.tenant-features', [
            'tenant'        => $tenant,
            'featureGroups' => collect($features->matrixForTenant($tenant))->groupBy('group'),
            'limitGroups'   => collect($limitOverrides->matrixForTenant($tenant))->groupBy('group'),
            'hasOverrides'  => $featureOverrides->hasAnyOverride($tenant)
                || $limitOverrides->hasAnyOverride($tenant),
        ]);
    }

    public function update(
        Request $request,
        int $tenantId,
        SuperAdminTenantFeatureService $featureService,
        SuperAdminTenantLimitService $limitService,
    ) {
        $tenant = Tenant::findOrFail($tenantId);

        $validated = $request->validate([
            'override_reason' => 'nullable|string|max:1000',
            'expires_at'      => 'nullable|date|after:now',
            'overrides'       => 'nullable|array',
            'limits'          => 'nullable|array',
        ]);

        $expiresAt = isset($validated['expires_at'])
            ? \Carbon\Carbon::parse($validated['expires_at'])
            : null;

        $reason = $validated['override_reason'] ?? null;

        $featureService->saveOverrides(
            $tenant,
            $featureService->overridesFromRequest($request),
            $reason,
            $expiresAt,
        );

        $limitService->saveOverrides(
            $tenant,
            $limitService->overridesFromRequest($request),
            $reason,
            $expiresAt,
        );

        return redirect()
            ->route('super-admin.tenant.features.get', $tenant->id)
            ->with('success', 'Plan access settings saved for this tenant.');
    }

    public function reset(
        int $tenantId,
        SuperAdminTenantFeatureService $featureService,
        SuperAdminTenantLimitService $limitService,
    ) {
        $tenant = Tenant::findOrFail($tenantId);

        $featureService->resetOverrides($tenant);
        $limitService->resetOverrides($tenant);

        return redirect()
            ->route('super-admin.tenant.features.get', $tenant->id)
            ->with('success', 'All overrides cleared. Package defaults now apply.');
    }
}
