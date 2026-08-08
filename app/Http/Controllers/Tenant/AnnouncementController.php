<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\SystemAnnouncement;
use App\Models\Central\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function dismiss(int $id)
    {
        $tenant = $this->resolveOrganizationTenant();

        $announcement = SystemAnnouncement::query()->findOrFail($id);

        abort_unless($announcement->is_dismissible, 403, 'This announcement cannot be dismissed.');
        abort_unless($announcement->isVisibleToTenant($tenant), 404);

        $announcement->dismiss((int) $tenant->id);

        return back()->with('success', 'Announcement dismissed.');
    }

    private function resolveOrganizationTenant(): Tenant
    {
        if (Auth::guard('tenant')->check()) {
            /** @var Tenant $tenant */
            $tenant = Auth::guard('tenant')->user();

            return $tenant;
        }

        $admin = Auth::guard('admin')->user();
        abort_unless($admin && ($admin->role ?? null) === 'admin', 403);

        $tenantId = (int) ($admin->tenant_id ?: TenantContext::resolve());
        abort_if($tenantId <= 0, 403, 'Tenant workspace could not be resolved.');

        $tenant = Tenant::query()->find($tenantId);
        abort_unless($tenant, 403, 'Tenant workspace not found.');

        return $tenant;
    }
}
