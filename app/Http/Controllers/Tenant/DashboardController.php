<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\SystemAnnouncement;
use App\Models\Central\TenantInvoice;
use App\Services\Tenant\SubscriptionAccessService;
use App\Services\Tenant\TenantUsageService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(SubscriptionAccessService $accessService, TenantUsageService $usageService)
    {
        $tenant = Auth::guard('tenant')->user();
        $usage = $usageService->syncSnapshot((int) $tenant->id);
        $tenant->load(['plan', 'activeMembership']);
        $tenant->setRelation('usageSnapshot', $usage);

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

        $announcements = SystemAnnouncement::query()
            ->visible()
            ->forPlan((string) ($plan?->slug ?? ''))
            ->latest()
            ->get()
            ->filter(fn (SystemAnnouncement $a) => $a->isVisibleToTenant($tenant))
            ->values();

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
            'announcements',
        ));
    }
}
