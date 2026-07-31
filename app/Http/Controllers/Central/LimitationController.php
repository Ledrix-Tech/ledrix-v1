<?php

namespace App\Http\Controllers\Central;

use Illuminate\Http\Request;
use App\Models\Central\Tenant;
use App\Models\Central\TenantLimitOverride;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LimitationController extends Controller
{
    public function superdataLimitsPage()
    {
        $limits = TenantLimitOverride::with('tenant')->paginate(20);
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'email']);

        return view('central.pages.data-limits', compact('limits', 'tenants'));
    }

    public function superdataLimitsPost(Request $request)
    {
        $request->validate([
            'tenant_id'         => 'required|exists:central.tenants,id',
            'max_admins'        => 'nullable|integer|min:-1',
            'max_brands'        => 'nullable|integer|min:-1',
            'max_sellers'       => 'nullable|integer|min:-1',
            'max_clients'       => 'nullable|integer|min:-1',
            'max_leads'         => 'nullable|integer|min:-1',
            'max_orders'        => 'nullable|integer|min:-1',
            'max_payment_links' => 'nullable|integer|min:-1',
        ]);

        TenantLimitOverride::updateOrCreate(
            ['tenant_id' => $request->tenant_id],
            array_filter([
                'max_admins'          => $request->input('max_admins'),
                'max_brands'          => $request->input('max_brands'),
                'max_sellers'         => $request->input('max_sellers'),
                'max_clients'         => $request->input('max_clients'),
                'max_leads_per_month' => $request->input('max_leads'),
                'max_orders'          => $request->input('max_orders'),
                'max_payment_links'   => $request->input('max_payment_links'),
                'override_reason'     => 'Super-admin data limits panel',
                'overridden_by'       => Auth::guard('super_admin')->id(),
            ], fn ($value) => $value !== null && $value !== ''),
        );

        return back()->with('success', 'Tenant limit overrides saved successfully.');
    }

    public function superdataLimitsUpdate(Request $request, $id)
    {
        $request->validate([
            'max_admins'        => 'nullable|integer|min:-1',
            'max_brands'        => 'nullable|integer|min:-1',
            'max_sellers'       => 'nullable|integer|min:-1',
            'max_clients'       => 'nullable|integer|min:-1',
            'max_leads'         => 'nullable|integer|min:-1',
            'max_orders'        => 'nullable|integer|min:-1',
            'max_payment_links' => 'nullable|integer|min:-1',
        ]);

        $limit = TenantLimitOverride::findOrFail($id);

        $limit->update(array_filter([
            'max_admins'          => $request->input('max_admins', $limit->max_admins),
            'max_sellers'         => $request->input('max_sellers', $limit->max_sellers),
            'max_brands'          => $request->input('max_brands', $limit->max_brands),
            'max_clients'         => $request->input('max_clients', $limit->max_clients),
            'max_leads_per_month' => $request->input('max_leads', $limit->max_leads_per_month),
            'max_orders'          => $request->input('max_orders', $limit->max_orders),
            'max_payment_links'   => $request->input('max_payment_links', $limit->max_payment_links),
            'override_reason'     => 'Super-admin data limits panel',
            'overridden_by'       => Auth::guard('super-admin')->id(),
        ], fn ($value) => $value !== null && $value !== ''));

        return back()->with('success', 'Tenant limit overrides updated successfully.');
    }
}
