<?php

namespace App\Http\Middleware;

use App\Models\Central\Tenant;
use App\Services\Tenant\SubscriptionAccessService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures authenticated CRM users (admin/seller) belong to a tenant workspace
 * with an active subscription before accessing portal routes.
 */
class EnsureCrmWorkspaceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantIdFromActor();

        abort_if($tenantId <= 0, 403, 'Tenant workspace could not be resolved for this account.');

        session(['tenant_id' => $tenantId]);
        TenantContext::set($tenantId);

        $tenant = Tenant::query()->find($tenantId);

        if (! $tenant) {
            abort_if(
                ! app()->environment('testing'),
                403,
                'Tenant workspace not found.'
            );

            return $next($request);
        }

        abort_unless(
            app(SubscriptionAccessService::class)->canUseCrm($tenant),
            403,
            'Your subscription is not active. Please renew or sign in via your organization portal.'
        );

        return $next($request);
    }

    private function resolveTenantIdFromActor(): int
    {
        $admin = auth('admin')->user();
        if ($admin && $admin->tenant_id) {
            return (int) $admin->tenant_id;
        }

        $seller = auth('seller')->user();
        if ($seller && $seller->tenant_id) {
            return (int) $seller->tenant_id;
        }

        return (int) (TenantContext::resolve() ?? 0);
    }
}
