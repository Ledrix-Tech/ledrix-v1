<?php

namespace App\Http\Middleware;

use App\Models\Central\Tenant;
use App\Services\Tenant\SubscriptionAccessService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures authenticated CRM users (admin/seller) belong to a tenant workspace.
 * Full CRM requires an active subscription; Organization Billing/Support/Referrals
 * stay reachable for admins so they can renew without leaving Admin.
 */
class EnsureCrmWorkspaceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantIdFromActor();

        abort_if($tenantId <= 0, 403, 'No organization workspace is available for this account. Sign out and sign in again, or use your organization portal → Enter CRM.');

        session(['tenant_id' => $tenantId]);
        TenantContext::set($tenantId);

        $tenant = Tenant::query()->find($tenantId);

        if (! $tenant) {
            abort_if(
                ! app()->environment('testing'),
                403,
                'Organization workspace was not found. Contact support.'
            );

            return $next($request);
        }

        $access = app(SubscriptionAccessService::class);

        if ($access->canUseCrm($tenant)) {
            return $next($request);
        }

        // Admins may renew / open platform support from Admin CRM while CRM is locked.
        if ($this->isAdminActor() && $access->canAccessOrgBilling($tenant)) {
            if ($request->routeIs('admin.org.*')) {
                return $next($request);
            }

            return redirect()
                ->route('admin.org.billing')
                ->with(
                    'error',
                    'Your subscription is not active. Renew below to restore CRM access.'
                );
        }

        if (Auth::guard('seller')->check()) {
            Auth::guard('seller')->logout();

            return redirect()
                ->route('admin.login.get')
                ->with(
                    'error',
                    'Your organization subscription is not active. Ask your administrator to renew billing in Admin → Organization → Billing.'
                );
        }

        if (Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
            session()->forget(['tenant_id', 'role']);

            return redirect()
                ->route('admin.login.get')
                ->with(
                    'error',
                    'Your subscription is not active. Sign in again after renewing, or use your organization portal.'
                );
        }

        abort(403, 'Your subscription is not active. Please renew via your organization portal.');
    }

    private function isAdminActor(): bool
    {
        $admin = Auth::guard('admin')->user();

        return $admin
            && ($admin->role ?? null) === 'admin';
    }

    private function resolveTenantIdFromActor(): int
    {
        $admin = Auth::guard('admin')->user();
        if ($admin && $admin->tenant_id) {
            return (int) $admin->tenant_id;
        }

        $seller = Auth::guard('seller')->user();
        if ($seller && $seller->tenant_id) {
            return (int) $seller->tenant_id;
        }

        return (int) (TenantContext::resolve() ?? 0);
    }
}
