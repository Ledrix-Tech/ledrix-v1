<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('auth_admin')) {
    function auth_admin()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            // abort(302, '', ['Location' => route('admin.login.get')]);
            return back()->with('info', "You don't have access to this page");
        }
        return $admin;
    }
}

if (!function_exists('super_admin')) {
    function super_admin()
    {
        $super_admin = Auth::guard('admin')->user();
        // Redirect to login if not logged in
        if (!$super_admin) {
            // abort(302, '', ['Location' => route('admin.login.get')]);
            return back()->with('info', "You don't have access to this page");
        }
        // Only allow super admins
        if ($super_admin->role !== 'super_admin') {
            // abort(403, 'Unauthorized access. Only Super Admins can access this section.');
            return back()->with('info', "You don't have access to this page");
        }
        return $super_admin;
    }
}




if (!function_exists('currentUser')) {
    function currentUser()
    {
        return Auth::guard('admin')->user()
            ?? Auth::guard('seller')->user()
            ?? Auth::guard('client')->user();
    }
}

if (!function_exists('authRole')) {
    function authRole(): string
    {
        // 🔹 Admin or Finance
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();

            if (isset($admin->role) && $admin->role === 'finance') {
                return 'finance';
            }

            return 'admin';
        }

        // 🔹 Seller Roles
        if (Auth::guard('seller')->check()) {
            $seller = Auth::guard('seller')->user();
            return $seller->is_seller ?? 'seller';
        }

        // 🔹 Client Role
        if (Auth::guard('client')->check()) {
            return 'client';
        }

        return 'guest';
    }
}


if (!function_exists('isAdmin')) {
    function isAdmin(): bool
    {
        return authRole() === 'admin';
    }
}

if (!function_exists('isSeller')) {
    function isSeller(): bool
    {
        return authRole() === 'seller';
    }
}

if (!function_exists('isFinance')) {
    function isFinance(): bool
    {
        return authRole() === 'finance';
    }
}

if (!function_exists('isFrontSeller')) {
    function isFrontSeller(): bool
    {
        return authRole() === 'front_seller';
    }
}

if (!function_exists('isProjectManager')) {
    function isProjectManager(): bool
    {
        return authRole() === 'project_manager';
    }
}

if (! function_exists('org_route')) {
    /**
     * Named route for organization portal pages (tenant-profile or admin CRM).
     */
    function org_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $admin = request()->routeIs('admin.org.*')
            || (($GLOBALS['__organization_portal'] ?? null) === 'admin')
            || (view()->shared('organizationPortal') === 'admin');

        $map = [
            'dashboard' => $admin ? 'admin.index.get' : 'tenant.dashboard',
            'overview' => $admin ? 'admin.org.overview' : 'tenant.dashboard',
            'plan' => $admin ? 'admin.org.plan' : 'tenant.plan',
            'settings' => $admin ? 'admin.org.settings' : 'tenant.settings',
            'settings.update' => $admin ? 'admin.org.settings.update' : 'tenant.settings.update',
            'domain' => $admin ? 'admin.org.domain' : 'tenant.domain',
            'domain.update' => $admin ? 'admin.org.domain.update' : 'tenant.domain.update',
            'domain.verify' => $admin ? 'admin.org.domain.verify' : 'tenant.domain.verify',
            'domain.branding' => $admin ? 'admin.org.domain.branding' : 'tenant.domain.branding',
            'audit-logs' => $admin ? 'admin.org.audit-logs' : 'tenant.audit-logs',
            'data-export' => $admin ? 'admin.org.data-export' : 'tenant.data-export',
            'data-export.store' => $admin ? 'admin.org.data-export.store' : 'tenant.data-export.store',
            'data-export.download' => $admin ? 'admin.org.data-export.download' : 'tenant.data-export.download',
            'team' => 'admin.org.team',
            'api-tokens' => 'admin.org.api-tokens',
            'billing' => $admin ? 'admin.org.billing' : 'tenant.billing',
            'billing.currency' => $admin ? 'admin.org.billing.currency' : 'tenant.billing.currency',
            'billing.auto-renew' => $admin ? 'admin.org.billing.auto-renew' : 'tenant.billing.auto-renew',
            'billing.cancel' => $admin ? 'admin.org.billing.cancel' : 'tenant.billing.cancel',
            'billing.stripe.checkout' => $admin ? 'admin.org.billing.stripe.checkout' : 'tenant.billing.stripe.checkout',
            'billing.payfast.checkout' => $admin ? 'admin.org.billing.payfast.checkout' : 'tenant.billing.payfast.checkout',
            'billing.jazzcash.checkout' => $admin ? 'admin.org.billing.jazzcash.checkout' : 'tenant.billing.jazzcash.checkout',
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
        ];

        if (! isset($map[$name])) {
            throw new InvalidArgumentException("Unknown organization route [{$name}]");
        }

        return route($map[$name], $parameters, $absolute);
    }
}
