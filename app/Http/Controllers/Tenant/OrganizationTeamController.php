<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Central\AuditLog;
use App\Services\Tenant\TenantLimitService;
use App\Services\Tenant\TenantUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OrganizationTeamController extends Controller
{
    use ResolvesOrganizationTenant;

    public function index(TenantUsageService $usageService)
    {
        $tenant = $this->organizationTenant();
        $tenant->load('plan');

        $members = Admin::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereIn('role', ['admin', 'finance'])
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $usage = $usageService->syncSnapshot((int) $tenant->id);
        $adminLimit = $tenant->limit('max_admins');
        $adminUsed = (int) ($usage->total_admins ?? $members->count());

        return $this->organizationView('team', [
            'tenant'     => $tenant,
            'members'    => $members,
            'adminLimit' => $adminLimit,
            'adminUsed'  => $adminUsed,
        ]);
    }

    public function store(Request $request, TenantLimitService $limits, TenantUsageService $usageService)
    {
        $tenant = $this->organizationTenant();

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', Rule::in(['admin', 'finance'])],
        ]);

        $limits->assertCanCreateAdmin((int) $tenant->id);

        $member = Admin::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => $validated['password'],
            'role'      => $validated['role'],
        ]);

        $usageService->syncSnapshot((int) $tenant->id);

        $actor = Auth::guard('admin')->user();
        AuditLog::record(
            'tenant.team_member_created',
            (int) $tenant->id,
            'admin',
            $actor?->id,
            $actor?->name,
            [
                'subject_type' => 'admin',
                'subject_id'   => $member->id,
                'description'  => "Created {$member->role} seat {$member->email}",
            ]
        );

        return $this->organizationRedirect('team', [], 'success', 'Team member added.');
    }

    public function destroy(int $id, TenantUsageService $usageService)
    {
        $tenant = $this->organizationTenant();
        $actor = Auth::guard('admin')->user();

        $member = Admin::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereIn('role', ['admin', 'finance'])
            ->findOrFail($id);

        abort_if($actor && (int) $actor->id === (int) $member->id, 403, 'You cannot remove your own seat.');

        $adminCount = Admin::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('role', 'admin')
            ->count();

        abort_if(
            $member->role === 'admin' && $adminCount <= 1,
            403,
            'You cannot remove the last administrator seat.'
        );

        $email = $member->email;
        $role = $member->role;
        $member->delete();

        $usageService->syncSnapshot((int) $tenant->id);

        AuditLog::record(
            'tenant.team_member_removed',
            (int) $tenant->id,
            'admin',
            $actor?->id,
            $actor?->name,
            [
                'subject_type' => 'admin',
                'description'  => "Removed {$role} seat {$email}",
            ]
        );

        return $this->organizationRedirect('team', [], 'success', 'Team member removed.');
    }
}
