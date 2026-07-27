<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Services\Central\SuperAdminTenantFeatureService;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Http\Request;

class TenantFeatureController extends Controller
{
    public function edit(int $tenantId, TenantFeatureService $features)
    {
        $tenant = Tenant::with(['plan', 'featureOverride.overriddenBy'])->findOrFail($tenantId);

        return view('central.pages.tenant-features', [
            'tenant'         => $tenant,
            'featureGroups'  => collect($features->matrixForTenant($tenant))->groupBy('group'),
            'hasOverrides'   => app(SuperAdminTenantFeatureService::class)->hasAnyOverride($tenant),
        ]);
    }

    public function update(Request $request, int $tenantId, SuperAdminTenantFeatureService $service)
    {
        $tenant = Tenant::findOrFail($tenantId);

        $validated = $request->validate([
            'override_reason' => 'nullable|string|max:1000',
            'expires_at'      => 'nullable|date|after:now',
            'overrides'       => 'nullable|array',
        ]);

        $overrides = $service->overridesFromRequest($request);
        $expiresAt = isset($validated['expires_at'])
            ? \Carbon\Carbon::parse($validated['expires_at'])
            : null;

        $service->saveOverrides(
            $tenant,
            $overrides,
            $validated['override_reason'] ?? null,
            $expiresAt
        );

        return redirect()
            ->route('super-admin.tenant.features.get', $tenant->id)
            ->with('success', 'Tenant feature overrides saved.');
    }

    public function reset(int $tenantId, SuperAdminTenantFeatureService $service)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $service->resetOverrides($tenant);

        return redirect()
            ->route('super-admin.tenant.features.get', $tenant->id)
            ->with('success', 'Tenant feature overrides cleared. Plan defaults now apply.');
    }
}
