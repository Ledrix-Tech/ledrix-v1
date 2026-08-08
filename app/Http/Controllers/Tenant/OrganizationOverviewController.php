<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\SystemAnnouncement;
use App\Models\Central\TenantInvoice;
use App\Services\Tenant\SubscriptionAccessService;
use App\Services\Tenant\TenantUsageService;

class OrganizationOverviewController extends Controller
{
    use ResolvesOrganizationTenant;

    public function index(SubscriptionAccessService $accessService, TenantUsageService $usageService)
    {
        $tenant = $this->organizationTenant();
        $usage = $usageService->syncSnapshot((int) $tenant->id);
        $tenant->load(['plan', 'activeMembership']);
        $tenant->setRelation('usageSnapshot', $usage);

        $membership = $accessService->currentMembership($tenant);
        $plan = $tenant->plan;

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

        $invoices = TenantInvoice::query()
            ->with('payment')
            ->where('tenant_id', $tenant->id)
            ->latest('issued_at')
            ->take(5)
            ->get();

        return $this->organizationView('overview', [
            'tenant'           => $tenant,
            'membership'       => $membership,
            'usage'            => $usage,
            'plan'             => $plan,
            'limits'           => $limits,
            'canUseCrm'        => $accessService->canUseCrm($tenant),
            'needsPayment'     => $accessService->needsPayment($tenant),
            'expiresSoon'      => $accessService->expiresSoon($tenant),
            'daysUntilRenewal' => $membership?->daysUntilExpiry() ?? 0,
            'invoices'         => $invoices,
            'announcements'    => $announcements,
        ]);
    }
}
