<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Services\Tenant\TenantFeatureService;
use App\Services\Tenant\TenantUsageService;

class OrganizationPlanController extends Controller
{
    use ResolvesOrganizationTenant;

    public function index(TenantFeatureService $features, TenantUsageService $usageService)
    {
        $tenant = $this->organizationTenant();
        $tenant->load(['plan', 'featureOverride', 'limitOverride']);

        $featureMatrix = collect($features->matrixForTenant($tenant))
            ->groupBy('group');

        $usage = $usageService->syncSnapshot((int) $tenant->id);
        $plan = $tenant->plan;

        $limits = [
            ['label' => 'Brands', 'used' => $usage->total_brands, 'max' => $plan?->max_brands],
            ['label' => 'Sellers', 'used' => $usage->total_sellers, 'max' => $plan?->max_sellers],
            ['label' => 'Admins', 'used' => $usage->total_admins, 'max' => $plan?->max_admins],
            ['label' => 'Clients', 'used' => $usage->total_clients, 'max' => $plan?->max_clients],
            ['label' => 'Orders', 'used' => $usage->total_orders, 'max' => $plan?->max_orders],
            ['label' => 'Leads / month', 'used' => $usage->leads_this_month, 'max' => $plan?->max_leads_per_month],
        ];

        return $this->organizationView('plan', [
            'tenant'        => $tenant,
            'plan'          => $plan,
            'featureMatrix' => $featureMatrix,
            'limits'        => $limits,
        ]);
    }
}
