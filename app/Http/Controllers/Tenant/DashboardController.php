<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\TenantInvoice;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(SubscriptionAccessService $accessService)
    {
        $tenant = Auth::guard('tenant')->user();
        $tenant->load(['plan', 'activeMembership', 'usageSnapshot']);

        $membership = $accessService->currentMembership($tenant);
        $usage = $tenant->usageSnapshot;
        $plan = $tenant->plan;
        $canUseCrm = $accessService->canUseCrm($tenant);
        $needsPayment = $accessService->needsPayment($tenant);
        $expiresSoon = $accessService->expiresSoon($tenant);
        $daysUntilRenewal = $membership?->daysUntilExpiry() ?? 0;

        $invoices = TenantInvoice::query()
            ->with('payment')
            ->where('tenant_id', $tenant->id)
            ->latest('issued_at')
            ->take(5)
            ->get();

        $limits = [
            'brands'        => $plan?->max_brands,
            'sellers'       => $plan?->max_sellers,
            'admins'        => $plan?->max_admins,
            'clients'       => $plan?->max_clients,
            'leads_monthly' => $plan?->max_leads_per_month,
            'orders'        => $plan?->max_orders,
        ];

        return view('front.pages.tenant.dashboard', compact(
            'tenant',
            'membership',
            'usage',
            'plan',
            'limits',
            'canUseCrm',
            'needsPayment',
            'expiresSoon',
            'daysUntilRenewal',
            'invoices',
        ));
    }
}
