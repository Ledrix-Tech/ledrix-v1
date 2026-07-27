<?php

namespace App\Http\Controllers\Central;

use Illuminate\Http\Request;
use App\Models\Central\Tenant;
use App\Models\Central\TenantLimit;
use App\Http\Controllers\Controller;

class LimitationController extends Controller
{
    public function superdataLimitsPage()
    {
        $limits = TenantLimit::with(['tenant', 'package'])->paginate(20);
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'email']);

        return view('central.pages.data-limits', compact('limits', 'tenants'));
    }

    

    public function superdataLimitsPost(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'           => 'required|exists:central.tenants,id',
            'package_id'          => 'nullable|exists:package_pricings,id',
            'max_admins'          => 'nullable|integer|min:0',
            'max_users'           => 'nullable|integer|min:0',
            'max_brands'          => 'nullable|integer|min:0',
            'max_sellers'         => 'nullable|integer|min:0',
            'max_clients'         => 'nullable|integer|min:0',
            'max_leads'           => 'nullable|integer|min:0',
            'max_orders'          => 'nullable|integer|min:0',
            'max_payment_links'   => 'nullable|integer|min:0',
            'max_payments'        => 'nullable|integer|min:0',
        ]);

        $commonData = [
            'package_id'          => $request->package_id,
            'max_admins'          => $request->max_admins ?? 0,
            'max_users'           => $request->max_users ?? 0,
            'max_brands'          => $request->max_brands ?? 0,
            'max_sellers'         => $request->max_sellers ?? 0,
            'max_clients'         => $request->max_clients ?? 0,
            'max_leads'           => $request->max_leads ?? 0,
            'max_orders'          => $request->max_orders ?? 0,
            'max_payment_links'   => $request->max_payment_links ?? 0,
            'max_payments'        => $request->max_payments ?? 0,
        ];

        // dd($request->all(),$commonData);

        if ($request->filled('tenant_id')) {
            TenantLimit::updateOrCreate(
                ['tenant_id' => $request->tenant_id],
                $commonData
            );
        } else {
            // Global default limits (not tied to a specific company)
            TenantLimit::create($commonData);
        }

        return back()->with('success', 'CRM limits saved successfully.');
    }

    public function superdataLimitsUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'max_admins'          => 'nullable|integer|min:0',
            'max_users'           => 'nullable|integer|min:0',
            'max_brands'          => 'nullable|integer|min:0',
            'max_sellers'         => 'nullable|integer|min:0',
            'max_clients'         => 'nullable|integer|min:0',
            'max_leads'           => 'nullable|integer|min:0',
            'max_orders'          => 'nullable|integer|min:0',
            'max_payment_links'   => 'nullable|integer|min:0',
            'max_payments'        => 'nullable|integer|min:0',
        ]);

        $limit = TenantLimit::findOrFail($id);
        // dd($request->all(),$validated,$limit);

        $limit->update([
            'max_admins'          => $request->max_admins ?? $limit->max_admins,
            'max_users'           => $request->max_users ?? $limit->max_users,
            'max_brands'          => $request->max_brands ?? $limit->max_brands,
            'max_sellers'         => $request->max_sellers ?? $limit->max_sellers,
            'max_clients'         => $request->max_clients ?? $limit->max_clients,
            'max_leads'           => $request->max_leads ?? $limit->max_leads,
            'max_orders'          => $request->max_orders ?? $limit->max_orders,
            'max_payment_links'   => $request->max_payment_links ?? $limit->max_payment_links,
            'max_payments'        => $request->max_payments ?? $limit->max_payments,
        ]);

        return back()->with('success', 'Company limits updated successfully.');
    }
}
