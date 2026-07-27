<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Models\Central\TenantInvoice;
use App\Models\Central\TenantPayment;
use App\Services\Central\SuperAdminTenantFeatureService;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Http\Request;


class MembershipController extends Controller
{
    public function superCompanyProfiles()
    {
        $companies = Tenant::with(['plan:id,name,slug', 'activeMembership'])
            ->latest()
            ->paginate(20);

        return view('central.pages.company-profiles', compact('companies'));
    }

    public function superTenantShow($id, TenantFeatureService $features, SuperAdminTenantFeatureService $overrides)
    {
        $tenant = Tenant::with([
            'plan',
            'featureOverride',
            'activeMembership.plan',
            'memberships' => fn ($q) => $q->latest()->take(5),
            'payments'    => fn ($q) => $q->latest()->take(8),
        ])->findOrFail($id);

        $pendingPayments = TenantPayment::query()
            ->with('invoice')
            ->where('tenant_id', $tenant->id)
            ->where('gateway', 'payoneer')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $invoices = TenantInvoice::query()
            ->with('payment')
            ->where('tenant_id', $tenant->id)
            ->latest('issued_at')
            ->get();

        return view('central.pages.tenant-detail', [
            'tenant'          => $tenant,
            'pendingPayments' => $pendingPayments,
            'invoices'        => $invoices,
            'featureSummary'  => collect($features->matrixForTenant($tenant))->filter(fn ($f) => $f['effective']),
            'hasOverrides'    => $overrides->hasAnyOverride($tenant),
        ]);
    }

    public function superTenantStatus(Request $request)
    {
        $tenant = Tenant::find($request->user_id);

        if (! $tenant) {
            return response()->json(['success' => false]);
        }

        $tenant->status = $request->status;
        $tenant->save();

        return response()->json(['success' => true]);
    }
}
