<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Central\Tenant;
use App\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

trait ResolvesOrganizationTenant
{
    protected function organizationTenant(): Tenant
    {
        if (Auth::guard('tenant')->check()) {
            /** @var Tenant $tenant */
            $tenant = Auth::guard('tenant')->user();

            return $tenant;
        }

        $tenantId = (int) (TenantContext::resolve()
            ?? Auth::guard('admin')->user()?->tenant_id
            ?? 0);

        abort_unless($tenantId > 0, 403, 'No organization workspace is available.');

        return Tenant::query()->findOrFail($tenantId);
    }

    protected function isAdminOrganizationPortal(): bool
    {
        return request()->routeIs('admin.org.*');
    }

    protected function organizationView(string $page, array $data = []): View
    {
        $data['organizationPortal'] = $this->isAdminOrganizationPortal() ? 'admin' : 'tenant';
        $data['tenant'] = $data['tenant'] ?? $this->organizationTenant();

        // Shared blades under front.pages.tenant.* switch layout via $organizationPortal.
        view()->share('organizationPortal', $data['organizationPortal']);

        return view('front.pages.tenant.' . $page, $data);
    }

    protected function organizationRedirect(string $name, mixed $parameters = [], ?string $flashKey = null, ?string $flashMessage = null): RedirectResponse
    {
        $response = redirect()->route($this->organizationRouteName($name), $parameters);

        if ($flashKey && $flashMessage !== null) {
            $response->with($flashKey, $flashMessage);
        }

        return $response;
    }

    /**
     * After gateway return URLs (often public), send the user to whichever portal they can use.
     */
    protected function billingHomeRedirect(?string $flashKey = null, ?string $flashMessage = null): RedirectResponse
    {
        $route = Auth::guard('admin')->check()
            ? 'admin.org.billing'
            : (Auth::guard('tenant')->check() ? 'tenant.billing' : 'tenant.login');

        $response = redirect()->route($route);

        if ($flashKey && $flashMessage !== null) {
            $response->with($flashKey, $flashMessage);
        }

        return $response;
    }

    protected function organizationRouteName(string $name): string
    {
        $admin = $this->isAdminOrganizationPortal();

        return match ($name) {
            'dashboard' => $admin ? 'admin.index.get' : 'tenant.dashboard',
            'billing' => $admin ? 'admin.org.billing' : 'tenant.billing',
            'billing.currency' => $admin ? 'admin.org.billing.currency' : 'tenant.billing.currency',
            'billing.stripe.checkout' => $admin ? 'admin.org.billing.stripe.checkout' : 'tenant.billing.stripe.checkout',
            'billing.payfast.checkout' => $admin ? 'admin.org.billing.payfast.checkout' : 'tenant.billing.payfast.checkout',
            'billing.bank-transfer.checkout' => $admin ? 'admin.org.billing.bank-transfer.checkout' : 'tenant.billing.bank-transfer.checkout',
            'billing.bank-transfer.show' => $admin ? 'admin.org.billing.bank-transfer.show' : 'tenant.billing.bank-transfer.show',
            'billing.bank-transfer.report' => $admin ? 'admin.org.billing.bank-transfer.report' : 'tenant.billing.bank-transfer.report',
            'billing.invoice.show' => $admin ? 'admin.org.billing.invoice.show' : 'tenant.billing.invoice.show',
            'support.index' => $admin ? 'admin.org.support.index' : 'tenant.support.index',
            'support.create' => $admin ? 'admin.org.support.create' : 'tenant.support.create',
            'support.store' => $admin ? 'admin.org.support.store' : 'tenant.support.store',
            'support.show' => $admin ? 'admin.org.support.show' : 'tenant.support.show',
            'support.reply' => $admin ? 'admin.org.support.reply' : 'tenant.support.reply',
            'referrals' => $admin ? 'admin.org.referrals' : 'tenant.referrals',
            'referrals.issue' => $admin ? 'admin.org.referrals.issue' : 'tenant.referrals.issue',
            default => throw new \InvalidArgumentException("Unknown organization route [{$name}]"),
        };
    }
}
