<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\ProvisionTenantAdminService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;

class CrmAccessController extends Controller
{
    public function enter(ProvisionTenantAdminService $provisionTenantAdminService)
    {
        $tenant = Auth::guard('tenant')->user();

        if (! $provisionTenantAdminService->canAccessCrm($tenant)) {
            return redirect()
                ->route('tenant.dashboard')
                ->with('error', 'Your subscription is not active. Please renew or contact support.');
        }

        $admin = $provisionTenantAdminService->provision($tenant);

        TenantContext::set($tenant->id);

        Auth::guard('admin')->login($admin);

        session(['tenant_id' => $tenant->id]);

        return redirect()
            ->route('admin.index.get')
            ->with('success', 'Welcome to your CRM workspace.');
    }
}
