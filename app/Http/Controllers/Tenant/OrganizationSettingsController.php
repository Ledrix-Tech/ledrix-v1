<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrganizationSettingsController extends Controller
{
    use ResolvesOrganizationTenant;

    public function edit()
    {
        $tenant = $this->organizationTenant();

        return $this->organizationView('settings', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request)
    {
        $tenant = $this->organizationTenant();

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:40'],
            'country'         => ['nullable', 'string', 'max:5'],
            'website'         => ['nullable', 'url', 'max:255'],
            'billing_name'    => ['nullable', 'string', 'max:255'],
            'billing_email'   => ['nullable', 'email', 'max:255'],
            'billing_phone'   => ['nullable', 'string', 'max:40'],
            'billing_address' => ['nullable', 'string', 'max:1000'],
        ]);

        $before = $tenant->only(array_keys($validated));
        $tenant->fill($validated)->save();

        $actor = Auth::guard('admin')->user() ?? Auth::guard('tenant')->user();
        AuditLog::record(
            'tenant.settings_updated',
            (int) $tenant->id,
            Auth::guard('admin')->check() ? 'admin' : 'tenant',
            $actor?->id,
            $actor?->name ?? $tenant->name,
            [
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
                'description'  => 'Organization settings updated',
                'before'       => $before,
                'after'        => $validated,
            ]
        );

        return $this->organizationRedirect('settings', [], 'success', 'Organization settings saved.');
    }
}
